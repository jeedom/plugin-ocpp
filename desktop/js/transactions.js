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
}).on('page', function() {
	jeedomUtils.initTooltips(transactionsTable)
})

var ocppTransModal = jeeDialog.get('#ocpp_trans_modal', 'dialog')
ocppTransModal.addEventListener('click', function(event) {
	event.stopImmediatePropagation()
	var _target = null

	if (_target = event.target.closest('.transAction[data-action="remove"]')) {
		let tr = _target.closest('tr')
		let transactionId = tr.dataset.id
		let message = '{{Êtes-vous sûr de vouloir supprimer la transaction}} ' + transactionId + ' ?'
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
