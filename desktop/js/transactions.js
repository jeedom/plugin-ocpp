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

var transactionsTable = document.getElementById('table_transactions')
if (transactionsTable._dataTable) {
	transactionsTable._dataTable.destroy()
}
new DataTable(transactionsTable, {
	perPage: 25,
	perPageSelect: [10, 25, 50, 100],
	searchable: false,
	layout: {
		top: "{select}",
		bottom: "{pager}"
	}
	// >4.6.0 mini : }).on('page columns.sort', function() {
}).on('page', function() {
	jeedomUtils.initTooltips(transactionsTable)
})

var ocppTransModal = jeeDialog.get('#ocpp_trans_modal', 'dialog')
ocppTransModal?.querySelector('#md_ocppTransactions').addEventListener('click', function(event) {
	let _target = null

	if (_target = event.target.closest('.transAction[data-action="remove"]')) {
		event.stopImmediatePropagation()
		const tr = _target.closest('tr')
		const transactionId = tr.dataset.id
		const message = '{{Êtes-vous sûr de vouloir supprimer cette transaction ?}} (#' + transactionId + ')'
		jeeDialog.confirm(message, function(result) {
			if (result) {
				jeedom.ocpp.removeTransaction({
					transactionId: transactionId,
					error: function(error) {
						jeedomUtils.showAlert({
							attachTo: ocppTransModal,
							message: error.message,
							level: 'danger'
						})
					},
					success: function() {
						tr.remove()
					}
				})
			}
		})
		return
	}
})

// Native listener kept for once jeedom.vanillaEvents ships in core (see jeedom/core#3416), switch back then instead of the jQuery listener below
// if (jeedom.vanillaEvents && !jeedom.vanillaEvents.includes('ocpp_transaction::update')) {
// 	jeedom.vanillaEvents.push('ocpp_transaction::update')
// }
// document.body.unRegisterEvent('ocpp_transaction::update').registerEvent('ocpp_transaction::update', function(event) {
// 	const table = ocppTransModal?.querySelector('#table_transactions')
// 	if (!table) return
// 	if (table.dataset.tag_id && event.detail.tagId != table.dataset.tag_id) return
// 	if (table.dataset.cp_id && event.detail.cpId != table.dataset.cp_id) return
// 	const cells = Object.values(event.detail.cells)
// 	let row = table._dataTable.table.rows.find(r => r.node.dataset.id == event.detail.transactionId)
// 	if (row) {
// 		cells.forEach((html, i) => row.cells[i].setContent(html))
// 	} else {
// 		row = table._dataTable.rows().add(cells)
// 		row.node.dataset.id = event.detail.transactionId
// 	}
// 	row.cells[5].node.dataset.sorton = event.detail.rawDuration
// })
$(document.body).off('ocpp_transaction::update').on('ocpp_transaction::update', function(event, detail) {
	const table = ocppTransModal?.querySelector('#table_transactions')
	if (!table) return
	if (table.dataset.tag_id && detail.tagId != table.dataset.tag_id) return
	if (table.dataset.cp_id && detail.cpId != table.dataset.cp_id) return
	const cells = Object.values(detail.cells)
	let row = table._dataTable.table.rows.find(r => r.node.dataset.id == detail.transactionId)
	if (row) {
		cells.forEach((html, i) => row.cells[i].setContent(html))
	} else {
		row = table._dataTable.rows().add(cells)
		row.node.dataset.id = detail.transactionId
	}
	row.cells[5].node.dataset.sorton = detail.rawDuration
})
