# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import sys
import os
import traceback
import signal
import json
import argparse
import datetime
import asyncio
import websockets
from ocpp.routing import on
from ocpp.v16 import ChargePoint as cp
from ocpp.v16 import call, call_result
from ocpp.v16.enums import (
    Action, AuthorizationStatus, DataTransferStatus, RegistrationStatus)


try:
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

CHARGERS = {}


class ChargePoint(cp):
    @on(Action.Heartbeat)
    def on_heartbeat(self):
        return call_result.Heartbeat(datetime.datetime.now(
            datetime.timezone.utc).isoformat()
        )

    @on(Action.BootNotification)
    def on_boot_notification(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'boot', 'cp_id': self.id, 'data': kwargs})
        return call_result.BootNotification(
            current_time=datetime.datetime.now(
                datetime.timezone.utc).isoformat(),
            interval=3600,
            status=RegistrationStatus.accepted,
        )

    @on(Action.StatusNotification, skip_schema_validation=True)
    def on_status_notification(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'status', 'cp_id': self.id, 'data': kwargs})
        return call_result.StatusNotification()

    @on(Action.Authorize)
    def on_authorize(self, **kwargs):
        kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
        jeedom_com.send_change_immediate(
            {'event': 'authorize', 'cp_id': self.id, 'data': kwargs})
        return call_result.Authorize(id_tag_info=kwargs['id_tag_info'])

    @on(Action.StartTransaction)
    def on_start_transaction(self, **kwargs):
        kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
        kwargs['transaction_id'] = int(datetime.datetime.now().strftime('%s'))
        jeedom_com.send_change_immediate(
            {'event': 'start_transaction', 'cp_id': self.id, 'data': kwargs})
        return call_result.StartTransaction(
            transaction_id=kwargs['transaction_id'], id_tag_info=kwargs['id_tag_info']
        )

    @on(Action.StopTransaction)
    def on_stop_transaction(self, **kwargs):
        if kwargs['id_tag']:
            kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
            jeedom_com.send_change_immediate(
                {'event': 'stop_transaction', 'cp_id': self.id, 'data': kwargs})
            return call_result.StopTransaction(id_tag_info=kwargs['id_tag_info'])
        jeedom_com.send_change_immediate(
            {'event': 'stop_transaction', 'cp_id': self.id, 'data': kwargs})
        return call_result.StopTransaction()

    @on(Action.MeterValues)
    def on_meter_values(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'meter_values', 'cp_id': self.id, 'data': kwargs})
        return call_result.MeterValues()

    @on(Action.DataTransfer)
    def on_data_transfer(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'data_transfer', 'cp_id': self.id, 'data': kwargs})
        return call_result.DataTransfer(status=DataTransferStatus.accepted)

    @on(Action.SecurityEventNotification)
    def on_security_event_notification(self, **kwargs):
        logging.debug("SecurityEventNotification : %s", kwargs)
        return call_result.SecurityEventNotification()

    @on(Action.FirmwareStatusNotification)
    def on_firmware_status_notification(self, **kwargs):
        logging.debug("FirmwareStatusNotification : %s", kwargs)
        return call_result.FirmwareStatusNotification()

    def get_auth(self, idTag: str):
        if (idTag in self.auth_list):
            auth = self.auth_list[idTag].copy()
            auth['status'] = getattr(
                AuthorizationStatus, auth['status'], AuthorizationStatus.blocked)
            return auth
        return {'status': AuthorizationStatus.invalid}

    async def set_auth_list(self, authList: dict = {}):
        self.auth_list = authList
        return {"status": "Accepted"}

    async def get_configuration(self, key: str = None):
        req = call.GetConfiguration(key)
        return await self.call(req)

    async def change_configuration(self, key: str, value: str):
        req = call.ChangeConfiguration(key=key, value=value)
        return await self.call(req)

    async def change_availability(self, connectorId: int, availability: str):
        req = call.ChangeAvailability(
            connector_id=connectorId, type=availability)
        return await self.call(req)

    async def start_transaction(self, connectorId: int, idTag: str):
        req = call.RemoteStartTransaction(
            connector_id=connectorId, id_tag=idTag)
        return await self.call(req)

    async def stop_transaction(self, transactionId: int):
        req = call.RemoteStopTransaction(transaction_id=transactionId)
        return await self.call(req)

    async def trigger_message(self, requestedMessage: str, connectorId: str = None):
        req = call.TriggerMessage(
            requested_message=requestedMessage, connector_id=connectorId)
        return await self.call(req)

    async def get_composite_schedule(self, connectorId: int, duration: int):
        req = call.GetCompositeSchedule(
            connector_id=connectorId, duration=duration)
        return await self.call(req)

    async def set_charging_profile(self, connectorId: int, chargingProfile: dict = {}):
        req = call.SetChargingProfile(
            connector_id=connectorId, cs_charging_profiles=chargingProfile)
        return await self.call(req)

    async def reset(self, type: str = "Soft"):
        req = call.Reset(type)
        return await self.call(req)

    async def disconnect(self):
        del CHARGERS[self.id]
        await self._connection.close()
        return {"status": "Accepted"}


# ----------------------------------------------------------------------------


async def on_connect(websocket, path):
    path = list(filter(None, path.split('/')))
    cp_id = path[0]

    if len(path) == 2 and path[1] == "Jeedom":
        message = json.loads(await websocket.recv())
        if message['apikey'] != _apikey:
            logging.error("Invalid apikey from Jeedom: %s", message)
            await websocket.send(json.dumps({"status": "Invalid"}))
        else:
            del message['apikey']
            logging.debug("Message from Jeedom: %s", message)

            if cp_id not in CHARGERS:
                logging.error(
                    "Charge point: %s not registered in central system", cp_id)
                await websocket.send(json.dumps({"status": "Unregistered"}))
            else:
                cp = CHARGERS[cp_id]
                response = await getattr(cp, message['method'])(*message['args'])
                logging.debug("Response: %s", response)
                if type(response) is dict:
                    await websocket.send(json.dumps(response))
                else:
                    await websocket.send(json.dumps(response.__dict__))

        return await websocket.close()
    else:
        try:
            requested_protocols = websocket.request_headers["Sec-WebSocket-Protocol"]
        except KeyError:
            logging.error(
                "Client hasn't requested any Subprotocol. Closing Connection")
            return await websocket.close()
        if websocket.subprotocol:
            logging.info("Protocols Matched: %s", websocket.subprotocol)
        else:
            logging.warning(
                "Protocols Mismatched | Expected Subprotocols: %s,"
                " but client supports  %s | Closing connection",
                websocket.available_subprotocols,
                requested_protocols,
            )
            return await websocket.close()

        if cp_id is None:
            logging.error(
                "Charge point ID unspecified, please check charge point configuration")
            return await websocket.close()

        cp = ChargePoint(cp_id, websocket)
        cp.auth_list = {}
        CHARGERS[cp_id] = cp
        jeedom_com.send_change_immediate(
            {'event': 'connect', 'cp_id': cp_id})
        try:
            await cp.start()
        except websockets.exceptions.ConnectionClosed as e:
            if cp_id in CHARGERS:
                del CHARGERS[cp_id]
                jeedom_com.send_change_immediate(
                    {'event': 'disconnect', 'cp_id': cp_id, 'error': e})


async def main():
    server = await websockets.serve(
        on_connect, "0.0.0.0", _socket_port, subprotocols=["ocpp1.6", "ocpp2.0.1"], ping_interval=30, ping_timeout=None
    )

    logging.info("OCPP Server Started listening to new connections...")
    await server.wait_closed()

# ----------------------------------------------------------------------------


def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logging.debug("Removing PID file %s", _pidfile)
    try:
        os.remove(_pidfile)
    except:
        pass
    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)

# ----------------------------------------------------------------------------


_log_level = 'error'
_socket_port = 9000
_pidfile = '/tmp/ocppd.pid'
_apikey = ''
_callback = ''

parser = argparse.ArgumentParser(
    description='OCPP Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument(
    "--socketport", help="Port for OCPP Server", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.socketport:
    _socket_port = args.socketport

_socket_port = int(_socket_port)

jeedom_utils.set_log_level(_log_level)

logging.info('Start ocppd')
logging.info('Log level : '+str(_log_level))
logging.info('Websocket port : '+str(_socket_port))
logging.info('PID file : '+str(_pidfile))
logging.info('Apikey : '+str(_apikey))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey=_apikey, url=_callback)
    if not jeedom_com.test():
        logging.error(
            'Network communication issues. Please fixe your Jeedom network configuration.')
        shutdown()
    asyncio.run(main())
except Exception as e:
    logging.error('Fatal error : '+str(e))
    logging.info(traceback.format_exc())
    shutdown()
