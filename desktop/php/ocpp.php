<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('ocpp');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>
<style>
	.dt-table thead th input,
	.dt-table thead th select {
		position: unset;
		top: unset;
		width: unset;
	}
</style>
<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoSecondary" data-action="transactions">
				<i class="fas fa-charging-station"></i>
				<br>
				<span>{{Transactions}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-charging-station"></i> {{Mes bornes de recharge}}</legend>
		<?php
		echo '<div class="input-group" style="margin:5px;">';
		echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
		echo '<div class="input-group-btn">';
		echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
		echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
		echo '</div>';
		echo '</div>';
		echo '<div class="eqLogicThumbnailContainer">';
		foreach ($eqLogics as $eqLogic) {
			$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
			echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
			echo '<img src="' . $eqLogic->getImage() . '"/>';
			echo '<br>';
			echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
			echo '<span class="hiddenAsCard displayTableRight hidden">';
			echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
			echo '</span>';
			echo '</div>';
		}
		echo '</div>';
		// }
		?>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-warning eqLogicAction tooltips" data-action="saveCp" title="{{Enregistrer les paramètres sur la borne}}"><i class="fas fa-save"></i> {{Paramètres borne}}
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i><span class="hidden-xs"> {{Equipement}}</span></a></li>
			<li role="presentation"><a href="#authtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-shield-alt"></i><span class="hidden-xs"> {{Autorisations}}</span></a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i><span class="hidden-xs"> {{Commandes}}</span></a></li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Identifiant}}</label>
								<div class="col-sm-6">
									<div class="label label-info">
										<span class="eqLogicAttr" data-l1key="logicalId"></span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Modèle}}</label>
								<div class="col-sm-6">
									<div class="label label-info">
										<span class="eqLogicAttr" data-l1key="configuration" data-l2key="charge_point_vendor"></span>
										<span class="eqLogicAttr" data-l1key="configuration" data-l2key="charge_point_model"></span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Numéro de série}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="configuration" data-l2key="charge_point_serial_number"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Firmware}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-info" data-l1key="configuration" data-l2key="firmware_version"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"></label>
								<div class="col-sm-6">
									<a class="btn btn-primary eqLogicAction" data-action="transactions"><i class="fas fa-charging-station"></i> {{Liste des transactions}}</a>
								</div>
							</div>
						</div>

						<div class="col-lg-12"></div>

						<div class="col-lg-6">
							<legend><i class="fas fa-list-alt"></i> {{Paramètres OCPP}}</legend>
							<div class="col-xs-12">
								<div class="alert alert-warning text-center col-sm-10 col-sm-offset-1">
									<i class="fas fa-exclamation-triangle"></i> {{Toute modification erronée est susceptible d'entrainer des dysfonctionnements}}
								</div>
								<button class="btn btn-sm btn-default pull-right toggleReadonly tooltips" data-visible="0" title="{{Afficher les champs en lecture seule}}"><i class="fas fa-eye"></i></button>
							</div>
							<div id="ocppConfigKey"></div>
						</div>

						<div class="col-lg-6">
							<legend><i class="far fa-list-alt"></i> {{Paramètres fabricant}}</legend>
							<div class="col-xs-12">
								<div class="alert alert-warning text-center col-sm-10 col-sm-offset-1">
									<i class="fas fa-exclamation-triangle"></i> {{Toute modification erronée est susceptible d'entrainer des dysfonctionnements}}
								</div>
								<button class="btn btn-sm btn-default pull-right toggleReadonly tooltips" data-visible="0" title="{{Afficher les champs en lecture seule}}"><i class="fas fa-eye"></i></button>
							</div>
							<div id="cpConfigKey"></div>
						</div>
					</fieldset>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane" id="authtab">
				<div class="table-responsive" id="authorizations_div">
					<div class="input-group pull-right" style="display:inline-flex">
						<a class="btn btn-success btn-sm roundedLeft authAction" data-action="add"><i class="fas fa-plus-circle"></i> {{Ajouter}}</a>
						<a class="btn btn-primary btn-sm authAction" data-action="downloadCSV"><i class="fas fa-file-download"></i> {{Télécharger}}</a>
						<span class="btn btn-warning btn-sm btn-file roundedRight" title="{{Envoyer un fichier CSV}}"><i class="fas fa-file-upload"></i> {{Envoyer}}
							<input id="uploadCsvFile" type="file" name="file" accept=".csv">
						</span>
					</div>
					<table class="table table-condensed" id="table_auth">
						<thead>
							<tr>
								<th data-type="input">{{Identifiant}}</th>
								<th data-type="select-text">{{Statut}}</th>
								<!-- <th>{{Groupe}}</th> -->
								<th data-sortable="false">{{Date d'expiration}}</th>
								<th data-sortable="false" style="min-width:50px;width:100px;"></th>
							</tr>

							<template>
								<th style="padding-top:unset;"><input type="text" class="input-sm form-control authSearch dt-input" placeholder="{{Rechercher}}"></th>
								<th style="padding-top:unset;">
									<select class="input-sm form-control authSearch dt-input">
										<option value="">{{Tous}}</option>
										<option value="accepted">{{Autorisé}}</option>
										<option value="blocked">{{Bloqué}}</option>
										<option value="expired">{{Expiré}}</option>
										<option value="invalid">{{Invalide}}</option>
									</select>
								</th>
								<th style="padding-top:unset;"><input type="text" class="input-sm form-control authSearch dt-input" placeholder="{{Rechercher}}"></th>
								<th style="padding-top:unset;"></th>
							</template>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>
</div>
<?php include_file('core', 'plugin.template', 'js'); ?>
<?php include_file('core', 'ocpp', 'class.js', 'ocpp'); ?>
<?php include_file('desktop', 'ocpp', 'js', 'ocpp'); ?>
