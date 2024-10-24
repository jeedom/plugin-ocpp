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
var /* measurandChanges = */ authChanges = false

document.getElementById('div_pageContainer').addEventListener('click', function(event) {
  var _target = null
  if (_target = event.target.closest('.eqLogicAction[data-action="transactions"]')) {
    let cpId = (getUrlVars('id')) ? document.querySelector('.eqLogicAttr[data-l1key="logicalId"]').value : null
    let title = (cpId) ? '{{Transactions de}} ' + document.querySelector('.eqLogicAttr[data-l1key="name"]').value : '{{Toutes les transactions}}'
    jeeDialog.dialog({
      id: 'jee_modal',
      title: title,
      contentUrl: 'index.php?v=d&plugin=ocpp&modal=transactions' + ((cpId) ? '&cpId=' + cpId : '')
    })
    return
  }

  if (_target = event.target.closest('.authAction[data-action="add"]')) {
    let authDataTable = document.getElementById('table_auth')._dataTable
    if (!authDataTable || authDataTable.table.rows.length == 0) {
      authDataTable = initAuthDatatable()
    }
    authDataTable.rows().add(addAuth())
    jeedomUtils.datePickerInit('Y-m-d H:i', '.authAttr[data-l1key="expiry_date"]')
    modifyWithoutSave = authChanges = true
    return
  }

  if (_target = event.target.closest('.authAction[data-action="downloadCSV"]')) {
    jeedom.ocpp.authList.download({
      eqLogicId: getUrlVars('id'),
      error: function(error) {
        jeedomUtils.showAlert({ message: error.message, level: 'danger' })
      },
      success: function(data) {
        window.open('core/php/downloadFile.php?pathfile=' + data)
      }
    })
    return
  }

  if (_target = event.target.closest('.authAction[data-action="transactions"]')) {
    let tagId = _target.closest('tr').querySelector('.authAttr[data-l1key="id"]').value
    jeeDialog.dialog({
      id: 'jee_modal',
      title: "{{Transactions de l'utilisateur}} " + tagId,
      contentUrl: 'index.php?v=d&plugin=ocpp&modal=transactions&tagId=' + tagId
    })
    return
  }

  if (_target = event.target.closest('.authAction[data-action="remove"]')) {
    let authTable = document.getElementById('table_auth')
    authTable._dataTable.rows().remove(_target.closest('tr').dataIndex)
    if (authTable._dataTable.table.rows.length == 1) {
      authTable._dataTable.rows().remove(0)
      authTable.querySelector('thead').deleteRow(1)
    }
    modifyWithoutSave = authChanges = true
    return
  }
})

document.getElementById('div_pageContainer').addEventListener('change', function(event) {
  var _target = null
  // if (_target = event.target.closest('.eqLogicAttr[data-l2key="authorize_all_transactions"]')) {
  //   if (_target.checked) {
  //     document.getElementById('authorizations_div').unseen()
  //   } else {
  //     document.getElementById('authorizations_div').seen()
  //   }
  //   authChanges = true
  //   return
  // }

  // if (_target = event.target.closest('.measurandAttr')) {
  //   if (_target.getAttribute('data-l3key') && _target.getAttribute('data-l3key') != 'selected') {
  //     if (!_target.closest('tr').querySelector('.measurandAttr[data-l3key="selected"]').checked) {
  //       _target.closest('tr').querySelector('.measurandAttr[data-l3key="selected"]').checked = true
  //     }
  //   }
  //   modifyWithoutSave = measurandChanges = true
  //   return
  // }

  if (_target = event.target.closest('.authAttr')) {
    modifyWithoutSave = authChanges = true
    return
  }

  if (_target = event.target.closest('select.authSearch')) {
    searchAuthDataTable()
    return
  }
})

document.getElementById('table_auth').addEventListener('keyup', function(event) {
  var _target = null
  if (_target = event.target.closest('input.authSearch')) {
    searchAuthDataTable()
    return
  }
})

$('#uploadCsvFile').fileupload({
  replaceFileInput: false,
  url: 'plugins/ocpp/core/ajax/ocpp.ajax.php?action=uploadCsvFile&eqLogicId=' + getUrlVars('id'),
  dataType: 'json',
  done: function(e, data) {
    if (data.result.state != 'ok') {
      jeedomUtils.showAlert({
        message: data.result.result,
        level: 'danger'
      })
      return
    }
    window.location.reload()
  }
})

function printEqLogic(_eqLogic) {
  // if (_eqLogic.configuration.authorize_all_transactions == 1) {
  //   document.getElementById('authorizations_div').unseen()
  // } else {
  let authTable = document.getElementById('table_auth')
  authTable.querySelector('tbody').innerHTML = ''
  if (authTable.querySelector('thead').rows.length > 1) {
    authTable.querySelector('thead').deleteRow(1)
  }

  jeedom.ocpp.authList.get({
    eqLogicId: _eqLogic.id,
    error: function(error) {
      jeedomUtils.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      if (Object.keys(data).length) {
        let authDataTable = initAuthDatatable()
        for (id in data) {
          auth = data[id]
          auth.id = id
          authDataTable.rows().add(addAuth(auth))
        }
        jeedomUtils.datePickerInit('Y-m-d H:i', '.authAttr[data-l1key="expiry_date"]')
      }
    }
  })
  // }

  // document.querySelectorAll('input.measurandAttr').forEach(_measureInput => {
  //   _measureInput.checked = false
  // })
  // let meterValues = ['MeterValuesSampledData', 'MeterValuesAlignedData']
  // var phases = ['L1', 'L2', 'L3', 'N', 'L1-N', 'L2-N', 'L3-N', 'L1-L2', 'L2-L3', 'L3-L1']
  // meterValues.forEach(_meterValue => {
  //   if (isset(_eqLogic.configuration[_meterValue])) {
  //     let measurands = _eqLogic.configuration[_meterValue].split(',').map(item => item.trim())
  //     measurands.forEach(_measurand => {
  //       let phase = false
  //       let measurandSplit = _measurand.split('.')
  //       if (phases.includes(measurandSplit[measurandSplit.length - 1])) {
  //         phase = measurandSplit[measurandSplit.length - 1]
  //         measurandSplit.pop()
  //         _measurand = measurandSplit.join('.')
  //         if (_measurand == 'Current') {
  //           _measurand = 'Current.Import'
  //         }
  //       }
  //       if (document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"]')) {
  //         document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"]').checked = true
  //         if (phase) {
  //           document.querySelector('.measurandAttr[data-l1key="' + _meterValue + '"][data-l2key="' + _measurand + '"][data-l3key="' + phase + '"]').checked = true
  //         }
  //       }
  //     })
  //   }
  // })

  // if (_eqLogic.configuration.SupportedFeatureProfiles.includes('SmartCharging')) {
  //   document.querySelector('a[href="#smartchargingtab"]').closest('li').seen()
  // } else {
  //   document.querySelector('a[href="#smartchargingtab"]').closest('li').unseen()
  // }
}

function saveEqLogic(_eqLogic) {
  if (authChanges) {
    if (document.getElementById('table_auth')._dataTable) {
      document.getElementById('table_auth')._dataTable.reset()
    }
    jeedom.ocpp.authList.set({
      eqLogicId: _eqLogic.id,
      authList: document.getElementById('table_auth').querySelectorAll('tbody tr').getJeeValues('.authAttr'),
      error: function(error) {
        jeedomUtils.showAlert({ message: error.message, level: 'danger' })
      }
    })
  }

  // if (measurandChanges) {
  //   let measurandsConf = document.getElementById('measurandstab').getJeeValues('.measurandAttr')[0]
  //   for (let meterData in measurandsConf) {
  //     if (meterData == 'configuration') {
  //       for (let measurand in measurandsConf[meterData]) {
  //         jeedom.ocpp.chargerChangeConfiguration({
  //           eqLogicId: _eqLogic.id,
  //           key: measurand,
  //           value: measurandsConf[meterData][measurand],
  //           error: function(error) {
  //             jeedomUtils.showAlert({ message: error.message, level: 'danger' })
  //           }
  //         })
  //       }
  //     } else {
  //       _eqLogic.configuration[meterData] = ''
  //       for (let measurand in measurandsConf[meterData]) {
  //         if (measurandsConf[meterData][measurand]['selected'] == 1) {
  //           _eqLogic.configuration[meterData] += (_eqLogic.configuration[meterData] == '') ? measurand : ',' + measurand

  //         }
  //       }
  //       jeedom.ocpp.chargerChangeConfiguration({
  //         eqLogicId: _eqLogic.id,
  //         key: meterData,
  //         value: _eqLogic.configuration[meterData],
  //         error: function(error) {
  //           jeedomUtils.showAlert({ message: error.message, level: 'danger' })
  //         }
  //       })
  //     }

  //   }
  // }

  return _eqLogic
}

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
  newRow.setJeeValues(_cmd, '.cmdAttr')
  jeedom.cmd.changeType(newRow, init(_cmd.subType))
}

function addAuth(_auth = null) {
  let id = '<input class="authAttr form-control" data-l1key="id" value="' + (_auth?.id || '') + '">'
  let status = '<select class="authAttr form-control" data-l1key="status">'
  status += '<option value="accepted"' + (_auth?.status == 'accepted' ? ' selected' : '') + '>{{Autorisé}}</option>'
  status += '<option value="blocked"' + (_auth?.status == 'blocked' ? ' selected' : '') + '>{{Bloqué}}</option>'
  status += '<option value="expired"' + (_auth?.status == 'expired' ? ' selected' : '') + '>{{Expiré}}</option>'
  status += '<option value="invalid"' + (_auth?.status == 'invalid' ? ' selected' : '') + '>{{Invalide}}</option>'
  status += '</select>'
  let expiration = '<input class="authAttr form-control" data-l1key="expiry_date" value="' + (_auth?.expiry_date || '') + '">'
  let transactions = '<a class="btn btn-primary btn-xs authAction" data-action="transactions" title="{{Transactions}}"><i class="fas fa-charging-station"></i></a>'
  let remove = ' <a class="btn btn-danger btn-xs authAction" data-action="remove" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a>'

  return [id, status, expiration, transactions + remove]
}

function initAuthDatatable() {
  let authTable = document.getElementById('table_auth')
  if (authTable._dataTable) {
    authTable._dataTable.destroy()
    while (authTable._dataTable.table.rows.length > 0) {
      authTable._dataTable.rows().remove(0)
    }
  }
  authTable.querySelector('tbody').insertRow(0)
  let dataTable = new DataTable(authTable, {
    perPage: 25,
    perPageSelect: [10, 25, 50, 100],
    searchable: false,
    layout: {
      top: "{select}",
      bottom: "{pager}"
    }
  })
  let headerSearch = document.getElementById('table_auth').querySelector('thead').insertRow(1)
  headerSearch.innerHTML = authTable.querySelector('thead template').innerHTML
  return dataTable
}

function searchAuthDataTable() {
  let query = []
  document.querySelectorAll('.authSearch').forEach(_search => {
    if (_search.value != '') {
      query[_search.closest('th').cellIndex] = _search.value.toLowerCase()
    }
  })

  let dataTable = document.getElementById('table_auth')._dataTable
  dataTable.searching = true
  dataTable.searchData = []

  if (!query.length) {
    dataTable.searching = false
    dataTable.wrapper.classList -= 'search-results'
    dataTable.update()
    return false
  }

  dataTable.table.rows.forEach(row => {
    let includes = true

    for (let column in query) {
      if (row.cells[column].node.firstChild.value.toLowerCase().indexOf(query[column]) < 0) {
        includes = false
        break
      }
    }
    if (includes) {
      dataTable.searchData.push(row)
    }
  })
  dataTable.wrapper.classList += 'search-results'

  if (!dataTable.searchData.length) {
    dataTable.wrapper.classList -= 'search-results'
    dataTable.setMessage(dataTable.config.labels.noRows)
  } else {
    dataTable.update()
  }
}
