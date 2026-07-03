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
var ocppAuthChanges = selectedGroupId = false
var ocppAuthModal = jeeDialog.get('#ocpp_auth_modal', 'dialog')
var csvUpload = new jeeFileUploader({
	fileInput: document.getElementById('uploadAuthList'),
	done: function(e, data) {
		if (data.result.state != 'ok') {
			jeedomUtils.showAlert({
				attachTo: ocppAuthModal,
				message: data.result.result,
				level: 'danger'
			})
			return
		}
		document.getElementById('uploadAuthList').value = ''
		destroyAuthDatatable(selectedGroupId)
		ocppAuthModal.querySelector('#auth_groups_menu > li.selected > .authAction[data-action="selectGroup"]').triggerEvent('click')
	}
})

ocppAuthModal.addEventListener('click', function(event) {
	event.stopImmediatePropagation()
	let _target = null

	if (_target = event.target.closest('.authAction[data-action="addGroup"]')) {
		jeeDialog.prompt("{{Nom du nouveau groupe d'autorisations ?}}", function(result) {
			if (result !== null && result.trim() != '') {
				addGroup({
					id: Math.random().toString(36).substring(8),
					name: result
				}, true)
				jeedomUtils.initTooltips()
				ocppAuthChanges = true
			}
		})
		return
	}

	if (_target = event.target.closest('.authAction[data-action="selectGroup"]')) {
		document.getElementById('table_auth_' + selectedGroupId)?.closest('.dt-wrapper').addClass('hidden')
		ocppAuthModal.querySelector('li.selected')?.removeClass('selected')

		const li = _target.closest('li')
		selectedGroupId = li.dataset.groupId
		li.addClass('selected')
		let table
		if (table = document.getElementById('table_auth_' + selectedGroupId)) {
			table.closest('.dt-wrapper').removeClass('hidden')
		} else {
			table = document.createElement('table')
			table.id = 'table_auth_' + selectedGroupId
			table.classList = 'table table-condensed'
			table.innerHTML = document.getElementById('table_auth_template').innerHTML
			ocppAuthModal.querySelector('#authorizations_div').appendChild(table)
			table = initAuthDatatable(selectedGroupId)

			jeedom.ocpp.getAuthGroup({
				groupId: selectedGroupId,
				error: function(error) {
					jeedomUtils.showAlert({
						attachTo: ocppAuthModal,
						message: error.message,
						level: 'danger'
					})
				},
				success: function(data) {
					if (data.length) {
						const rows = data.map(auth => addAuth(auth))
						table.import({ data: { data: rows.reverse() } })
						jeedomUtils.initTooltips()
						jeedomUtils.datePickerInit('Y-m-d H:i', '.authAttr[data-l1key="expiry_date"]')
					} else {
						table.currentPage = 1
					}
				}
			})
		}

		ocppAuthModal.querySelector('#authorizations_div>.input-group').removeClass('hidden')
		csvUpload.url = 'plugins/ocpp/core/ajax/ocpp.ajax.php?action=uploadAuthList&groupId=' + selectedGroupId
		return
	}

	if (_target = event.target.closest('.authAction[data-action="removeGroup"]')) {
		const li = _target.closest('li')
		let message = '{{Êtes-vous sûr de vouloir supprimer le groupe}} '
		message += li.querySelector('.authAction[data-action="selectGroup"]').innerText + '?<br>'
		message += '{{Toutes les autorisations des bornes liées à ce groupe seront supprimées!}}'
		jeeDialog.confirm(message, function(result) {
			if (result) {
				jeedom.ocpp.removeAuthGroup({
					groupId: li.dataset.groupId,
					error: function(error) {
						jeedomUtils.showAlert({
							attachTo: ocppAuthModal,
							message: error.message,
							level: 'danger'
						})
					},
					success: function(data) {
						destroyAuthDatatable(li.dataset.groupId)
						li.remove()
						if (ocppAuthModal.querySelectorAll('#auth_groups_menu > li').length > 0) {
							ocppAuthModal.querySelector('.authAction[data-action="selectGroup"]').triggerEvent('click')
						} else {
							ocppAuthModal.querySelector('#authorizations_div>.input-group').addClass('hidden')
							ocppAuthChanges = false
						}
					}
				})
			}
		})
		return
	}

	if (_target = event.target.closest('.authAction[data-action="add"]')) {
		const authDataTable = document.getElementById('table_auth_' + selectedGroupId)._dataTable
		authDataTable.rows().add(addAuth())
		jeedomUtils.datePickerInit('Y-m-d H:i', '.authAttr[data-l1key="expiry_date"]')
		jeedomUtils.initTooltips()
		ocppAuthChanges = true
		return
	}

	if (_target = event.target.closest('.authAction[data-action="downloadCSV"]')) {
		jeedom.ocpp.downloadAuthlist({
			groupId: selectedGroupId,
			error: function(error) {
				jeedomUtils.showAlert({
					attachTo: ocppAuthModal,
					message: error.message,
					level: 'danger'
				})
			},
			success: function(data) {
				window.open('core/php/downloadFile.php?pathfile=' + data)
			}
		})
		return
	}

	if (_target = event.target.closest('.authAction[data-action="transactions"]')) {
		const tagId = _target.closest('tr').querySelector('.authAttr[data-l1key="id"]').value
		jeeDialog.dialog({
			id: 'jee_modal',
			title: "{{Transactions de l'utilisateur}} " + tagId,
			contentUrl: 'index.php?v=d&plugin=ocpp&modal=transactions&tagId=' + tagId
		})
		return
	}

	if (_target = event.target.closest('.authAction[data-action="remove"]')) {
		const authDataTable = _target.closest('table')._dataTable
		authDataTable.rows().remove(_target.closest('tr').dataIndex)
		ocppAuthChanges = true
		return
	}
})

document.getElementById('auth_groups_menu').addEventListener('dblclick', function(event) {
	let _target = null
	if (_target = event.target.closest('.authAction[data-action="selectGroup"]')) {
		jeeDialog.prompt({
			message: "{{Nouveau nom du groupe d'autorisations ?}}",
			placeholder: _target.innerText
		}, function(result) {
			if (result !== null && result.trim() != '') {
				_target.innerText = result
				ocppAuthChanges = true
			}
		})
		return
	}
})

document.getElementById('authorizations_div').addEventListener('change', function(event) {
	let _target = null
	if (_target = event.target.closest('.authAttr')) {
		ocppAuthChanges = true
		return
	}

	if (_target = event.target.closest('select.authSearch')) {
		searchAuthDataTable()
		return
	}
})

document.getElementById('authorizations_div').addEventListener('keyup', function(event) {
	let _target = null
	if (_target = event.target.closest('input.authSearch')) {
		searchAuthDataTable()
		return
	}
})

for (let authGroupId in _authGroups) {
	addGroup({
		id: authGroupId,
		name: _authGroups[authGroupId]
	})
}
ocppAuthModal.querySelector('.authAction[data-action="selectGroup"]')?.triggerEvent('click')

function addGroup(_group, _select = false) {
	const li = document.createElement('li')
	li.innerHTML = '<a class="authAction" data-action="selectGroup" title="' + _group.id + '" style="flex:1;font-size:16px;">' + _group.name + '</a>'
	li.innerHTML += '<button class="btn btn-xs btn-danger authAction" title="{{Supprimer le groupe}}" data-action="removeGroup"><i class="fas fa-trash-alt"></i></button>'
	li.dataset.groupId = _group.id
	li.style.display = 'flex'
	li.style.alignItems = 'center'
	ocppAuthModal.querySelector('#auth_groups_menu').appendChild(li)
	if (_select) {
		li.querySelector('a.authAction[data-action="selectGroup"]').triggerEvent('click')
	}
}

function addAuth(_auth = null) {
	const id = '<input class="authAttr form-control" data-l1key="id" value="' + (_auth?.id || '') + '">'
	const name = '<input class="authAttr form-control" data-l1key="name" value="' + (_auth?.name || '') + '">'
	let status = '<select class="authAttr form-control" data-l1key="status">'
	status += '<option value="Accepted"' + (_auth?.status == 'Accepted' ? ' selected' : '') + '>{{Autorisé}}</option>'
	status += '<option value="Blocked"' + (_auth?.status == 'Blocked' ? ' selected' : '') + '>{{Bloqué}}</option>'
	status += '<option value="Expired"' + (_auth?.status == 'Expired' ? ' selected' : '') + '>{{Expiré}}</option>'
	status += '<option value="Invalid"' + (_auth?.status == 'Invalid' ? ' selected' : '') + '>{{Invalide}}</option>'
	status += '</select>'
	const expiration = '<input class="authAttr form-control" data-l1key="expiry_date" value="' + (_auth?.expiry_date || '') + '">'
	const concurrentTx = '<input type="checkbox" class="authAttr" data-l1key="concurrentTx"' + ((_auth?.concurrentTx == '1') ? ' checked' : '') + '>'
	const transactions = '<a class="btn btn-primary btn-xs authAction" data-action="transactions" title="{{Transactions}}"><i class="fas fa-charging-station"></i></a>'
	const remove = ' <a class="btn btn-danger btn-xs authAction" data-action="remove" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a>'

	return [id, name, status, expiration, concurrentTx, transactions + remove]
}

function initAuthDatatable(_groupId) {
	const authTable = document.getElementById('table_auth_' + _groupId)
	const dataTable = new DataTable(authTable, {
		perPage: 15,
		perPageSelect: [10, 15, 25, 50],
		searchable: false,
		layout: {
			top: "{select}",
			bottom: "{pager}"
		}
	})
	const headerSearch = authTable.querySelector('thead').insertRow(1)
	headerSearch.innerHTML = authTable.querySelector('thead template').innerHTML
	return dataTable
}

function searchAuthDataTable() {
	const table = document.getElementById('table_auth_' + selectedGroupId)
	const dataTable = table._dataTable
	dataTable.searching = true
	dataTable.searchData = []

	const query = []
	table.querySelectorAll('.authSearch').forEach(_search => {
		if (_search.value != '') {
			query[_search.closest('th').cellIndex] = _search.value.toLowerCase()
		}
	})

	if (!query.length) {
		dataTable.searching = false
		dataTable.wrapper.removeClass('search-results')
		dataTable.update()
		return false
	}

	dataTable.table.rows.forEach(row => {
		if (row.cells.length == 0) {
			return
		}
		let includes = true

		for (const column in query) {
			if (row.cells[column].node.firstChild.value.toLowerCase().indexOf(query[column]) < 0) {
				includes = false
				break
			}
		}
		if (includes) {
			dataTable.searchData.push(row)
		}
	})
	dataTable.wrapper.addClass('search-results')

	if (!dataTable.searchData.length) {
		dataTable.wrapper.removeClass('search-results')
		dataTable.setMessage(dataTable.config.labels.noRows)
	} else {
		dataTable.update()
	}
}

function destroyAuthDatatable(_groupId) {
	const authTable = document.getElementById('table_auth_' + _groupId)
	authTable._dataTable.destroy()
	while (authTable._dataTable.table.rows.length > 0) {
		authTable._dataTable.rows().remove(0)
	}
	authTable.remove()
}
