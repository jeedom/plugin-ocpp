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
from datetime import datetime, timezone
import asyncio
import websockets
from decimal import Decimal   # >>> MODIF
from ocpp.routing import on
from ocpp.v16 import ChargePoint as cp
from ocpp.v16 import call, call_result
from ocpp.v16.enums import (Action, DataTransferStatus, RegistrationStatus)


try:
    from jeedom.jeedom import *
except ImportError:
    print("Error: importing module jeedom.jeedom")
    sys.exit(1)

CHARGERS = {}

# >>> MODIF : sérialisation JSON sûre (Decimal / datetime)
def json_safe(obj):
    if isinstance(obj, Decimal):
        return float(obj)
    if isinstance(obj, datetime):
        return obj.isoformat()
    return str(obj)
# <<< MODIF

class ChargePoint(cp):
    # >>> MODIF : état interne transaction
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.active_transaction_id = None
    # <<< MODIF
    
    @on(Action.heartbeat)
    def on_heartbeat(self):
        return call_result.Heartbeat(datetime.now(timezone.utc).isoformat())

    @on(Action.boot_notification)
    def on_boot_notification(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'boot', 'cp_id': self.id, 'data': kwargs})
        return call_result.BootNotification(
            current_time=datetime.now(timezone.utc).isoformat(),
            interval=3600,
            status=RegistrationStatus.accepted,
        )

    #@on(Action.status_notification, skip_schema_validation=True)
    #def on_status_notification(self, **kwargs):
    #    jeedom_com.send_change_immediate(
    #        {'event': 'status', 'cp_id': self.id, 'data': kwargs})
    #    return call_result.StatusNotification()
    # >>> MODIF MAJEURE : gestion transaction zombie Schneider
    # >>> FIX SCHNEIDER : gestion état bloqué Preparing
    @on(Action.status_notification, skip_schema_validation=True)
    async def on_status_notification(self, **kwargs):
        status = kwargs.get("status")

        jeedom_com.send_change_immediate(
            {'event': 'status', 'cp_id': self.id, 'data': kwargs}
        )

        if status == "Preparing" and self.active_transaction_id:
            logging.warning(
                f"[{self.id}] Preparing alors qu'une transaction est active "
                f"({self.active_transaction_id}) → StopTransaction forcé"
            )
            try:
                await self.stop_transaction(self.active_transaction_id)
            except Exception as e:
                logging.error(f"[{self.id}] StopTransaction forcé KO: {e}")
            self.active_transaction_id = None

        return call_result.StatusNotification()
    # <<< FIX SCHNEIDER
    
    @on(Action.authorize)
    async def on_authorize(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'authorize', 'cp_id': self.id, 'data': kwargs})
        return call_result.Authorize(id_tag_info=await self.wait_cs_response('id_tag_info', {"status": "Invalid"}))

    @on(Action.start_transaction)
    async def on_start_transaction(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'start_transaction', 'cp_id': self.id, 'data': kwargs}
        )

        transaction_id = await self.wait_cs_response('transaction_id', 0)

        # >>> FIX SCHNEIDER : mémorisation transaction
        self.active_transaction_id = transaction_id
        # <<< FIX SCHNEIDER
        # >>> FIX STATUS RESYNC : borne + connecteur = Charging
        connector_id = kwargs.get('connector_id', 1)

        # Statut BORNE
        jeedom_com.send_change_immediate({
            'event': 'status',
            'cp_id': self.id,
            'data': {
                'connectorId': 0,
                'status': 'Charging',
                'errorCode': 'NoError'
            }
        })

        # Statut CONNECTEUR
        jeedom_com.send_change_immediate({
            'event': 'status',
            'cp_id': self.id,
            'data': {
                'connectorId': connector_id,
                'status': 'Charging',
                'errorCode': 'NoError'
            }
        })
        # <<< FIX STATUS RESYNC
        return call_result.StartTransaction(
            transaction_id=transaction_id,
            id_tag_info=await self.wait_cs_response(
                'id_tag_info', {"status": "Invalid"}
            )
        )

    @on(Action.stop_transaction)
    async def on_stop_transaction(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'stop_transaction', 'cp_id': self.id, 'data': kwargs}
        )

        # >>> FIX SCHNEIDER : libération transaction
        self.active_transaction_id = None
        # <<< FIX SCHNEIDER
        # >>> FIX STATUS RESYNC : borne + connecteur = Available
        connector_id = kwargs.get('connector_id', 1)

        # Statut CONNECTEUR
        jeedom_com.send_change_immediate({
            'event': 'status',
            'cp_id': self.id,
            'data': {
                'connectorId': connector_id,
                'status': 'Available',
                'errorCode': 'NoError'
            }
        })

        # Statut BORNE
        jeedom_com.send_change_immediate({
            'event': 'status',
            'cp_id': self.id,
            'data': {
                'connectorId': 0,
                'status': 'Available',
                'errorCode': 'NoError'
            }
        })
        # <<< FIX STATUS RESYNC
        
        if kwargs.get('id_tag'):
            return call_result.StopTransaction(
                id_tag_info=await self.wait_cs_response(
                    'id_tag_info', {"status": "Invalid"}
                )
            )
        return call_result.StopTransaction()

    @on(Action.meter_values)
    def on_meter_values(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'meter_values', 'cp_id': self.id, 'data': kwargs})
        return call_result.MeterValues()

    @on(Action.data_transfer)
    def on_data_transfer(self, **kwargs):
        jeedom_com.send_change_immediate(
            {'event': 'data_transfer', 'cp_id': self.id, 'data': kwargs})
        return call_result.DataTransfer(status=DataTransferStatus.accepted)

    @on(Action.security_event_notification)
    def on_security_event_notification(self, **kwargs):
        logging.debug("SecurityEventNotification : %s", kwargs)
        return call_result.SecurityEventNotification()

    @on(Action.firmware_status_notification)
    def on_firmware_status_notification(self, **kwargs):
        logging.debug("FirmwareStatusNotification : %s", kwargs)
        return call_result.FirmwareStatusNotification()

    async def wait_cs_response(self, attr, default):
        i = 1
        while i < 60:
            await asyncio.sleep(0.3)
            if hasattr(self, attr):
                result = getattr(self, attr)
                delattr(self, attr)
                return result
            i += 1
        return default

    async def get_configuration(self, key: str = None):
        req = call.GetConfiguration(key=[key])
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

    async def get_composite_schedule(self, connectorId: int, duration: int, chargingRateUnit: str):
        req = call.GetCompositeSchedule(
            connector_id=connectorId, duration=duration, charging_rate_unit=chargingRateUnit)
        return await self.call(req)

    async def set_charging_profile(self, connectorId: int, chargingProfile: dict = {}):
        req = call.SetChargingProfile(
            connector_id=connectorId, cs_charging_profiles=chargingProfile)
        return await self.call(req)

    async def reset(self, type: str = "Soft"):
        req = call.Reset(type)
        return await self.call(req)

    # >>> FIX SCHNEIDER : NE PLUS SUPPRIMER LE CP
    async def disconnect(self):
        try:
            await self._connection.close()
        except Exception:
            pass

        logging.warning(
            f"[{self.id}] WebSocket closed – ChargePoint conservé"
        )
        return {"status": "Accepted"}
    # <<< FIX SCHNEIDER



# ----------------------------------------------------------------------------
# ==============================
# WebSocket handler
# ==============================
async def on_connect(websocket):
    path = list(filter(None, websocket.request.path.split('/')))
    cp_id = path[0]

    # =====================================================
    # >>> FIX CRITIQUE : connexion CS persistante
    # =====================================================
    if len(path) == 2 and path[1] == "cs":
        logging.info(f"CS connected for {cp_id}")

        if cp_id not in CHARGERS:
            await websocket.send(json.dumps({"status": "Retry"}))
            return

        cp = CHARGERS.get(cp_id)
        if not cp:
            return

        try:
            async for raw in websocket:
                message = json.loads(raw)

                if message.get('apikey') != _apikey:
                    await websocket.send(
                        json.dumps({"status": "Invalid"})
                    )
                    continue

                message.pop('apikey', None)

                if message['method'] == 'cs_response':
                    setattr(cp, message['attr'], message['value'])
                    response = {"status": "Accepted"}
                else:
                    response = await getattr(
                        cp,
                        message['method']
                    )(*message['args'])

                await websocket.send(
                    json.dumps(response, default=json_safe)
                )

        except websockets.exceptions.ConnectionClosed:
            logging.warning(f"CS disconnected for {cp_id}")

        return
    # <<< FIX CRITIQUE

    # ---------- CP connection ----------
    try:
        websocket.request.headers["Sec-WebSocket-Protocol"]
    except KeyError:
        return await websocket.close()

    # >>> FIX SCHNEIDER : reconnexion propre
    if cp_id in CHARGERS:
        cp = CHARGERS[cp_id]
        cp._connection = websocket
        logging.info(f"{cp_id} reconnecté (reuse CP)")
    else:
        cp = ChargePoint(cp_id, websocket)
        CHARGERS[cp_id] = cp
        logging.info(f"{cp_id} nouveau CP enregistré")
    # <<< FIX SCHNEIDER

    jeedom_com.send_change_immediate({'event': 'connect', 'cp_id': cp_id})

    try:
        await cp.start()
    except websockets.exceptions.ConnectionClosed as e:
        logging.error(f"{cp_id} websocket closed: {e}")
        jeedom_com.send_change_immediate(
            {'event': 'disconnect', 'cp_id': cp_id}
        )


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
