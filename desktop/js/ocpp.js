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
var measurandChanges = authChanges = false

document.getElementById('div_pageContainer').addEventListener('click', function(event) {
  var _target = null
  if (_target = event.target.closest('.authAction[data-action="add"]')) {
    addAuth()
    modifyWithoutSave = authChanges = true
    return
  }

  if (_target = event.target.closest('.authAction[data-action="downloadCSV"]')) {
    jeedom.ocpp.authList.download({
      eqLogicId: getUrlVars('id'),
      error: function(error) {
        $.fn.showAlert({ message: error.message, level: 'danger' })
      },
      success: function(data) {
        window.open('core/php/downloadFile.php?pathfile=' + data)
      }
    })
    return
  }

  if (_target = event.target.closest('.authAction[data-action="remove"]')) {
    _target.closest('tr').remove()
    modifyWithoutSave = authChanges = true
    return
  }
})

document.getElementById('div_pageContainer').addEventListener('change', function(event) {
  var _target = null
  if (_target = event.target.closest('.measurandAttr')) {
    console.log(event)
    modifyWithoutSave = measurandChanges = true
    return
  }

  if (_target = event.target.closest('.authAttr')) {
    modifyWithoutSave = authChanges = true
    return
  }
})

$('#uploadCsvFile').fileupload({
  replaceFileInput: false,
  url: 'plugins/ocpp/core/ajax/ocpp.ajax.php?action=uploadCsvFile&eqLogicId=' + getUrlVars('id'),
  dataType: 'json',
  done: function(e, data) {
    if (data.result.state != 'ok') {
      $.fn.showAlert({
        message: data.result.result,
        level: 'danger'
      })
      return
    }
    window.location.reload()
  }
})

function printEqLogic(_eqLogic) {
  // if (_eqLogic.display.remoteTx) {
  //   document.querySelector('.eqLogicAttr[data-l2key="AuthorizeRemoteTxRequests"]').disabled = false
  // } else {
  //   document.querySelector('.eqLogicAttr[data-l2key="AuthorizeRemoteTxRequests"]').disabled = true
  // }

  document.querySelectorAll('input.measurandAttr').forEach(_measureInput => {
    _measureInput.checked = false
  })
  let meterValues = ['MeterValuesSampledData', 'MeterValuesAlignedData']
  var phases = ['L1', 'L2', 'L3', 'N', 'L1-N', 'L2-N', 'L3-N', 'L1-L2', 'L2-L3', 'L3-L1']
  meterValues.forEach(_meterValue => {
    if (isset(_eqLogic.configuration[_meterValue])) {
      let measurands = _eqLogic.configuration[_meterValue].split(',').map(item => item.trim())
      measurands.forEach(_measurand => {
        let phase = false
        let measurandSplit = _measurand.split('.')
        if (phases.includes(measurandSplit[measurandSplit.length - 1])) {
          phase = measurandSplit[measurandSplit.length - 1]
          measurandSplit.pop()
          _measurand = measurandSplit.join('.')
          if (_measurand == 'Current') {
            _measurand = 'Current.Import'
          }
        }
        if (document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"]')) {
          document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"]').checked = true
          if (phase) {
            document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"][data-l3key="' + phase + '"]').checked = true
          }
        }
      })
    }
  })

  document.getElementById('table_auth').querySelector('tbody').innerHTML = ''
  jeedom.ocpp.authList.get({
    eqLogicId: _eqLogic.id,
    error: function(error) {
      $.fn.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      for (id in data) {
        auth = data[id]
        auth.id = id
        addAuth(auth)
      }
    }
  })
}

function saveEqLogic(_eqLogic) {
  if (measurandChanges) {
    console.log(jQuery(document.getElementById('measurandstab')).getValues('.measurandAttr')[0])
    // jeedom.ocpp.measurands.set({
    //   eqLogicId: _eqLogic.id,
    //   measurands: jQuery(document.getElementById('measurandstab')).getValues('.measurandAttr'), // 4.4 mini => document.getElementById('measurandstab').getJeeValues('.measurandAttr')
    //   error: function(error) {
    //     $.fn.showAlert({ message: error.message, level: 'danger' })
    //   }
    // })
  }

  if (authChanges) {
    let authLines = document.getElementById('table_auth').querySelectorAll('tbody > tr')
    // console.log(jQuery(authLines)?.getValues('.authAttr'))
    jeedom.ocpp.authList.set({
      eqLogicId: _eqLogic.id,
      authList: jQuery(authLines)?.getValues('.authAttr'), // 4.4 mini => authLines.getJeeValues('.authAttr')
      error: function(error) {
        $.fn.showAlert({ message: error.message, level: 'danger' })
      }
    })
  }
  return _eqLogic
}

$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true }) // 4.4 mini => useless

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  let tr = '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn">'
  tr += '<a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a>'
  tr += '</span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked>{{Historiser}}</label> '
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;margin-top:7px;">'
  tr += '</td>'
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>'
  tr += '</td>'

  let newRow = document.createElement('tr')
  newRow.innerHTML = tr
  newRow.classList = 'cmd'
  newRow.setAttribute('data-cmd_id', init(_cmd.id))
  document.getElementById('table_cmd').querySelector('tbody').appendChild(newRow)
  jQuery(newRow).setValues(_cmd, '.cmdAttr') // 4.4 mini => newRow.setJeeValues(_cmd, '.cmdAttr')
  jeedom.cmd.changeType(jQuery(newRow), init(_cmd.subType)) // 4.4 mini => jeedom.cmd.changeType(newRow, init(_cmd.subType))
}

function addAuth(_auth = null) {
  let tr = '<td>'
  tr += '<input class="authAttr form-control" data-l1key="id">'
  tr += '</td>'
  tr += '<td>'
  tr += '<select class="authAttr form-control" data-l1key="status">'
  tr += '<option value="accepted">{{Autorisé}}</option>'
  tr += '<option value="blocked">{{Bloqué}}</option>'
  tr += '<option value="expired">{{Expiré}}</option>'
  tr += '<option value="invalid">{{Invalide}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<input type="datetime-local" class="authAttr form-control" data-l1key="expiry_date">'
  tr += '</td>'
  tr += '<td>'
  tr += '<a class="btn btn-danger btn-xs authAction" data-action="remove"><i class="fas fa-trash-alt"></i><span class="hidden-xs"> {{Supprimer}}</span></a>'
  tr += '</td>'

  let newRow = document.createElement('tr')
  newRow.innerHTML = tr
  document.getElementById('table_auth').querySelector('tbody').appendChild(newRow)
  jQuery(newRow).setValues(_auth, '.authAttr') // 4.4 mini => newRow.setJeeValues(_auth, '.authAttr')
}
