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

/************************* Measurands ************************************************/

jeedom.ocpp.measurands = function() { }

jeedom.ocpp.measurands.set = function(_params) {
	var paramsRequired = ['eqLogicId', 'measurands']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
	} catch (e) {
		(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'setMeasurands',
		eqLogicId: _params.eqLogicId,
		measurands: _params.measurands
	}
	$.ajax(paramsAJAX) // 4.4 mini => domUtils.ajax
}

/************************* Authorizations list ************************************************/

jeedom.ocpp.authList = function() { }

jeedom.ocpp.authList.set = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
	} catch (e) {
		(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'setAuthList',
		eqLogicId: _params.eqLogicId,
		authList: _params.authList
	}
	$.ajax(paramsAJAX) // 4.4 mini => domUtils.ajax
}

jeedom.ocpp.authList.get = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
	} catch (e) {
		(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'getAuthList',
		eqLogicId: _params.eqLogicId
	}
	$.ajax(paramsAJAX) // 4.4 mini => domUtils.ajax
}

jeedom.ocpp.authList.download = function(_params) {
	var paramsRequired = ['eqLogicId']
	var paramsSpecifics = {}
	try {
		jeedom.private.checkParamsRequired(_params || {}, paramsRequired)
	} catch (e) {
		(_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e)
		return
	}
	var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {})
	var paramsAJAX = jeedom.private.getParamsAJAX(params)
	paramsAJAX.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php'
	paramsAJAX.data = {
		action: 'downloadAuthList',
		eqLogicId: _params.eqLogicId
	}
	$.ajax(paramsAJAX) // 4.4 mini => domUtils.ajax
}
