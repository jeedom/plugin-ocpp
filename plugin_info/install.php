<?php
/* Jeedom is free software: you can redistribute it and/or modify
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
* along with Jeedom. If not, see
<http: //www.gnu.org/licenses />.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function ocpp_install() {
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

	if (config::byKey('mbState') == 1) {
		rename(__DIR__ . '/ocpp_icon.png', __DIR__ . '/ocpp_icon_default.png');
		rename(__DIR__ . '/ocpp_icon_alternate.png', __DIR__ . '/ocpp_icon.png');
	}
}

function ocpp_update() {
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

	foreach ((ocpp::byType('ocpp', true)) as $eqLogic) {
		$eqLogic->createCmds();
	}

	if (config::byKey('mbState') == 1) {
		rename(__DIR__ . '/ocpp_icon.png', __DIR__ . '/ocpp_icon_default.png');
		rename(__DIR__ . '/ocpp_icon_alternate.png', __DIR__ . '/ocpp_icon.png');
	}
}
