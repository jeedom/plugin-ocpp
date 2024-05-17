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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'ocpp')) {
	echo __("Vous n'êtes pas autorisé à effectuer cette action", __FILE__);
	die();
}
if (init('test') != '') {
	echo 'OK';
	die();
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result) || empty($result)) {
	die();
}

$eqLogic = ocpp::byLogicalId($result['cp_id'], 'ocpp');
if (!is_object($eqLogic)) {
	if ($result['event'] == 'connect') {
		log::add('ocpp', 'debug', __('Nouvelle borne détectée', __FILE__) . ' : ' . $result['cp_id'] . '. ' . __('Attente de la notification de démarrage...', __FILE__));
	} else if ($result['event'] == 'boot') {
		log::add('ocpp', 'debug', __('Création de la borne de recharge', __FILE__) . ' : ' . $result['data']['charge_point_model'] . ' ' . $result['cp_id']);
		$eqLogic = (new ocpp)
			->setEqType_name('ocpp')
			->setLogicalId($result['cp_id'])
			->setName($result['data']['charge_point_model'] . ' ' . $result['cp_id'])
			->setCategory('energy', 1)
			->setIsEnable(1)
			->setIsVisible(1)
			->setConfiguration('charge_point_vendor', $result['data']['charge_point_vendor'])
			->setConfiguration('charge_point_model', $result['data']['charge_point_model']);
		if (isset($result['data']['charge_point_serial_number'])) {
			$eqLogic->setConfiguration('charge_point_serial_number', $result['data']['charge_point_serial_number']);
		}
		if (isset($result['data']['firmware_version'])) {
			$eqLogic->setConfiguration('firmware_version', $result['data']['firmware_version']);
		}
		$eqLogic->setStatus('reachable', 1);
		$eqLogic->save();
		$eqLogic->chargerInit();
	} else {
		log::add('ocpp', 'debug', __('Tentative de demande de notification de démarrage pour borne de recharge inconnue', __FILE__) . ' : ' . $result['cp_id']);
		$eqLogic = (new ocpp)
			->setEqType_name('ocpp')
			->setLogicalId($result['cp_id'])
			->setName($result['cp_id']);
		$eqLogic->setStatus('reachable', 1);
		if (!$eqLogic->chargerTriggerMessage('BootNotification')) {
			log::add('ocpp', 'warning', __('Echec de la demande de notification de démarrage, tentative de redémarrage de la borne', __FILE__) . ' : ' . $result['cp_id']);
			if (!$eqLogic->chargerReset()) {
				log::add('ocpp', 'error', __('Echec de la demande de redémarrage de la borne', __FILE__) . ' : ' . $result['cp_id'] . '. ' . __('Un redémarrage manuel est nécessaire !', __FILE__));
			}
		}
	}
} else if ($eqLogic->getIsEnable() == 1 || $result['event'] == 'connect') {
	log::add('ocpp', 'debug', 'jeeOcpp : ' . print_r($result, true));
	switch ($result['event']) {
		case 'connect':
			$eqLogic->chargerInit();
			break;

		case 'boot':
			if (isset($result['data']['firmware_version']) && $result['data']['firmware_version'] != $eqLogic->getConfiguration('firmware_version')) {
				$eqLogic->setConfiguration('firmware_version', $result['data']['firmware_version'])->save(true);
			}
			break;

		case 'status':
			$connectorId = $result['data']['connector_id'];
			$eqLogic->checkAndUpdateCmd('status::' . $connectorId, $result['data']['status']);
			$eqLogic->checkAndUpdateCmd('error::' . $connectorId, $result['data']['error_code'] . ((!empty($errorInfo = trim($result['data']['info']))) ? ' (' . $errorInfo . ')' : ''));
			if (in_array(trim($result['data']['status']), _STATUSES['operative'])) {
				$eqLogic->checkAndUpdateCmd('state::' . $connectorId, 1);
			} else {
				$eqLogic->checkAndUpdateCmd('state::' . $connectorId, 0);
			}
			break;

		case 'meter_values':
			$connectorId = $result['data']['connector_id'];

			foreach ($result['data']['meter_value'] as $meterValue) {
				$valueDate = date('Y-m-d H:i:s', strtotime($meterValue['timestamp']));

				foreach ($meterValue['sampled_value'] as $sampledValue) {
					$logical = $sampledValue['measurand'] . (isset($sampledValue['phase']) ? '::' . $sampledValue['phase'] : '') . '::' . $connectorId;
					$cmd = $eqLogic->getCmd('info', $logical);
					if (!is_object($cmd)) {
						$connector = ($connectorId == 0) ? ' ' . __('borne', __FILE__) : ' ' . __('connecteur', __FILE__);
						if (is_object($eqLogic->getCmd('info', 'status::2')) && $connectorId >= 1) {
							$connector .= ' ' . $connectorId;
						}

						$cmd = (new ocppCmd)
							->setEqLogic_id($eqLogic->getId())
							->setLogicalId($logical)
							->setName(ocpp::measurands($sampledValue['measurand']) . (isset($sampledValue['phase']) ? ' ' . $sampledValue['phase'] : '') . $connector)
							->setType('info')
							->setSubType('numeric')
							->setTemplate('dashboard', 'badge')
							->setTemplate('mobile', 'badge')
							->setDisplay('showStatsOndashboard', 0)
							->setDisplay('showStatsOnmobile', 0)
							// ->setDisplay('graphType', 'column')
							->setUnite($sampledValue['unit'])
							// ->setOrder($cmdsTemplate[$_logicalId]['order'])
							->setIsVisible(1)
							->setIsHistorized(1);
						$cmd->save();
					}
					log::add('ocpp', 'debug', $cmd->getHumanName() . ' ' . __('Mesure reçue', __FILE__) . ' (' . $valueDate . ') ' . print_r($sampledValue, true));
					$eqLogic->checkAndUpdateCmd($logical, $sampledValue['value'], $valueDate);
				}
			}
			break;

		case 'disconnect':
			$eqLogic->chargerUnreachable();
			break;
	}
}
