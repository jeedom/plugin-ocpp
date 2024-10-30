<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

// const _FEATURES = array('Core', 'FirmwareManagement', 'LocalAuthListManagement', 'Reservation', 'RemoteTrigger', 'SmartCharging');
const _MESSAGES = array('BootNotification', 'DiagnosticsStatusNotification', 'FirmwareStatusNotification', 'Heartbeat', 'MeterValues', 'StatusNotification');
const _RESET = array('Soft', 'Hard');
const _STATUSES = array(
  'operative' => ['Available', 'Preparing', 'Charging', 'SuspendedEVSE', 'SuspendedEV', 'Finishing', 'Reserved'],
  'inoperative' => ['Unavailable', 'Faulted']
);

class ocpp extends eqLogic {

  public static function standardConfiguration() {
    return array(
      'AllowOfflineTxForUnknownId' => array(
        'name' => __('Transactions inconnues (déconnecté)', __FILE__),
        'description' => __('Autoriser les transactions inconnues lorsque le système central est déconnecté', __FILE__),
        'required' => false,
        'type' => 'checkbox'
      ),
      'AuthorizationCacheEnabled' => array(
        'name' => __("Cache d'autorisations", __FILE__),
        'description' => __('Activer le cache pour les autorisations', __FILE__),
        'required' => false,
        'type' => 'checkbox'
      ),
      'AuthorizeRemoteTxRequests' => array(
        'name' => __('Transactions à distance', __FILE__),
        'description' => __('Autoriser les transactions à distance', __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'BlinkRepeat' => array(
        'name' => __('Nombre de clignotements', __FILE__),
        'description' => __("Nombre de clignotements de la borne lors d'une alerte", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'ClockAlignedDataInterval' => array(
        'name' => __('ClockAlignedDataInterval', __FILE__),
        'description' => __("ClockAlignedDataInterval", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'ConnectionTimeOut' => array(
        'name' => __('ConnectionTimeOut', __FILE__),
        'description' => __("ConnectionTimeOut", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'ConnectorPhaseRotation' => array(
        'name' => __('ConnectorPhaseRotation', __FILE__),
        'description' => __("ConnectorPhaseRotation", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'ConnectorPhaseRotationMaxLength' => array(
        'name' => __('ConnectorPhaseRotationMaxLength', __FILE__),
        'description' => __("ConnectorPhaseRotationMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'GetConfigurationMaxKeys' => array(
        'name' => __('GetConfigurationMaxKeys', __FILE__),
        'description' => __("GetConfigurationMaxKeys", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'HeartbeatInterval' => array(
        'name' => __('HeartbeatInterval', __FILE__),
        'description' => __("HeartbeatInterval", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'LightIntensity' => array(
        'name' => __('LightIntensity', __FILE__),
        'description' => __("LightIntensity", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'LocalAuthorizeOffline' => array(
        'name' => __('LocalAuthorizeOffline', __FILE__),
        'description' => __("LocalAuthorizeOffline", __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'LocalPreAuthorize' => array(
        'name' => __('LocalPreAuthorize', __FILE__),
        'description' => __("LocalPreAuthorize", __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'MaxEnergyOnInvalidId' => array(
        'name' => __('MaxEnergyOnInvalidId', __FILE__),
        'description' => __("MaxEnergyOnInvalidId", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'MeterValuesAlignedData' => array(
        'name' => __('MeterValuesAlignedData', __FILE__),
        'description' => __("MeterValuesAlignedData", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'MeterValuesAlignedDataMaxLength' => array(
        'name' => __('MeterValuesAlignedDataMaxLength', __FILE__),
        'description' => __("MeterValuesAlignedDataMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'MeterValuesSampledData' => array(
        'name' => __('MeterValuesSampledData', __FILE__),
        'description' => __("MeterValuesSampledData", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'MeterValuesSampledDataMaxLength' => array(
        'name' => __('MeterValuesSampledDataMaxLength', __FILE__),
        'description' => __("MeterValuesSampledDataMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'MeterValueSampleInterval' => array(
        'name' => __('MeterValueSampleInterval', __FILE__),
        'description' => __("MeterValueSampleInterval", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'MinimumStatusDuration' => array(
        'name' => __('MinimumStatusDuration', __FILE__),
        'description' => __("MinimumStatusDuration", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'NumberOfConnectors' => array(
        'name' => __('NumberOfConnectors', __FILE__),
        'description' => __("NumberOfConnectors", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'ResetRetries' => array(
        'name' => __('ResetRetries', __FILE__),
        'description' => __("ResetRetries", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'StopTransactionOnEVSideDisconnect' => array(
        'name' => __('StopTransactionOnEVSideDisconnect', __FILE__),
        'description' => __("StopTransactionOnEVSideDisconnect", __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'StopTransactionOnInvalidId' => array(
        'name' => __('StopTransactionOnInvalidId', __FILE__),
        'description' => __("StopTransactionOnInvalidId", __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'StopTxnAlignedData' => array(
        'name' => __('StopTxnAlignedData', __FILE__),
        'description' => __("StopTxnAlignedData", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'StopTxnAlignedDataMaxLength' => array(
        'name' => __('StopTxnAlignedDataMaxLength', __FILE__),
        'description' => __("StopTxnAlignedDataMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'StopTxnSampledData' => array(
        'name' => __('StopTxnSampledData', __FILE__),
        'description' => __("StopTxnSampledData", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'StopTxnSampledDataMaxLength' => array(
        'name' => __('StopTxnSampledDataMaxLength', __FILE__),
        'description' => __("StopTxnSampledDataMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'SupportedFeatureProfiles' => array(
        'name' => __('SupportedFeatureProfiles', __FILE__),
        'description' => __("SupportedFeatureProfiles", __FILE__),
        'required' => true,
        'type' => 'text'
      ),
      'SupportedFeatureProfilesMaxLength' => array(
        'name' => __('SupportedFeatureProfilesMaxLength', __FILE__),
        'description' => __("SupportedFeatureProfilesMaxLength", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'TransactionMessageAttempts' => array(
        'name' => __('TransactionMessageAttempts', __FILE__),
        'description' => __("TransactionMessageAttempts", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'TransactionMessageRetryInterval' => array(
        'name' => __('TransactionMessageRetryInterval', __FILE__),
        'description' => __("TransactionMessageRetryInterval", __FILE__),
        'required' => true,
        'type' => 'number'
      ),
      'UnlockConnectorOnEVSideDisconnect' => array(
        'name' => __('UnlockConnectorOnEVSideDisconnect', __FILE__),
        'description' => __("UnlockConnectorOnEVSideDisconnect", __FILE__),
        'required' => true,
        'type' => 'checkbox'
      ),
      'WebSocketPingInterval' => array(
        'name' => __('WebSocketPingInterval', __FILE__),
        'description' => __("WebSocketPingInterval", __FILE__),
        'required' => false,
        'type' => 'number'
      ),
      'LocalAuthListEnabled' => array(
        'name' => __('LocalAuthListEnabled', __FILE__),
        'description' => __("LocalAuthListEnabled", __FILE__),
        'required' => true,
        'type' => 'checkbox',
        'feature' => 'LocalAuthListManagement'
      ),
      'LocalAuthListMaxLength' => array(
        'name' => __('LocalAuthListMaxLength', __FILE__),
        'description' => __("LocalAuthListMaxLength", __FILE__),
        'required' => true,
        'type' => 'number',
        'feature' => 'LocalAuthListManagement'
      ),
      'SendLocalListMaxLength' => array(
        'name' => __('SendLocalListMaxLength', __FILE__),
        'description' => __("SendLocalListMaxLength", __FILE__),
        'required' => true,
        'type' => 'number',
        'feature' => 'LocalAuthListManagement'
      ),
      'ReserveConnectorZeroSupported' => array(
        'name' => __('ReserveConnectorZeroSupported', __FILE__),
        'description' => __("ReserveConnectorZeroSupported", __FILE__),
        'required' => false,
        'type' => 'checkbox',
        'feature' => 'Reservation'
      ),
      'ChargeProfileMaxStackLevel' => array(
        'name' => __('ChargeProfileMaxStackLevel', __FILE__),
        'description' => __("ChargeProfileMaxStackLevel", __FILE__),
        'required' => true,
        'type' => 'number',
        'feature' => 'SmartCharging'
      ),
      'ChargingScheduleAllowedChargingRateUnit' => array(
        'name' => __('ChargingScheduleAllowedChargingRateUnit', __FILE__),
        'description' => __("ChargingScheduleAllowedChargingRateUnit", __FILE__),
        'required' => true,
        'type' => 'text',
        'feature' => 'SmartCharging'
      ),
      'ChargingScheduleMaxPeriods' => array(
        'name' => __('ChargingScheduleMaxPeriods', __FILE__),
        'description' => __("ChargingScheduleMaxPeriods", __FILE__),
        'required' => true,
        'type' => 'number',
        'feature' => 'SmartCharging'
      ),
      'ConnectorSwitch3to1PhaseSupported' => array(
        'name' => __('ConnectorSwitch3to1PhaseSupported', __FILE__),
        'description' => __("ConnectorSwitch3to1PhaseSupported", __FILE__),
        'required' => false,
        'type' => 'checkbox',
        'feature' => 'SmartCharging'
      ),
      'MaxChargingProfilesInstalled' => array(
        'name' => __('MaxChargingProfilesInstalled', __FILE__),
        'description' => __("MaxChargingProfilesInstalled", __FILE__),
        'required' => true,
        'type' => 'number',
        'feature' => 'SmartCharging'
      )
    );
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = __CLASS__;
    $return['state'] = 'nok';
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/ocppd.pid';
    if (file_exists($pid_file)) {
      if (@posix_getsid(trim(file_get_contents($pid_file)))) {
        $return['state'] = 'ok';
      } else {
        shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
      }
    }
    $return['launchable'] = 'ok';
    return $return;
  }

  public static function deamon_start() {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    $cmd = system::getCmdPython3(__CLASS__) . realpath(__DIR__ . '/../../resources/ocppd') . '/ocppd.py';
    $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
    $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, 9000);
    $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/ocpp/core/php/jeeOcpp.php';
    $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
    $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/ocppd.pid';
    log::add(__CLASS__, 'info', __('Démarrage du démon', __FILE__) . ' : ' . $cmd);
    exec($cmd . ' >> ' . log::getPathToLog(__CLASS__ . 'd') . ' 2>&1 &');

    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le port', __FILE__), 'unableStartDeamon');
      return false;
    }
    message::removeAll(__CLASS__, 'unableStartDeamon');
    return true;
  }

  public static function deamon_stop() {
    log::add(__CLASS__, 'info', __('Arrêt du démon', __FILE__));

    foreach (self::byType(__CLASS__, true) as $eqLogic) {
      if ($eqLogic->getStatus('reachable') == 1) {
        $eqLogic->chargerUnreachable();
      }
    }

    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/ocppd.pid';
    if (file_exists($pid_file)) {
      $pid = intval(trim(file_get_contents($pid_file)));
      system::kill($pid);
    }
    system::kill('ocppd.py');
  }

  public function postUpdate() {
    if ($this->getIsEnable() == 0) {
      if ($this->getStatus('reachable') == 1) {
        $this->chargerUnreachable();
      }
    } else {
      $connector = ' ' . __('borne', __FILE__);

      $numberOfConnectors = $this->getLocalConfiguration('NumberOfConnectors');
      if ($numberOfConnectors >= 1) {
        $order = 0;
        foreach (range(0, $numberOfConnectors) as $connectorId) {
          if ($connectorId >= 1) {
            $connector = ' ' . __('connecteur', __FILE__);
            if ($numberOfConnectors > 1) {
              $connector .= ' ' . $connectorId;
            }
          }

          $stateCmd = $this->getCmd('info', 'state::' . $connectorId);
          if (!is_object($stateCmd)) {
            $stateCmd = (new ocppCmd)
              ->setLogicalId('state::' . $connectorId)
              ->setEqLogic_id($this->getId())
              ->setName(__('Etat', __FILE__) . $connector)
              ->setType('info')
              ->setSubType('binary')
              ->setIsVisible(0)
              ->setOrder($order);
            $stateCmd->save();
          }
          $order++;

          $cmd = $this->getCmd('action', 'changeAvailability::' . $connectorId . '::operative');
          if (!is_object($cmd)) {
            $cmd = (new ocppCmd)
              ->setLogicalId('changeAvailability::' . $connectorId . '::operative')
              ->setEqLogic_id($this->getId())
              ->setName(__('Activer', __FILE__) . $connector)
              ->setType('action')
              ->setSubType('other')
              ->setValue($stateCmd->getId())
              ->setTemplate('dashboard', 'core::binaryDefault')
              ->setTemplate('mobile', 'core::binaryDefault')
              ->setOrder($order);
            $cmd->save();
          }
          $order++;

          $cmd = $this->getCmd('action', 'changeAvailability::' . $connectorId . '::inoperative');
          if (!is_object($cmd)) {
            $cmd = (new ocppCmd)
              ->setLogicalId('changeAvailability::' . $connectorId . '::inoperative')
              ->setEqLogic_id($this->getId())
              ->setName(__('Désactiver', __FILE__) . $connector)
              ->setType('action')
              ->setSubType('other')
              ->setValue($stateCmd->getId())
              ->setTemplate('dashboard', 'core::binaryDefault')
              ->setTemplate('mobile', 'core::binaryDefault')
              ->setOrder($order);
            $cmd->save();
          }
          $order++;

          $cmd = $this->getCmd('info', 'status::' . $connectorId);
          if (!is_object($cmd)) {
            $cmd = (new ocppCmd)
              ->setLogicalId('status::' . $connectorId)
              ->setEqLogic_id($this->getId())
              ->setName(__('Statut', __FILE__) . $connector)
              ->setType('info')
              ->setSubType('string')
              ->setDisplay('forceReturnLineBefore', 1)
              ->setDisplay('forceReturnLineAfter', 1)
              ->setOrder($order);
            $cmd->save();
          }
          $order++;

          $cmd = $this->getCmd('info', 'error::' . $connectorId);
          if (!is_object($cmd)) {
            $cmd = (new ocppCmd)
              ->setLogicalId('error::' . $connectorId)
              ->setEqLogic_id($this->getId())
              ->setName(__('Erreur', __FILE__) . $connector)
              ->setType('info')
              ->setSubType('string')
              ->setDisplay('forceReturnLineBefore', 1)
              ->setDisplay('forceReturnLineAfter', 1)
              ->setOrder($order);
            $cmd->save();
          }
          $order++;

          if ($connectorId >= 1) {
            $cmd = $this->getCmd('action', 'startTransaction::' . $connectorId);
            if (!is_object($cmd)) {
              $cmd = (new ocppCmd)
                ->setLogicalId('startTransaction::' . $connectorId)
                ->setEqLogic_id($this->getId())
                ->setName(__('Démarrer charge', __FILE__) . ($numberOfConnectors > 1 ? $connector : ''))
                ->setType('action')
                ->setSubType('select')
                ->setOrder($order);
              $cmd->save();
            }
            $order++;

            $cmd = $this->getCmd('action', 'stopTransaction::' . $connectorId);
            if (!is_object($cmd)) {
              $cmd = (new ocppCmd)
                ->setLogicalId('stopTransaction::' . $connectorId)
                ->setEqLogic_id($this->getId())
                ->setName(__('Arrêter charge', __FILE__) . ($numberOfConnectors > 1 ? $connector : ''))
                ->setType('action')
                ->setSubType('other')
                ->setOrder($order);
              $cmd->save();
            }
            $order++;
          }
        }
      }
    }
  }

  public function chargerInit() {
    $this->setStatus('reachable', 1);
    $this->setStatus('waitingBoot', 1);

    $time = time() - 1;
    while ((time() - $time) < 10) {
      sleep(1);
      if ($this->getStatus('waitingBoot') != 1) {
        break;
      }
      log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Attente de la notification de démarrage...', __FILE__));
      $this->chargerTriggerMessage('BootNotification');
    }

    if ($this->getStatus('waitingBoot') == 1) {
      return $this->chargerUnreachable();
    }

    log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Connecté au système central OCPP', __FILE__));
    $this->chargerUpdateAuthList();

    $this->setLocalConfiguration($this->chargerGetConfiguration());

    $numberOfConnectors = $this->getLocalConfiguration('NumberOfConnectors', 1);
    foreach (range(0, $numberOfConnectors) as $connectorId) {
      sleep(1);
      $lastStatus = strtotime($this->getCmd('info', 'status::' . $connectorId)->getCollectDate());
      if ($lastStatus < $time) {
        $this->chargerTriggerMessage('StatusNotification', ($connectorId == 0) ? null : $connectorId);
      }
    }

    // TESTS
    // $this->chargerGetCompositeSchedule(0, 86400);
    // $this->chargerGetCompositeSchedule(1, 86400);
  }

  public function chargerUnreachable() {
    $this->chargerDisconnect();
    $this->setStatus('reachable', 0);

    $numberOfConnectors = $this->getLocalConfiguration('NumberOfConnectors', 1);
    foreach (range(0, $numberOfConnectors) as $connectorId) {
      $this->checkAndUpdateCmd('state::' . $connectorId, 0);
      $this->checkAndUpdateCmd('status::' . $connectorId, 'Unreachable');
    }
    log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Déconnecté du système central OCPP', __FILE__));
  }

  public function setAuthList(array $_authList = array()) {
    $logicalId = $this->getLogicalId();
    $file = __DIR__ . '/../../data/' . $logicalId . '.csv';
    if (file_exists($file)) {
      unlink($file);
    }
    if (!empty($_authList)) {
      if (($csv = fopen($file, 'w')) !== false) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' ' . __('Sauvegarde de la liste des autorisations', __FILE__) . ' : ' . '/plugins/ocpp/data/' . $logicalId . '.csv');
        fputcsv($csv, array_map('trim', array_keys($_authList[0])), ';');
        foreach ($_authList as $auth) {
          if (!empty($auth['id'])) {
            fputcsv($csv, array_map('trim', array_values($auth)), ';');
            // TODO
            // if (isset($auth['expiry_date'])) {
            // Use cron task to set expired
            // }
          }
        }
        fclose($csv);
      }
      if (!file_exists($file)) {
        throw new Exception(__('Impossible de sauvegarder la liste des autorisations', __FILE__)) . ' : ' . $file;
      }
    }
    $this->chargerUpdateAuthList();
  }

  public function getAuthList(): array {
    $file = __DIR__ . '/../../data/' . $this->getLogicalId() . '.csv';
    $return = array();
    if (is_file($file) && ($csv = fopen($file, 'r')) !== false) {
      $header = fgetcsv($csv, 1024, ';');
      $fields = count($header) - 1;
      while (($line = fgetcsv($csv, 1024, ';')) !== false) {
        foreach (range(1, $fields) as $authParamIndex) {
          $return[$line[0]][$header[$authParamIndex]] = $line[$authParamIndex];
        }
      }
      fclose($csv);
    }
    return $return;
  }

  public function chargerChangeAvailability(int $_connectorId, string $_availability) {
    if (in_array(strtolower($_availability), array_keys(_STATUSES))) {
      $changeAvailability = $this->sendToCharger(['method' => 'change_availability', 'args' => [$_connectorId, ucfirst($_availability)]]);
    }
    //   case 'Scheduled':
  }

  private function setLocalConfiguration(array $_conf) {
    $file = __DIR__ . '/../../data/' . $this->getLogicalId() . '.json';
    if (is_file($file)) {
      $conf = json_decode(file_get_contents($file), true);
      $_conf = array_merge($conf, $_conf);
    }
    if (!empty($_conf)) {
      return file_put_contents($file, json_encode($_conf, JSON_PRETTY_PRINT));
    }
    return false;
  }

  public function getLocalConfiguration(string $_key = null, $_default = '') {
    $file = __DIR__ . '/../../data/' . $this->getLogicalId() . '.json';
    if (!is_file($file)) {
      $conf = $this->chargerGetConfiguration();
      $this->setLocalConfiguration($conf);
    } else {
      $conf = json_decode(file_get_contents($file), true);
    }

    if (!$_key) {
      return $conf;
    }
    if (!isset($conf[$_key]) || !isset($conf[$_key]['value'])) {
      return $_default;
    }
    return $conf[$_key]['value'];
  }

  public function chargerChangeConfiguration(string $_key, string $_value, int $_retry = 0) {
    $currentConf = $this->chargerGetConfiguration($_key);
    if ($currentConf[$_key]['value'] == $_value) {
      return true;
    }
    if ($currentConf[$_key]['readonly']) {
      log::add(__CLASS__, 'warning', $this->getHumanName() . ' ' . __('Configuration en lecture seule', __FILE__) . ' : ' . $_key);
      return false;
    }
    $changeConf = $this->sendToCharger(['method' => 'change_configuration', 'args' => [$_key, $_value]]);
    switch (strtolower($changeConf['status'])) {
      case 'accepted':
        return true;

      case 'rejected':
        if ($_retry > 0) {
          return $this->chargerChangeConfiguration($_key, $_value, $_retry--);
        }
        break;

        //   case 'RebootRequired':
    }
    return false;
  }

  private function chargerGetConfiguration(string $_key = null): array {
    $chargerConf = $this->sendToCharger(['method' => 'get_configuration', 'args' => [$_key]]);
    if (!empty($chargerConf['unknown_key'])) {
      log::add(__CLASS__, 'warning', $this->getHumanName() . '[' . __FUNCTION__ . '] ' . _('Clé(s) de configuration inconnue(s)', __FILE__) . ' : ' . print_r($chargerConf['unknown_key'], true));
    }
    if (isset($chargerConf['configuration_key'])) {
      $keys = array_column($chargerConf['configuration_key'], 'key');
      $values = array_map(function ($row) {
        unset($row['key']);
        return $row;
      }, $chargerConf['configuration_key']);
      return array_combine($keys, $values);
    }
    return $chargerConf;
  }

  public function chargerStartTransaction(int $_connectorId, string $_idTag = null) {
    if (!$_idTag) {
      $_idTag = ($_SESSION['user'] && $_SESSION['user']->getLogin() != '') ? $_SESSION['user']->getLogin() : __('Inconnu', __FILE__);
    }
    $this->sendToCharger(['method' => 'start_transaction', 'args' => [$_connectorId, $_idTag]]);
  }

  public function chargerStopTransaction(int $_connectorId) {
    $transaction = ocpp_transaction::byCpIdAndConnectorId($this->getLogicalId(), $_connectorId, true);
    if (is_object($transaction)) {
      $this->sendToCharger(['method' => 'stop_transaction', 'args' => [$transaction->getTransactionId()]]);
    } else {
      log::add(__CLASS__, 'warning', $this->getHumanName() . ' ' . __('Aucune transaction trouvée pour le connecteur', __FILE__) . ' ' . $_connectorId);
    }
  }

  public function chargerTriggerMessage(string $_message, int $_connectorId = null, int $_retry = 0): bool {
    if ($this->chargerHasFeature('RemoteTrigger') && in_array($_message, _MESSAGES)) {
      $trigger = $this->sendToCharger(['method' => 'trigger_message', 'args' => [$_message, $_connectorId]]);
      switch (strtolower($trigger['status'])) {
        case 'accepted':
          return true;

        case 'rejected':
          if ($_retry > 0) {
            return $this->chargerTriggerMessage($_message, $_connectorId, $_retry--);
          }
          break;
      }
    }
    return false;
  }

  public function chargerSetMaxPower(float $_powerLimit) {
    $chargingProfile = array(
      'chargingProfileId' => $this->getLocalConfiguration('MaxChargingProfilesInstalled', 1),
      'stackLevel' => $this->getLocalConfiguration('ChargeProfileMaxStackLevel', 1),
      'chargingProfilePurpose' => 'ChargePointMaxProfile',
      'chargingProfileKind' => 'Absolute',
      'chargingSchedule' => array(
        'chargingRateUnit' => 'W',
        'chargingSchedulePeriod' => [array(
          'startPeriod' => 0,
          'limit' => $_powerLimit
        )]
      )
    );
    $this->chargerSetChargingProfile(0, $chargingProfile);
  }

  public function chargerSetChargingProfile(int $_connectorId, array $_chargingProfile) {
    if ($this->chargerHasFeature('SmartCharging')) {
      $setLimit = $this->sendToCharger(['method' => 'set_charging_profile', 'args' => [$_connectorId, $_chargingProfile]]);
    }
  }

  public function chargerUpdateAuthList() {
    $authList = $this->getAuthList();
    $setAuthList = $this->sendToCharger(['method' => 'set_auth_list', 'args' => [$authList]]);
  }

  // private function chargerGetCompositeSchedule(int $_connectorId, int $_duration) {
  //   $schedule =  $this->sendToCharger(['method' => 'get_composite_schedule', 'args' => [$_connectorId, $_duration]]);
  // }

  public function chargerReset(string $_type = 'Soft'): bool {
    if (in_array($_type, _RESET)) {
      $reset = $this->sendToCharger(['method' => 'reset', 'args' => [$_type]]);
      if (strtolower($reset['status']) == 'accepted') {
        return true;
      }
    }
    return false;
  }

  public function chargerHasFeature(string $_feature, string $_features = null): bool {
    if (!$_features) {
      $_features = $this->getLocalConfiguration('SupportedFeatureProfiles');
    }
    $supportedFeatures = array_map(function ($item) {
      return strtolower(trim($item));
    }, explode(',', $_features));

    if (in_array(strtolower($_feature), $supportedFeatures)) {
      return true;
    }
    return false;
  }

  private function chargerDisconnect() {
    $this->sendToCharger(['method' => 'disconnect', 'args' => []]);
  }

  private function sendToCharger(array $_data): array {
    log::add(__CLASS__, 'debug', $this->getHumanName() . ' _' . __FUNCTION__ . '() : ' . print_r($_data, true));
    $return = array();
    if ($this->getStatus('reachable') == 1) {
      $_data['apikey'] = jeedom::getApiKey(__CLASS__);
      $data = json_encode($_data);
      $dataLenght = strlen($data);

      $head = "GET /" . $this->getLogicalId() . "/Jeedom HTTP/1.1" . "\r\n" .
        "Upgrade: WebSocket" . "\r\n" .
        "Connection: Upgrade" . "\r\n" .
        "Origin: http://localhost/" . "\r\n" .
        "Host: localhost" . "\r\n" .
        "Sec-WebSocket-Key: TyPfhFqWTjuw8eDAxdY8xg==\r\n" .
        "Sec-WebSocket-Version: 13\r\n" .
        "Content-Length: " . $dataLenght . "\r\n" . "\r\n";
      $sock = fsockopen('localhost', config::byKey('socketport', __CLASS__, '9000'), $errno, $errstr, 2);
      fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);
      $hanshake = fread($sock, 1024);
      $header = chr(0x80 | 0x01);
      if ($dataLenght < 126) {
        $header .= chr(0x80 | $dataLenght);
      } elseif ($dataLenght < 0xFFFF) {
        $header .= chr(0x80 | 126) . pack("n", $dataLenght);
      } elseif (PHP_INT_SIZE > 4) {
        $header .= chr(0x80 | 127) . pack("Q", $dataLenght);
      } else {
        $header .= chr(0x80 | 127) . pack("N", 0) . pack("N", $dataLenght);
      }
      $mask = pack("N", rand(1, 0x7FFFFFFF));
      $header .= $mask;
      for ($i = 0; $i < $dataLenght; $i++) {
        $data[$i] = chr(ord($data[$i]) ^ ord($mask[$i % 4]));
      }

      fwrite($sock, $header . $data) or die('error:' . $errno . ':' . $errstr);
      $response = '';
      while (!feof($sock)) {
        $response .= fread($sock, 2048);
        if (preg_match('/{.*}?}/', $response, $match) && is_array($result = json_decode($match[0], true))) {
          $return = $result;
          break;
        }
      }
      fclose($sock);
    }
    log::add(__CLASS__, 'debug', $this->getHumanName() . ' _' . __FUNCTION__ . '(' . $_data['method'] . ') : ' . print_r($return, true));
    return $return;
  }
}

class ocppCmd extends cmd {

  public static function statuses(string $_status) {
    $statuses = array(
      'Available' => __('Disponible', __FILE__),
      'Preparing' => __('Préparation en cours', __FILE__),
      'Charging' => __('Charge en cours', __FILE__),
      'SuspendedEVSE' => __('Charge suspendue (borne)', __FILE__),
      'SuspendedEV' => __('Charge suspendue (véhicule)', __FILE__),
      'Finishing' => __('Fin de charge', __FILE__),
      'Reserved' => __('Réservé', __FILE__),
      'Unavailable' => __('Indisponible', __FILE__),
      'Faulted' => __('Erreur', __FILE__),
      'Unreachable' => __('Injoignable', __FILE__)
    );

    if (in_array($_status, array_keys($statuses))) {
      return $statuses[$_status];
    }
    return $_status;
  }

  public static function measurands(string $_measurand = null) {
    $measurands = array(
      'Current.Import' => __('Courant consommé', __FILE__),
      'Current.Export' => __('Courant injecté', __FILE__),
      'Current.Offered' => __('Courant maximal', __FILE__),
      'Energy.Active.Import.Register' => __('Energie consommée', __FILE__),
      'Energy.Active.Export.Register' => __('Energie injectée', __FILE__),
      'Power.Active.Import' => __('Puissance consommée', __FILE__),
      'Power.Active.Export' => __('Puissance injectée', __FILE__),
      'Power.Offered' => __('Puissance maximale', __FILE__),
      'Voltage' => __('Tension', __FILE__),
      'Frequency' => __('Fréquence', __FILE__),
      'Power.Factor' => __('Facteur de puissance', __FILE__),
      'SoC' => __('Niveau de charge', __FILE__),
      'Temperature' => __('Température', __FILE__),
      'RPM' => __('Vitesse du ventilateur', __FILE__)
    );
    if ($_measurand) {
      if (in_array($_measurand, array_keys($measurands))) {
        return $measurands[$_measurand];
      }
      return str_replace('.', ' ', $_measurand);
    }
    return $measurands;
  }

  public function dontRemoveCmd() {
    return true;
  }

  public function formatValueWidget($_value) {
    if (substr($this->getLogicalId(), 0, 6) == 'status') {
      $color = 'icon_red';
      if (in_array($_value, _STATUSES['operative'])) {
        $color = 'icon_green';
      }
      return '<span class="' . $color . '">' . self::statuses($_value) . '<span>';
    }
    return $_value;
  }

  public function execute($_options = array()) {
    log::add('ocpp', 'debug', $this->getHumanName() . ' ' . __('Execution', __FILE__) . ' ' . print_r($_options, true));
    if ($this->getType() == 'info') {
      return;
    }

    $eqLogic = $this->getEqLogic();

    $logicalArray = explode('::', $this->getLogicalId());
    $method = 'charger' . ucfirst($logicalArray[0]);
    if (method_exists($eqLogic, $method)) {
      unset($logicalArray[0]);
      return $eqLogic->$method(...$logicalArray);
    }
  }
}

class ocpp_transaction {

  private $id;
  private $transactionId;
  private $cpId;
  private $connectorId;
  private $tagId;
  private $start;
  private $end;
  private $options;
  private $_changed = false;

  public static function all() {
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' ORDER BY start';
    return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byId(int $_id) {
    $values = array('id' => $_id);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE id=:id';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byTransactionId(int $_transactionId) {
    $values = array('transactionId' => $_transactionId);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE transactionId=:transactionId';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byCpId(string $_cpId) {
    $values = array('cpId' => $_cpId);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE cpId=:cpId ORDER BY start';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byCpIdAndConnectorId(string $_cpId, int $_connectorId, bool $_inProgress = false) {
    $values = array(
      'cpId' => $_cpId,
      'connectorId' => $_connectorId,
    );
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM ' . __CLASS__ . '
		WHERE cpId=:cpId
		AND connectorId=:connectorId';
    if ($_inProgress) {
      $sql .= ' AND end IS NULL';
      return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }
    $sql .= ' ORDER BY start';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byTagId(string $_tagId) {
    $values = array('tagId' => $_tagId);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE tagId=:tagId ORDER BY start';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public function save(bool $_direct = false) {
    DB::save($this, $_direct);
    return $this;
  }

  public function remove() {
    return DB::remove($this);
  }

  public function executeListener(string $_phase) {
    $listeners = array_merge(listener::searchEvent(__CLASS__ . '::*'), listener::searchEvent(__CLASS__ . '::' . $this->getTagId()));
    if (is_array($listeners) && count($listeners) > 0) {
      foreach ($listeners as $listener) {
        $event = trim($listener->getEvent()[0], '#');
        $datetime = ($_phase == 'start_transaction') ? $this->getStart() : $this->getEnd();
        $listener->execute($event, $_phase, $datetime, $this);
      }
    }
  }

  public function getConsumption() {
    return floatval($this->getOptions('meterStop') - $this->getOptions('meterStart'));
  }

  public function getDuration($_convert = false) {
    $duration = strtotime($this->getEnd()) - strtotime($this->getStart());
    if ($_convert) {
      return convertDuration($duration);
    }
    return $duration;
  }

  public function setId($_id) {
    $this->_changed = utils::attrChanged($this->_changed, $this->id, $_id);
    $this->id = $_id;
    return $this;
  }

  public function getId() {
    return $this->id;
  }

  public function setTransactionId(int $_transactionId) {
    $this->_changed = utils::attrChanged($this->_changed, $this->transactionId, $_transactionId);
    $this->transactionId = $_transactionId;
    return $this;
  }

  public function getTransactionId() {
    return $this->transactionId;
  }

  public function setCpId(string $_cpId) {
    $this->_changed = utils::attrChanged($this->_changed, $this->cpId, $_cpId);
    $this->cpId = $_cpId;
    return $this;
  }

  public function getCpId() {
    return $this->cpId;
  }

  public function setConnectorId(int $_connectorId) {
    $this->_changed = utils::attrChanged($this->_changed, $this->connectorId, $_connectorId);
    $this->connectorId = $_connectorId;
    return $this;
  }

  public function getConnectorId() {
    return $this->connectorId;
  }

  public function setTagId(string $_tagId) {
    $this->_changed = utils::attrChanged($this->_changed, $this->tagId, $_tagId);
    $this->tagId = $_tagId;
    return $this;
  }

  public function getTagId() {
    return $this->tagId;
  }

  public function setStart(string $_start) {
    $this->_changed = utils::attrChanged($this->_changed, $this->start, $_start);
    $this->start = $_start;
    return $this;
  }

  public function getStart() {
    return $this->start;
  }

  public function setEnd(string $_end) {
    $this->_changed = utils::attrChanged($this->_changed, $this->end, $_end);
    $this->end = $_end;
    return $this;
  }

  public function getEnd() {
    return $this->end;
  }

  public function setOptions($_key, $_value) {
    $options = utils::setJsonAttr($this->options, $_key, $_value);
    $this->_changed = utils::attrChanged($this->_changed, $this->options, $options);
    $this->options = $options;
    return $this;
  }

  public function getOptions($_key = '', $_default = '') {
    return utils::getJsonAttr($this->options, $_key, $_default);
  }

  public function setChanged($_changed) {
    $this->_changed = $_changed;
    return $this;
  }

  public function getChanged() {
    return $this->_changed;
  }
}
