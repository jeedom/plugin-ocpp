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
$authGroups = (array) config::byKey('authGroups', 'ocpp', array());
sendVarToJS('_authGroups', $authGroups);
?>

<style>
	li.selected {
		font-weight: bold;
		background-color: rgb(var(--defaultBkg-color));
	}

	.dt-table thead th input,
	.dt-table thead th select {
		position: unset;
		top: unset;
		width: unset;
	}
</style>

<div style="display:flex;height:100%;">
	<div class="panel panel-default" style="width:250px;">
		<div class="panel-heading text-center">
			<div class="panel-title">
				<i class="fas fa-shield-alt"></i> {{Groupes d'autorisations}}
			</div>
		</div>
		<div class="panel-body">
			<div class="text-center">
				<a class="btn btn-sm btn-primary authAction" data-action="addGroup"><i class="fas fa-plus-square"></i> {{Ajouter un groupe}}</a>
			</div>
			<ul class="nav" id="auth_groups_menu">
			</ul>
		</div>
	</div>

	<div class="table-responsive" id="authorizations_div" style="flex-grow:1;margin-left:10px;">
		<div class="input-group pull-right hidden" style="display:inline-flex">
			<a class="btn btn-xs roundedLeft authAction" data-action="add" title="{{Ajouter une autorisation}}"><i class="fas fa-plus-circle"></i> {{Ajouter}}</a>
			<a class="btn btn-info btn-xs authAction" data-action="downloadCSV" title="{{Télécharger les autorisations du groupe (CSV)}}"><i class="fas fa-file-download"></i> {{Télécharger}}</a>
			<span class="btn btn-warning btn-xs btn-file roundedRight" title="{{Envoyer les autorisations du groupe (CSV)}}"><i class="fas fa-file-upload"></i> {{Envoyer}}
				<input id="uploadAuthList" type="file" name="file" accept=".csv">
			</span>
		</div>

		<template id="table_auth_template">
			<thead>
				<tr>
					<th data-type="input">{{Identifiant}}
						<sup><i class="fas fa-question-circle" title="{{Identifiant de l'utilisateur}}"></i></sup>
					</th>
					<th data-type="input">{{Nom}}
						<sup><i class="fas fa-question-circle" title="{{Nom de l'utilisateur (facultatif)}}"></i></sup>
					</th>
					<th data-type="select-text">{{Statut}}
						<sup><i class="fas fa-question-circle" title="{{Statut de l'autorisation}}"></i></sup>
					</th>
					<th data-sortable="false">{{Date d'expiration}}
						<sup><i class="fas fa-question-circle" title="{{Date d'expiration de l'autorisation (facultatif)}}"></i></sup>
					</th>
					<th data-sortable="false">{{Transactions concurrentes}}
						<sup><i class="fas fa-question-circle" title="{{Autoriser plusieurs transactions simultanées}}"></i></sup>
					</th>
					<th data-sortable="false" style="min-width:50px;width:100px;"></th>
				</tr>

				<template>
					<th style="padding-top:unset;">
						<input type="text" class="input-sm authSearch dt-input" placeholder="{{Rechercher}}">
					</th>
					<th style="padding-top:unset;">
						<input type="text" class="input-sm authSearch dt-input" placeholder="{{Rechercher}}">
					</th>
					<th style="padding-top:unset;">
						<select class="input-sm authSearch dt-input">
							<option value="">{{Tous}}</option>
							<option value="accepted">{{Autorisé}}</option>
							<option value="blocked">{{Bloqué}}</option>
							<option value="expired">{{Expiré}}</option>
							<option value="invalid">{{Invalide}}</option>
						</select>
					</th>
					<th style="padding-top:unset;">
						<input type="text" class="input-sm authSearch dt-input" placeholder="{{Rechercher}}">
					</th>
					<th style="padding-top:unset;"></th>
					<th style="padding-top:unset;"></th>
				</template>
			</thead>
			<tbody>
			</tbody>
		</template>
	</div>
</div>

<?php include_file('desktop', 'authorisations', 'js', 'ocpp'); ?>
