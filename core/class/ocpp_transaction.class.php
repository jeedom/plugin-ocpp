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

require_once __DIR__ . '/../../../../core/php/core.inc.php';

class ocpp_transaction {

  private $id;
  private $cpId;
  private $connectorId;
  private $tagId;
  private $start;
  private $end;
  private $options;
  private bool $_changed = false;

  public static function getTranslatedEndReason(string $_reason): string {
    $translations = array(
      'auto-closed' => __("Transaction fermée automatiquement (notification de fin non reçue)", __FILE__),
      'deauthorized' => __('Transaction non autorisée', __FILE__),
      'emergencystop' => __("Arrêt d'urgence", __FILE__),
      'evdisconnected' => __('Véhicule débranché', __FILE__),
      'hardreset' => __('Redémarrage matériel de la borne', __FILE__),
      'local' => __('Fin de transaction (locale)', __FILE__),
      'other' => __('Autre raison', __FILE__),
      'powerloss' => __('Panne de courant', __FILE__),
      'reboot' => __('Redémarrage de la borne', __FILE__),
      'remote' => __('Fin de transaction (à distance)', __FILE__),
      'softreset' => __('Redémarrage logiciel de la borne', __FILE__),
      'unlockcommand' => __('Déverrouillage du connecteur', __FILE__)
    );
    if (isset($translations[strtolower($_reason)])) {
      return $translations[strtolower($_reason)];
    }
    return $_reason;
  }

  public static function all() {
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' ORDER BY id DESC';
    return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byId(int $_id) {
    $values = array('id' => $_id);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE id=:id';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byCpId(string $_cpId) {
    $values = array('cpId' => $_cpId);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE cpId=:cpId ORDER BY id DESC';
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
    $sql .= ' ORDER BY id DESC';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function byTagId(string $_tagId, bool $_inProgress = false) {
    $values = array('tagId' => $_tagId);
    $sql = 'SELECT ' . DB::buildField(__CLASS__) . ' FROM ' . __CLASS__ . ' WHERE tagId=:tagId';
    if ($_inProgress) {
      $sql .= ' AND end IS NULL';
      return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
    }
    $sql .= ' ORDER BY id DESC';
    return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
  }

  public static function start(array $_transactionData): self {
    $transaction = (new self)
      ->setCpId($_transactionData['cp_id'])
      ->setConnectorId($_transactionData['data']['connector_id'])
      ->setTagId($_transactionData['data']['id_tag'])
      ->setStart(date('Y-m-d H:i:s', strtotime($_transactionData['data']['timestamp'])))
      ->setOptions('meterStart', $_transactionData['data']['meter_start']);
    if (isset($_transactionData['data']['reservation_id'])) {
      $transaction->setOptions('reservationId', $_transactionData['data']['reservation_id']);
    }
    $transaction->save();
    event::add(__CLASS__ . '::update', array(
      'transactionId' => $transaction->getId(),
      'tagId' => $transaction->getTagId(),
      'cpId' => $transaction->getCpId(),
      'cells' => $transaction->renderCells(),
      'rawDuration' => ''
    ));
    $transaction->executeListener('start_transaction');
    return $transaction;
  }

  public function stop(string $_date, int $_meterStop, string $_reason = 'Local', array $_transactionData = null) {
    $this->setEnd($_date)
      ->setOptions('meterStop', $_meterStop)
      ->setOptions('reason', $_reason);
    // if (isset($_transactionData)) {
    //   $this->setOptions('transactionData', $_transactionData);
    // }
    $this->save();
    event::add(__CLASS__ . '::update', array(
      'transactionId' => $this->getId(),
      'tagId' => $this->getTagId(),
      'cpId' => $this->getCpId(),
      'cells' => $this->renderCells(),
      'rawDuration' => $this->getDuration()
    ));
    $this->executeListener('stop_transaction');
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
    foreach ($listeners as $listener) {
      $event = trim($listener->getEvent()[0], '#');
      if ($event ==  '*') {
        continue;
      }
      $datetime = ($_phase == 'start_transaction') ? $this->getStart() : $this->getEnd();
      $listener->execute($event, $_phase, $datetime, $this->getId());
    }
  }

  public function getConsumption(): int {
    $conso = (int) $this->getOptions('meterStop') - (int) $this->getOptions('meterStart');
    if ($conso <= 0) {
      return 0;
    }
    return $conso;
  }

  public function getDuration(bool $_convert = false) {
    $duration = strtotime($this->getEnd()) - strtotime($this->getStart());
    if ($duration < 0) {
      return;
    }
    if ($_convert) {
      return convertDuration($duration);
    }
    return $duration;
  }

  public function renderCells(): array {
    $cpId = $this->getCpId();
    $userId = $this->getTagId();

    $chargePoint = ocpp::byLogicalId($cpId, 'ocpp');
    if (is_object($chargePoint)) {
      $name = $chargePoint->getName();

      $auths = array_change_key_case($chargePoint::getAuthGroup($chargePoint->getConfiguration('authGroupId')), CASE_UPPER);
      $upperUserId = strtoupper($userId);
      if (isset($auths[$upperUserId]['name']) && !empty($auths[$upperUserId]['name'])) {
        $userId = $auths[$upperUserId]['name'];
      }
    } else {
      $name = '' . __('Borne', __FILE__) . ' ' . $cpId;
    }

    $endDate = $this->getEnd();
    if (!empty($endDate)) {
      $reason = $this->getOptions('reason', 'Local');
      if ($reason === 'auto-closed') {
        $end = $endDate . ' <sup><i class="fas fa-exclamation-triangle warning" title="' . ocpp_transaction::getTranslatedEndReason($reason) . '"></i></sup>';
      } else {
        $end = $endDate . ' <sup><i class="fas fa-question-circle" title="' . htmlspecialchars(ocpp_transaction::getTranslatedEndReason($reason)) . '"></i></sup>';
      }
    } else {
      $openSince = time() - strtotime($this->getStart());
      if ($openSince > 48 * 3600) {
        $end = '<i class="fas fa-exclamation-circle danger" title="' . __('Transaction probablement abandonnée (ouverte depuis plus de 48h)', __FILE__) . '" style="cursor:pointer!important;"></i>';
      } elseif ($openSince > 24 * 3600) {
        $end = '<i class="fas fa-charging-station warning" title="' . __('Transaction ouverte depuis plus de 24h', __FILE__) . '" style="cursor:pointer!important;"></i>';
      } else {
        $end = '<i class="fas fa-charging-station success" title="' . __('Transaction en cours', __FILE__) . '" style="cursor:pointer!important;"></i>';
      }
    }

    $cells = array(
      'transactionId' => $this->getId(),
      'name' => htmlspecialchars($name),
      'userId' => htmlspecialchars($userId),
      'start' => $this->getStart(),
      'end' => $end,
      'duration' => $this->getDuration(true) ?? ' - ',
      'consumption' => (($consumption = $this->getConsumption()) === 0) && empty($endDate) ? ' - ' : $consumption,
      'connectorId' => $this->getConnectorId(),
      'remove' => '<a class="btn btn-danger btn-xs transAction" data-action="remove" title="' . __('Supprimer', __FILE__) . '"><i class="fas fa-trash-alt"></i></a>'
    );

    return $cells;
  }

  public function renderHtml(): string {
    $cells = $this->renderCells();

    $html = '<tr data-id="' . $cells['transactionId'] . '">';
    $html .= '<td>' . $cells['transactionId'] . '</td>';
    $html .= '<td>' . $cells['name'] . '</td>';
    $html .= '<td>' . $cells['userId'] . '</td>';
    $html .= '<td>' . $cells['start'] . '</td>';
    $html .= '<td>' . $cells['end'] . '</td>';
    $html .= '<td data-sorton="' . $this->getDuration() . '">' . $cells['duration'] . '</td>';
    $html .= '<td>' . $cells['consumption'] . '</td>';
    $html .= '<td>' . $cells['connectorId'] . '</td>';
    $html .= '<td>' . $cells['remove'] . '</td>';
    $html .= '</tr>';
    return $html;
  }

  public function setId($_id) {
    $this->_changed = utils::attrChanged($this->_changed, $this->id, $_id);
    $this->id = $_id;
    return $this;
  }

  public function getId() {
    return (int) $this->id;
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
