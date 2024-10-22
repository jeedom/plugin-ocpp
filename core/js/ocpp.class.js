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

jeedom.ocpp = function() { }


jeedom.ocpp.chargerChangeConfiguration = function(_params) {
	var paramsRequired = ['eqLogicId', 'key', 'value']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics)
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'chargerChangeConfiguration',
		eqLogicId: _params.eqLogicId,
		key: _params.key,
		value: _params.value
	}
	domUtils.ajax(paramsAJAX)
}

/************************* Authorizations list ************************************************/

jeedom.ocpp.authList = function() { }

jeedom.ocpp.authList.set = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics)
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'setAuthList',
		eqLogicId: _params.eqLogicId,
		authList: JSON.stringify(_params.authList)
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.authList.get = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics)
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'getAuthList',
		eqLogicId: _params.eqLogicId
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.authList.download = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics)
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'downloadAuthList',
		eqLogicId: _params.eqLogicId
	}
	domUtils.ajax(paramsAJAX)
}
