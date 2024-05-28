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

server = None
CHARGERS = {}


class ChargePoint(cp):
    @on(Action.Heartbeat)
    def on_heartbeat(self):
        return call_result.HeartbeatPayload(datetime.datetime.now(
            datetime.timezone.utc).isoformat()
        )

    @on(Action.BootNotification)
    def on_boot_notification(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'boot', 'cp_id': self.id, 'data': kwargs})
        return call_result.BootNotificationPayload(
            current_time=datetime.datetime.now(
                datetime.timezone.utc).isoformat(),
            interval=3600,
            status=RegistrationStatus.accepted,
        )

    @on(Action.StatusNotification, skip_schema_validation=True)
    def on_status_notification(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'status', 'cp_id': self.id, 'data': kwargs})
        return call_result.StatusNotificationPayload()

    @on(Action.Authorize)
    def on_authorize(self, **kwargs):
        kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
        jeedom_com.send_change_immediate(
            {'event': 'authorize', 'cp_id': self.id, 'data': kwargs})
        return call_result.AuthorizePayload(id_tag_info=kwargs['id_tag_info'])

    @on(Action.StartTransaction)
    def on_start_transaction(self, **kwargs):
        kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
        kwargs['transaction_id'] = int(datetime.datetime.now().strftime('%s'))
        jeedom_com.send_change_immediate(
            {'event': 'start_transaction', 'cp_id': self.id, 'data': kwargs})
        return call_result.StartTransactionPayload(
            transaction_id=kwargs['transaction_id'], id_tag_info=kwargs['id_tag_info']
        )

    @on(Action.StopTransaction)
    def on_stop_transaction(self, **kwargs):
        kwargs['id_tag_info'] = self.get_auth(kwargs['id_tag'])
        jeedom_com.send_change_immediate(
            {'event': 'stop_transaction', 'cp_id': self.id, 'data': kwargs})
        return call_result.StopTransactionPayload(id_tag_info=kwargs['id_tag_info'])

    @on(Action.MeterValues)
    def on_meter_values(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'meter_values', 'cp_id': self.id, 'data': kwargs})
        return call_result.MeterValuesPayload()

    @on(Action.DataTransfer)
    def on_data_transfer(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'data_transfer', 'cp_id': self.id, 'data': kwargs})
        return call_result.DataTransferPayload(status=DataTransferStatus.accepted)

    @on(Action.SecurityEventNotification)
    def on_security_event_notification(self, **kwargs):
        logging.debug("SecurityEventNotification : %s", kwargs)
        return call_result.SecurityEventNotificationPayload()

    @on(Action.FirmwareStatusNotification)
    def on_firmware_status_notification(self, **kwargs):
        logging.debug("FirmwareStatusNotification : %s", kwargs)
        return call_result.FirmwareStatusNotificationPayload()

    def get_auth(self, idTag: str):
        if (idTag in self.auth_list):
            auth = self.auth_list[idTag].copy()
            auth['status'] = getattr(
                AuthorizationStatus, auth['status'], AuthorizationStatus.blocked)
            return auth
        return {'status': getattr(
                AuthorizationStatus, self.auth_list['default'], AuthorizationStatus.invalid)}

    async def set_auth_list(self, authList: dict = {}):
        self.auth_list = authList
        return {"status": "Accepted"}

    async def get_configuration(self, key: str = ""):
        if key == "":
            response = await self.call(call.GetConfigurationPayload())
        else:
            response = await self.call(call.GetConfigurationPayload(key=key.split()))
        return response.__dict__

    async def change_configuration(self, key: str, value: str):
        response = await self.call(call.ChangeConfigurationPayload(key=key, value=value))
        return response.__dict__

    async def change_availability(self, connectorId: int, availability: str):
        response = await self.call(call.ChangeAvailabilityPayload(connector_id=connectorId, type=availability))
        return response.__dict__

    async def start_transaction(self, connectorId: int, idTag: str):
        response = await self.call(call.RemoteStartTransactionPayload(connector_id=connectorId, id_tag=idTag))
        return response.__dict__

    async def stop_transaction(self, transactionId: int):
        response = await self.call(call.RemoteStopTransactionPayload(transaction_id=transactionId))
        return response.__dict__

    async def trigger_message(self, requestedMessage: str, connectorId: str = None):
        response = await self.call(call.TriggerMessagePayload(requested_message=requestedMessage, connector_id=connectorId))
        return response.__dict__

    async def reset(self, type: str = "Soft"):
        response = await self.call(call.ResetPayload(type))
        return response.__dict__


# ----------------------------------------------------------------------------


async def on_connect(websocket, path):
    if path == '/Jeedom':
        message = json.loads(await websocket.recv())
        if message['apikey'] != _apikey:
            logging.error("Invalid apikey from websocket: %s", message)
            return
        del message['apikey']
        logging.debug("Message from Jeedom : %s", message)
        if message['cp_id'] not in CHARGERS:
            logging.error(
                "Charge point : %s not registered in central system", message['cp_id'])
        else:
            cp = CHARGERS[message['cp_id']]
            response = await getattr(cp, message['method'])(*message['args'])
            await websocket.send(json.dumps(response))
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

        cp_id = path.strip("/")
        if cp_id is None:
            logging.error(
                "Charge point ID unspecified, please check charge point configuration")
            return await websocket.close()

        cp = ChargePoint(cp_id, websocket)
        cp.auth_list = {'default': 'invalid'}
        CHARGERS[cp_id] = cp
        jeedom_com.send_change_immediate(
            {'event': 'connect', 'cp_id': cp_id})

        try:
            await cp.start()
        except websockets.exceptions.ConnectionClosed:
            del CHARGERS[cp_id]
            jeedom_com.send_change_immediate(
                {'event': 'disconnect', 'cp_id': cp_id})


async def main():
    server = await websockets.serve(
        on_connect, "0.0.0.0", _socket_port, subprotocols=["ocpp1.6"]
        # , ping_interval=60, ping_timeout=60
    )

    logging.info("OCPP Server Started listening to new connections...")
    await server.wait_closed()

# ----------------------------------------------------------------------------


def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()


def shutdown():
    logging.debug("Shutdown")
    try:
        server.close()
    except:
        pass
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
_pidfile = '/tmp/demond.pid'
_apikey = ''
_callback = ''

parser = argparse.ArgumentParser(
    description='OCPP Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument(
    "--socketport", help="Port for OCPP server communication", type=str)
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
