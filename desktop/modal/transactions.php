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
				<th>{{Connecteur}}</th>
				<th>{{Utilisateur}}</th>
				<th>{{Consommation (Wh)}}</th>
				<th data-type="custom">{{Durée}}</th>
				<th>{{Début}}</th>
				<th>{{Fin}}</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($transactions as $transaction) {
				$chargePoint = ocpp::byLogicalId($transaction->getCpId(), 'ocpp');
			?>
				<tr>
					<td><?= $transaction->getTransactionId() ?></td>
					<td><?= (is_object($chargePoint)) ? $chargePoint->getName() : '{{Borne}} ' . $transaction->getCpId() ?></td>
					<td><?= $transaction->getConnectorId() ?></td>
					<td><?= $transaction->getTagId() ?></td>
					<td><?= $transaction->getConsumption() ?></td>
					<td data-sorton="<?= $transaction->getDuration() ?>"><?= $transaction->getDuration(true) ?></td>
					<td><?= $transaction->getStart() ?></td>
					<td><?= $transaction->getEnd() ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>

<script>
	(function() {
		let transactionsTable = document.getElementById('table_transactions')
		if (transactionsTable._dataTable) {
			transactionsTable._dataTable.destroy()
		}
		new DataTable(transactionsTable, {
			perPage: 25,
			perPageSelect: [10, 25, 50, 100],
			searchable: false,
			layout: {
				top: "{select}",
				bottom: "{pager}"
			}
		})
	})()
</script>
