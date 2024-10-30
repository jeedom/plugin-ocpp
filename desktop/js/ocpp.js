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
var authChanges = false

document.getElementById('div_pageContainer').addEventListener('click', function(event) {
  var _target = null
  if (_target = event.target.closest('.eqLogicAction[data-action="transactions"]')) {
    let cpId = (document.querySelector('.eqLogic').style.display != 'none') ? document.querySelector('.eqLogicAttr[data-l1key="logicalId"]').value : null
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
    jeedom.ocpp.downloadAuthlist({
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
  let authTable = document.getElementById('table_auth')
  authTable.querySelector('tbody').innerHTML = ''
  if (authTable.querySelector('thead').rows.length > 1) {
    authTable.querySelector('thead').deleteRow(1)
  }

  jeedom.ocpp.getAuthlist({
    eqLogicId: _eqLogic.id,
    error: function(error) {
      jeedomUtils.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      if (Object.keys(data).length) {
        let authDataTable = initAuthDatatable()
        for (let id in data) {
          auth = data[id]
          auth.id = id
          authDataTable.rows().add(addAuth(auth))
        }
        jeedomUtils.datePickerInit('Y-m-d H:i', '.authAttr[data-l1key="expiry_date"]')
      }
    }
  })

  var ocppConfig = document.getElementById('ocppConfigKey')
  var cpConfig = document.getElementById('cpConfigKey')
  ocppConfig.empty()
  cpConfig.empty()

  jeedom.ocpp.getConfiguration({
    eqLogicId: _eqLogic.id,
    error: function(error) {
      jeedomUtils.showAlert({ message: error.message, level: 'danger' })
    },
    success: function(data) {
      for (let param in data) {
        let readonly = (data[param]['readonly']) ? ' disabled' : ''
        let value = (isset(data[param]['value'])) ? data[param]['value'] : ''
        let name = param
        let type = 'text'
        let divNode = cpConfig
        if (isset(data[param]['name'])) {
          divNode = ocppConfig
          name = data[param]['name']
          type = data[param]['type']
          if (type == 'checkbox' && value.toLowerCase() == 'true') {
            readonly += ' checked'
          }
        }

        let div = '<div class="form-group">'
        div += '<label class="col-sm-4 control-label">' + name
        if (isset(data[param]['description'])) {
          div += '<sup><i class="fas fa-question-circle tooltips" title="' + data[param]['description'] + '"></i></sup>'
        }
        div += '</label>'
        div += '<div class="col-sm-6">'
        div += '<input type="' + type + '" class="localConfigKey form-control" data-l1key="' + param + '" data-l2key="value" value="' + value + '"' + readonly + '>'
        div += '</div>'
        div += '</div>'

        divNode.insertAdjacentHTML('beforeend', div)
      }
    }
  })
}

function saveEqLogic(_eqLogic) {
  console.log(document.getElementById('eqlogictab').getJeeValues('.localConfigKey')[0])

  if (authChanges) {
    if (document.getElementById('table_auth')._dataTable) {
      document.getElementById('table_auth')._dataTable.reset()
    }
    jeedom.ocpp.setAuthlist({
      eqLogicId: _eqLogic.id,
      authList: document.getElementById('table_auth').querySelectorAll('tbody tr').getJeeValues('.authAttr'),
      error: function(error) {
        jeedomUtils.showAlert({ message: error.message, level: 'danger' })
      }
    })
  }
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
