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
#

import time
import logging
import threading
import requests
import datetime
import os

# ------------------------------------------------------------------------------


class jeedom_com():
    def __init__(self, apikey='', url='', cycle=0.5, retry=3):
        self.apikey = apikey
        self.url = url
        self.cycle = cycle
        self.retry = retry

    def send_change_immediate(self, change):
        threading.Thread(target=self.thread_change, args=(change,)).start()

    def thread_change(self, change):
        logging.info('Send to jeedom : %s', change)
        i = 0
        while i < self.retry:
            try:
                r = requests.post(self.url + '?apikey=' + self.apikey,
                                  json=change, timeout=(0.5, 120), verify=False)
                if r.status_code == requests.codes.ok:
                    break
            except Exception as error:
                logging.error(
                    'Error on send request to jeedom %s retry : %i/%i', error, i, self.retry)
            i = i + 1

    def test(self):
        try:
            response = requests.get(
                self.url + '?apikey=' + self.apikey, verify=False)
            if response.status_code != requests.codes.ok:
                logging.error('Callback error: %s %s. Please check your network configuration page',
                              response.status.code, response.status.message)
                return False
        except Exception as e:
            logging.error(
                'Callback result as a unknown error: %s. Please check your network configuration page', e.message)
            return False
        return True

# ------------------------------------------------------------------------------


class jeedom_utils():

    @staticmethod
    def convert_log_level(level='error'):
        LEVELS = {'debug': logging.DEBUG,
                  'info': logging.INFO,
                  'notice': logging.WARNING,
                  'warning': logging.WARNING,
                  'error': logging.ERROR,
                  'critical': logging.CRITICAL,
                  'none': logging.CRITICAL}
        return LEVELS.get(level, logging.CRITICAL)

    @staticmethod
    def set_log_level(level='error'):
        FORMAT = '[%(asctime)-15s][%(levelname)s] : %(message)s'
        logging.basicConfig(level=jeedom_utils.convert_log_level(
            level), format=FORMAT, datefmt="%Y-%m-%d %H:%M:%S")

    @staticmethod
    def write_pid(path):
        pid = str(os.getpid())
        logging.info("Writing PID %s to %s", pid, path)
        open(path, 'w').write("%s\n" % pid)
