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
    $cmd = '/usr/bin/python3 ' . realpath(__DIR__ . '/../../resources/ocppd') . '/ocppd.py';
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
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/ocppd.pid';
    if (file_exists($pid_file)) {
      $pid = intval(trim(file_get_contents($pid_file)));
      system::kill($pid);
    }
    system::kill('ocppd.py');

    foreach (self::byType(__CLASS__, true) as $eqLogic) {
      if ($eqLogic->getStatus('reachable') == 1) {
        $eqLogic->chargerUnreachable();
      }
    }
  }

  public function preInsert() {
    $requestedConf = 'SupportedFeatureProfiles NumberOfConnectors';
    $chargerConf = $this->chargerGetConfiguration($requestedConf, true);
    if (count($chargerConf) != count(explode(' ', $requestedConf))) {
      throw new Exception(__('Abandon, clé de configuration manquante', __FILE__) . ' : ' . $requestedConf .  ' != ' . print_r($chargerConf, true));
    }
  }

  public function postSave() {
    if ($this->getIsEnable() == 0) {
      if ($this->getStatus('reachable') == 1) {
        $this->chargerUnreachable();
      }
    }
  }


  public function postAjax() {
    $connector = ' ' . __('borne', __FILE__);

    $numberOfConnectors = $this->getConfiguration('NumberOfConnectors', 1);
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
            // ->setTemplate('dashboard', 'core::binaryDefault')
            // ->setTemplate('mobile', 'core::binaryDefault')
            ->setOrder($order);
        }
        // if ($this->getConfiguration('authorize_all_transactions', 0) == 1) {
        //   $cmd->setSubType('other');
        //   $cmd->save();
        // } else {
        //   $cmd->setSubType('select');
        //   $cmd->save();
        // }
        $order++;

        $cmd = $this->getCmd('action', 'stopTransaction::' . $connectorId);
        if (!is_object($cmd)) {
          $cmd = (new ocppCmd)
            ->setLogicalId('stopTransaction::' . $connectorId)
            ->setEqLogic_id($this->getId())
            ->setName(__('Arrêter charge', __FILE__) . ($numberOfConnectors > 1 ? $connector : ''))
            ->setType('action')
            ->setSubType('other')
            // ->setValue($stateCmd->getId())
            // ->setTemplate('dashboard', 'core::binaryDefault')
            // ->setTemplate('mobile', 'core::binaryDefault')
            ->setOrder($order);
          $cmd->save();
        }
        $order++;
      }
    }
  }

  public function chargerInit() {
    if ($this->getIsEnable() == 1) {
      log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Connecté au système central OCPP', __FILE__));
      $this->setStatus('reachable', 1);

      $updateConf = 'MeterValuesSampledData MeterValuesSampledDataMaxLength MeterValueSampleInterval MeterValuesAlignedData MeterValuesAlignedDataMaxLength ClockAlignedDataInterval'; // AuthorizeRemoteTxRequests
      $this->chargerGetConfiguration($updateConf, true);
      $this->save(true);

      $this->chargerUpdateAuthList();
      $this->chargerTriggerMessage('StatusNotification');
    } else {
      log::add(__CLASS__, 'warning', $this->getHumanName() . ' ' . __("Connexion au système central OCPP abandonnée car l'équipement est désactivé", __FILE__));
    }
  }

  public function chargerUnreachable() {
    $this->setStatus('reachable', 0);
    $this->save(true);

    $numberOfConnectors = $this->getConfiguration('NumberOfConnectors', 1);
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

  public function getAuthList(bool $_withDefault = false): array {
    $file = __DIR__ . '/../../data/' . $this->getLogicalId() . '.csv';
    if ($this->getConfiguration('authorize_all_transactions', 0) == 1) {
      if (file_exists($file)) {
        unlink($file);
      }
      return array('default' => 'accepted');
    }
    $return = ($_withDefault) ? array('default' => 'invalid') : array();
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

  private function chargerChangeConfiguration(string $_key, string $_value) {
    $currentConf = $this->chargerGetConfiguration($_key);
    if ($currentConf['value'] == $_value) {
      return;
    }
    if ($currentConf['readonly']) {
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' ' . __('Impossible de modifier cette configuration (lecture seule)', __FILE__) . ' : ' . $_key);
      return;
    }
    $changeConf = $this->sendToCharger(['method' => 'change_configuration', 'args' => [$_key, $_value]]);
    //   case 'RebootRequired':
  }

  private function chargerGetConfiguration(string $_key = null, bool $_record = false): array {
    $chargerConf = $this->sendToCharger(['method' => 'get_configuration', 'args' => [$_key]]);
    if (!empty($chargerConf['unknown_key'])) {
      log::add(__CLASS__, 'warning', $this->getHumanName() . '[' . __FUNCTION__ . '] ' . _('Clé(s) de configuration inconnue(s)', __FILE__) . ' : ' . print_r($chargerConf['unknown_key'], true));
    }
    if (isset($chargerConf['configuration_key'])) {
      if ($_record) {
        foreach ($chargerConf['configuration_key'] as $conf) {
          $this->setConfiguration($conf['key'], $conf['value']);
        }
      }
      if ($_key && count(explode(' ', $_key)) == 1) {
        return $chargerConf['configuration_key'][0];
      }
      return $chargerConf['configuration_key'];
    }
    return $chargerConf;
  }

  public function chargerStartTransaction(int $_connectorId, string $_idTag = null) {
    if (!$_idTag) {
      $_idTag = ($_SESSION['user'] && $_SESSION['user']->getLogin() != '') ? $_SESSION['user']->getLogin() : $this->getLogicalId();
    }
    $this->sendToCharger(['method' => 'start_transaction', 'args' => [$_connectorId, $_idTag]]);
  }

  public function chargerStopTransaction(int $_connectorId) {
    $transactionId = $this->getStatus('transaction_id::' . $_connectorId, false);
    if ($transactionId) {
      $this->sendToCharger(['method' => 'stop_transaction', 'args' => [$transactionId]]);
    }
  }

  public function chargerTriggerMessage(string $_message, int $_connectorId = null): bool {
    if ($_message == 'BootNotification' || $this->chargerHasFeature('RemoteTrigger') && in_array($_message, _MESSAGES)) {
      $trigger = $this->sendToCharger(['method' => 'trigger_message', 'args' => [$_message, $_connectorId]]);
      if (strtolower($trigger['status']) == 'accepted') {
        return true;
      }
    }
    return false;
  }

  private function chargerUpdateAuthList() {
    $authList = $this->getAuthList(true);
    $setAuthList = $this->sendToCharger(['method' => 'set_auth_list', 'args' => [$authList]]);
  }

  public function chargerReset(string $_type = 'Soft'): bool {
    if (in_array($_type, _RESET)) {
      $reset = $this->sendToCharger(['method' => 'reset', 'args' => [$_type]]);
      if (strtolower($reset['status']) == 'accepted') {
        return true;
      }
    }
    return false;
  }

  private function chargerHasFeature(string $_feature): bool {
    $supportedFeatures = array_map('trim', explode(',', $this->getConfiguration('SupportedFeatureProfiles')));
    if (in_array($_feature, $supportedFeatures)) {
      return true;
    }
    return false;
  }

  private function sendToCharger(array $_data): array {
    if ($this->getStatus('reachable') != 1) {
      return array('status' => 'Unreachable');
    }

    log::add(__CLASS__, 'debug', $this->getHumanName() . '[' . __FUNCTION__ . '] : ' . print_r($_data, true));
    $_data['cp_id'] = $this->getLogicalId();
    $_data['apikey'] = jeedom::getApiKey(__CLASS__);
    $data = json_encode($_data);
    $dataLenght = strlen($data);

    $head = "GET /Jeedom HTTP/1.1" . "\r\n" .
      "Upgrade: WebSocket" . "\r\n" .
      "Connection: Upgrade" . "\r\n" .
      "Origin: http://localhost/" . "\r\n" .
      "Host: localhost" . "\r\n" .
      "Sec-WebSocket-Key: TyPfhFqWTjuw8eDAxdY8xg==\r\n" .
      "Sec-WebSocket-Version: 13\r\n" .
      "Content-Length: " . $dataLenght . "\r\n" . "\r\n";
    $sock = fsockopen('localhost', config::byKey('socketport', __CLASS__, '9000'), $errno, $errstr, 2);
    fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);
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
    fread($sock, 200);
    usleep(250);
    $response = fread($sock, 1024);
    while (!preg_match('/{.*}?}/', $response, $match)) {
      $response .= fread($sock, 512);
    }
    fclose($sock);
    log::add(__CLASS__, 'debug', $this->getHumanName() . '[' . $_data['method'] . '] : ' . print_r($match[0], true));
    return json_decode($match[0], true);
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
