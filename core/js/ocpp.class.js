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

jeedom.ocpp.getConfiguration = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'getConfiguration',
		eqLogicId: _params.eqLogicId
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.getConfigurationChanges = function(_params) {
	var paramsRequired = ['eqLogicId', 'config']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'getConfigurationChanges',
		eqLogicId: _params.eqLogicId,
		config: JSON.stringify(_params.config)
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.changeConfiguration = function(_params) {
	var paramsRequired = ['eqLogicId', 'key', 'value']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'changeConfiguration',
		eqLogicId: _params.eqLogicId,
		key: _params.key,
		value: _params.value
	}
	domUtils.ajax(paramsAJAX)
}

/************************* Authorizations ************************************************/

jeedom.ocpp.setAuth = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'setAuth',
		eqLogicId: _params.eqLogicId,
		authList: JSON.stringify(_params.authList)
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.getAuth = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'getAuth',
		eqLogicId: _params.eqLogicId
	}
	domUtils.ajax(paramsAJAX)
}

jeedom.ocpp.downloadAuthlist = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(paramsRequired)
	} catch (e) {
		(paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = domUtils.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'downloadAuthList',
		eqLogicId: _params.eqLogicId
	}
	domUtils.ajax(paramsAJAX)
}
