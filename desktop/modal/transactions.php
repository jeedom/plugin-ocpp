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

if (($tagId = init('tagId')) != '') {
	$transactions = ocpp_transaction::byTagId($tagId);
	$context = 'data-tag_id="' . $tagId . '"';
} else if (($cpId = init('cpId')) != '') {
	$transactions = ocpp_transaction::byCpId($cpId);
	$context = 'data-cp_id="' . $cpId . '"';
} else {
	$transactions = ocpp_transaction::all();
	$context = '';
}

if (empty($transactions)) {
	echo '<div class="alert alert-info">{{Aucune transaction trouvée}}</div>';
	return;
}
?>

<div id="md_ocppTransactions" data-modalType="md_ocppTransactions">
	<table class="table table-condensed stickyHead" id="table_transactions" <?= $context ?>>
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
				echo $transaction->renderHtml();
			}
			?>
		</tbody>
	</table>
</div>

<?php include_file('desktop', 'transactions', 'js', 'ocpp'); ?>
