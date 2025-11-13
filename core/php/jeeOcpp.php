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
		log::add('ocpp', 'debug', __('Nouvelle borne détectée', __FILE__) . ' : ' . $result['cp_id'], __FILE__);
		$eqLogic = (new ocpp)
			->setEqType_name('ocpp')
			->setLogicalId($result['cp_id'])
			->setName('INIT ' . $result['cp_id'])
			->setCategory('energy', 1)
			->setIsEnable(1)
			->setIsVisible(1);
		$eqLogic->save(true);
		$eqLogic->chargerInit();
	}
} else if ($eqLogic->getIsEnable() == 1) {
	switch ($result['event']) {
		case 'connect':
			log::add('ocpp', 'debug', $eqLogic->getHumanName() . ' ' . __('Borne détectée, connexion en cours...', __FILE__));
			$eqLogic->chargerInit();
			break;

		case 'boot':
			log::add('ocpp', 'debug', $eqLogic->getHumanName() . ' ' . __("Notification de démarrage", __FILE__) . ' : ' . print_r($result['data'], true));
			$eqLogic->setStatus('waitingBoot', null);
			if ($result['data']['charge_point_vendor'] != $eqLogic->getConfiguration('charge_point_vendor')) {
				$eqLogic->setConfiguration('charge_point_vendor', $result['data']['charge_point_vendor'])
					->setConfiguration('charge_point_model', $result['data']['charge_point_model']);
				if ($eqLogic->getName() == 'INIT ' . $result['cp_id']) {
					$eqLogic->setName($result['data']['charge_point_model'] . ' ' . $result['cp_id']);
				}
				if (isset($result['data']['charge_point_serial_number'])) {
					$eqLogic->setConfiguration('charge_point_serial_number', $result['data']['charge_point_serial_number']);
				}
				$eqLogic->save();
			}
			if (isset($result['data']['firmware_version']) && $result['data']['firmware_version'] != $eqLogic->getConfiguration('firmware_version')) {
				$eqLogic->setConfiguration('firmware_version', $result['data']['firmware_version'])->save(true);
			}
			break;

		case 'status':
			log::add('ocpp', 'debug', $eqLogic->getHumanName() . ' ' . __('Nouveau statut', __FILE__) . ' : ' . print_r($result['data'], true));
			$status = trim($result['data']['status']);
			if (in_array($status, _STATUSES['operative'])) {
				$eqLogic->checkAndUpdateCmd('state::' . $result['data']['connector_id'], 1);
			} else {
				$eqLogic->checkAndUpdateCmd('state::' . $result['data']['connector_id'], 0);
			}
			$eqLogic->checkAndUpdateCmd('status::' . $result['data']['connector_id'], $status);
			$eqLogic->checkAndUpdateCmd('error::' . $result['data']['connector_id'], $result['data']['error_code']);
			$eqLogic->checkAndUpdateCmd('info::' . $result['data']['connector_id'], (isset($result['data']['info']) && $result['data']['info'] != 'null') ? $result['data']['info'] : '');



			if (in_array($status, ['SuspendedEVSE', 'SuspendedEV'])) {
				$eqLogic->chargerTriggerMessage('MeterValues', $result['data']['connector_id']);
			}
			break;

		case 'authorize':
			$auth = $eqLogic->getAuth($result['data']['id_tag']);
			log::add('ocpp', 'debug', $eqLogic->getHumanName() . ' ' . __("Demande d'autorisation pour", __FILE__) . ' ' . $result['data']['id_tag']) . ' : ' . print_r($auth, true);
			$eqLogic->chargerSendResponse('id_tag_info', $auth);
			break;

		case 'start_transaction':
			$auth = $eqLogic->getAuth($result['data']['id_tag']);
			$eqLogic->chargerSendResponse('id_tag_info', $auth);

			$transaction = ocpp_transaction::byCpIdAndConnectorId($result['cp_id'], $result['data']['connector_id'], true);
			$transactionDate = date('Y-m-d H:i:s', strtotime($result['data']['timestamp']));
			if ((!is_object($transaction) || $transaction->getStart() != $transactionDate) && $auth['status'] == 'Accepted') {
				log::add('ocpp_transaction', 'info', $eqLogic->getHumanName() . ' ' . __('Début charge', __FILE__) . ' : ' . print_r($result['data'], true));

				$eqLogic->checkAndUpdateCmd('idTag::' . $result['data']['connector_id'], $result['data']['id_tag'], $transactionDate);

				$transaction = (new ocpp_transaction)
					->setCpId($result['cp_id'])
					->setConnectorId($result['data']['connector_id'])
					->setTagId($result['data']['id_tag'])
					->setStart($transactionDate)
					->setOptions('meterStart', $result['data']['meter_start']);
				if (isset($result['data']['reservation_id'])) {
					$transaction->setOptions('reservationId', $result['data']['reservation_id']);
				}
				$transaction->save();
				$transaction->executeListener('start_transaction');
			}

			$eqLogic->chargerSendResponse('transaction_id', (is_object($transaction)) ? $transaction->getId() : 0);
			break;

		case 'stop_transaction':
			if (isset($result['data']['id_tag'])) {
				$eqLogic->chargerSendResponse('id_tag_info', $eqLogic->getAuth($result['data']['id_tag']));
			}

			if (is_object($transaction = ocpp_transaction::byId($result['data']['transaction_id']))) {
				if (empty($transaction->getEnd())) {
					log::add('ocpp_transaction', 'info', $eqLogic->getHumanName() . ' ' . __('Fin charge', __FILE__) . ' : ' . print_r($result['data'], true));
					$transactionDate = date('Y-m-d H:i:s', strtotime($result['data']['timestamp']));
					$eqLogic->checkAndUpdateCmd('idTag::' . $transaction->getConnectorId(), __('Aucun', __FILE__), $transactionDate);

					$transaction->setEnd($transactionDate)
						->setOptions('meterStop', $result['data']['meter_stop']);
					if (isset($result['data']['reason'])) {
						$transaction->setOptions('reason', $result['data']['reason']);
					}
					// if (isset($result['data']['transaction_data'])) {
					// 	$transaction->setOptions('transactionData', $result['data']['transaction_data']);
					// }
					$transaction->save();
					$eqLogic->chargerTriggerMessage('MeterValues', $transaction->getConnectorId());
					$transaction->executeListener('stop_transaction');
				}
			} else {
				log::add('ocpp_transaction', 'warning', $eqLogic->getHumanName() . ' ' . __('Transaction non trouvée', __FILE__) . ' : ' . $result['data']['transaction_id']);
			}
			break;

		case 'meter_values':
			$connectorId = $result['data']['connector_id'];
			log::add('ocpp_transaction', 'debug', $eqLogic->getHumanName() . '[' . $connectorId . '] ' . __('Mesure(s) reçue(s)', __FILE__) . ' : ' . print_r($result['data']['meter_value'], true));

			foreach ($result['data']['meter_value'] as $meterValue) {
				$valueDate = date('Y-m-d H:i:s', strtotime($meterValue['timestamp']));
				foreach ($meterValue['sampled_value'] as $sampledValue) {
					if (!isset($sampledValue['measurand']) || !isset($sampledValue['value'])) {
						continue;
					}

					$logical = $sampledValue['measurand'] . (isset($sampledValue['phase']) ? '::' . $sampledValue['phase'] : '') . '::' . $connectorId;
					if (!is_object($eqLogic->getCmd('info', $logical))) {
						$connector = ($connectorId == 0) ? ' ' . __('borne', __FILE__) : ' ' . __('connecteur', __FILE__);
						if (is_object($eqLogic->getCmd('info', 'status::2')) && $connectorId >= 1) {
							$connector .= ' ' . $connectorId;
						}

						if (!isset($sampledValue['unit'])) {
							$sampledValue['unit'] = ($sampledValue['measurand'] == 'Frequency') ? 'Hz' : 'Wh';
						} else {
							$formatUnits = array('Celsius' => '°C', 'Fahrenheit' => '°F', 'K' => '°K', 'Percent' => '%');
							if (in_array($sampledValue['unit'], array_keys($formatUnits))) {
								$sampledValue['unit'] = $formatUnits[$sampledValue['unit']];
							}
						}
						$cmd = (new ocppCmd)
							->setEqLogic_id($eqLogic->getId())
							->setLogicalId($logical)
							->setName(ocppCmd::measurands($sampledValue['measurand']) . (isset($sampledValue['phase']) ? ' ' . $sampledValue['phase'] : '') . $connector)
							->setType('info')
							->setSubType('numeric')
							->setTemplate('dashboard', 'badge')
							->setTemplate('mobile', 'badge')
							->setDisplay('showStatsOndashboard', 0)
							->setDisplay('showStatsOnmobile', 0)
							// ->setDisplay('graphType', 'column')
							->setUnite($sampledValue['unit'])
							->setIsVisible(1)
							->setIsHistorized(1);
						$cmd->save();
					}

					$eqLogic->checkAndUpdateCmd($logical, round($sampledValue['value'], 3), $valueDate);
				}
			}
			break;

		case 'disconnect':
			log::add('ocpp', 'warning', $eqLogic->getHumanName() . ' ' . __('Une erreur est survenue', __FILE__) . ' : ' . $result['error']);
			$eqLogic->setConfiguration('reachable', 0)->save(true);
			$eqLogic->chargerUnreachable();
			break;

		default:
			log::add('ocpp', 'debug', $eqLogic->getHumanName() . ' ' . __('Message non traité', __FILE__) . ' : ' . print_r($result, true));
			break;
	}
}
