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

try {
  require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
  include_file('core', 'authentification', 'php');

  if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
  }

  ajax::init(['uploadCsvFile']);

  if (init('action') == 'setMeasurands') {
    $eqLogic = ocpp::byId(init('eqLogicId'));
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable (ID)', __FILE__) . ' : ' . init('eqLogicId'));
    }

    ajax::success();
  }

  if (init('action') == 'setAuthList') {
    $eqLogic = ocpp::byId(init('eqLogicId'));
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable (ID)', __FILE__) . ' : ' . init('eqLogicId'));
    }
    ajax::success($eqLogic->setAuthList(json_decode(init('authList', array()), true)));
  }

  if (init('action') == 'getAuthList') {
    $eqLogic = ocpp::byId(init('eqLogicId'));
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable (ID)', __FILE__) . ' : ' . init('eqLogicId'));
    }
    ajax::success($eqLogic->getAuthList());
  }

  if (init('action') == 'downloadAuthList') {
    $eqLogic = ocpp::byId(init('eqLogicId'));
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable (ID)', __FILE__) . ' : ' . init('eqLogicId'));
    }
    $file = __DIR__ . '/../../data/' . $eqLogic->getLogicalId() . '.csv';
    if (!is_file($file)) {
      $csv = fopen($file, 'w');;
      fputcsv($csv, ['id', 'status', 'expiry_date'], ';');
      fclose($csv);
    }
    ajax::success(realpath($file));
  }

  if (init('action') == 'uploadCsvFile') {
    if (!isConnect('admin')) {
      throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    if (!isset($_FILES['file'])) {
      throw new Exception(__('Aucun fichier trouvé. Vérifiez le paramètre PHP (post size limit)', __FILE__));
    }
    $eqLogic = ocpp::byId(init('eqLogicId'));
    if (!is_object($eqLogic)) {
      throw new Exception(__('Equipement introuvable (ID)', __FILE__) . ' : ' . init('eqLogicId'));
    }

    $extension = strtolower(strrchr($_FILES['file']['name'], '.'));
    if ($extension != '.csv') {
      throw new Exception(__('Extension du fichier non valide (autorisé .csv)', __FILE__) . ' : ' . $extension);
    }
    if (filesize($_FILES['file']['tmp_name']) > 5000000) {
      throw new Exception(__('Le fichier est trop volumineux (maximum 5Mo)', __FILE__));
    }
    $uploaddir = realpath(__DIR__ . '/../../data');
    if (!is_dir($uploaddir)) {
      mkdir($uploaddir, 0775);
    }

    $filepath = $uploaddir . '/' . $eqLogic->getLogicalId() . '.csv';
    if (file_exists($filepath)) {
      @unlink($filepath);
    }
    file_put_contents($filepath, file_get_contents($_FILES['file']['tmp_name']));
    if (!file_exists($filepath)) {
      throw new Exception(__('Impossible de sauvegarder le fichier', __FILE__));
    }
    $eqLogic->chargerSendAuthList();
    ajax::success($filepath);
  }

  throw new Exception(__('Aucune méthode correspondante', __FILE__) . ' : ' . init('action'));
} catch (Exception $e) {
  ajax::error(displayException($e), $e->getCode());
}
