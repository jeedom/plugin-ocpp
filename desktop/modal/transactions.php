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
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

if (init('tagId') != '') {
	$transactions = ocpp_transaction::byTagId(init('tagId'));
} else if (init('cpId') != '') {
	$transactions = ocpp_transaction::byCpId(init('cpId'));
} else {
	$transactions = ocpp_transaction::all();
}

if (empty($transactions)) {
	echo '<div class="alert alert-info">{{Aucune transaction trouvée}}</div>';
	return;
}
?>

<div id="md_ocppTransactions" data-modalType="md_ocppTransactions">
	<table class="table table-condensed stickyHead" id="table_transactions">
		<thead>
			<tr>
				<th>{{ID}}</th>
				<th>{{Equipement}}</th>
				<th>{{Utilisateur}}</th>
				<th>{{Début}}</th>
				<th>{{Fin}}</th>
				<th data-type="custom">{{Durée}}</th>
				<th>{{Consommation (Wh)}}</th>
				<th>{{Connecteur}}</th>
				<th data-sortable="false"></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($transactions as $transaction) {
				$cpId = $transaction->getCpId();
				$userId = $transaction->getTagId();

				$chargePoint = ocpp::byLogicalId($cpId, 'ocpp');
				if (is_object($chargePoint)) {
					$name = $chargePoint->getName();

					$auths = $chargePoint::getAuthGroup($chargePoint->getConfiguration('authGroupId'));
					if (isset($auths[$userId]['name']) && !empty($auths[$userId]['name'])) {
						$userId = $auths[$userId]['name'];
					}
				} else {
					$name = '{{Borne}} ' . $cpId;
				}
			?>
				<tr data-id="<?= $transaction->getId() ?>">
					<td><?= $transaction->getId() ?></td>
					<td><?= $name ?></td>
					<td><?= $userId ?></td>
					<td><?= $transaction->getStart() ?></td>
					<td><?= $transaction->getEnd() ?></td>
					<td data-sorton="<?= $transaction->getDuration() ?>"><?= $transaction->getDuration(true) ?></td>
					<td><?= $transaction->getConsumption() ?></td>
					<td><?= $transaction->getConnectorId() ?></td>
					<td><a class="btn btn-danger btn-xs transAction" data-action="remove" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>

<?php include_file('desktop', 'transactions', 'js', 'ocpp'); ?>
