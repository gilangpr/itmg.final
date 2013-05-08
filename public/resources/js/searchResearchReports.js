function showRrSearch() {
	var storeRR = loadStore('ResearchReports');
	var storeRRC = loadStore('ResearchReportCategorys');
	var storeRR_curpage = storeRR.currentPage;
	storeRR.load({
		params: {
			all: 1
		}
	});
	storeRRC.load({
		params: {
			all: 1
		}
	});
	Ext.create('Ext.Window', {
		title: 'Search Research Report',
		width: 500,
		id: 'research-report-search-window',
		modal: true,
		draggable: false,
		resizable: false,
		items: [{
			xtype: 'form',
			layout: 'form',
			id: 'researchreports-search-form',
			bodyPadding: '5 5 5 5',
			waitMsgTarget: true,
			border: false,
			items: [{
				xtype: 'combobox',
				fieldLabel: 'Title',
				name: 'TITLE',
				id: 'research-report-title',
				store: storeRR,
				displayField: 'TITLE',
				typeAhead: true,
				allowBlank: true,
				minChars: 2,
				emptyText: 'All Research Report'
			},{
				xtype: 'combobox',
				fieldLabel: 'Category',
				name: 'RESEARCH_REPORT_CATEGORY',
				id: 'research-report-category',
				store: storeRRC,
				displayField: 'RESEARCH_REPORT_CATEGORY',
				typeAhead: true,
				allowBlank: true,
				minChars: 2,
				emptyText: 'All Category'
			}]
		}],
		buttons: [{
			text: 'Search',
			listeners: {
				click: function() {
					showLoadingWindow();
					var rTitle = Ext.getCmp('research-report-title').getValue();
					var rCategory = Ext.getCmp('research-report-category').getValue();
					if(typeof(rTitle) === 'undefined') {
						rTitle = '';
					}
					if(typeof(rCategory) === 'undefined') {
						rCategory = '';
					}
					this.up().up().close();
					var _storeResearchReports = Ext.create("Ext.data.Store", {
						model: "ResearchReport",
						storeId: "ResearchReports__",
						proxy:{"type":"ajax","api":{"read":"\/research\/request\/read","create":"\/research\/request\/create","update":"\/research\/request\/update","destroy":"\/research\/request\/destroy"},"actionMethods":{"create":"POST","destroy":"POST","read":"POST","update":"POST"},"reader":{"idProperty":"DATE","type":"json","root":"data.items","totalProperty":"data.totalCount"},"writer":{"type":"json","root":"data","writeAllFields":true},
							extraParams: {
								search: 2,
								title: rTitle,
								category: rCategory
							}}});
					_storeResearchReports.load({
						callback: function(d, i, e) {
							closeLoadingWindow();
							if(d.length == 0) {
								Ext.Msg.alert('Message', 'No data found.');
							} else {
								Ext.Msg.alert('Message', 'Result: ' + d.length + ' data(s) found.');
								var c = Ext.getCmp('main-content');
								var xyz = Math.random();
								var id = 'research-reports-search-result-' + xyz;
								if(!c.items.get(id)) {
									// Bottom Bar Panel Init :
									var comboBbar = new Ext.form.ComboBox({
									  name : 'perpage',
									  width: 50,
									  store: new Ext.data.ArrayStore({fields:['id'],data:[['25'],['50'],['75'],['100']]}),
									  mode : 'local',
									  value: '25',
									  listWidth     : 40,
									  triggerAction : 'all',
									  displayField  : 'id',
									  valueField    : 'id',
									  editable      : false,
									  forceSelection: true
									});
									
									var bbar = new Ext.PagingToolbar({
										store: _storeResearchReports,
										displayInfo: true,
										displayMsg: 'Displaying data {0} - {1} of {2}',
										emptyMsg: 'No data to display',
										items: [
										    '-',
										    'Records per page',
										    '-',
										    comboBbar
										]
									});
									
									comboBbar.on('select', function(combo, _records) {
										_storeResearchReports.pageSize = parseInt(_records[0].get('id'), 10);
										_storeResearchReports.loadPage(1);
									}, this);
									c.add({
										title: 'Research Report Search Result',
										id: id,
										closable: true,
										bbar: bbar,
										items: [{
											xtype: 'gridpanel',
											border: false,
											store: _storeResearchReports,
											columns: [{
												text: 'Title',
												flex: 1,
												dataIndex: 'TITLE'
											},{
												text: 'Category',
												width: 200,
												align: 'center',
												dataIndex: 'RESEARCH_REPORT_CATEGORY'
											},{
												text: 'File Size (Byte)',
												width: 150,
												dataIndex: 'FILE_SIZE',
												align: 'center',
												renderer: Ext.util.Format.numberRenderer('0.,/i')
											},{
												text: 'Downloaded',
												width: 120,
												align: 'center',
												dataIndex: 'TOTAL_HIT'
											},{
												text: 'File Type',
												width: 200,
												align: 'center',
												dataIndex: 'FILE_TYPE'
											},{
												text: 'Created Date',
												width: 200,
												align: 'center',
												dataIndex: 'CREATED_DATE'
											},{
												text: 'Last Modified',
												width: 200,
												align: 'center',
												dataIndex: 'MODIFIED_DATE'
											}],
											listeners: {
												itemdblclick: function(d, i, e) {
													document.location = sd.baseUrl + '/research/request/download/id/' + i.data.RESEARCH_REPORT_ID;
													var store = loadStore('ResearchReports');
													setTimeout(function(){
														store.loadPage(store.currentPage);
													},800);
												}
											}
										}]
									});
								}
								c.setActiveTab(id);
							}
						}
					});
				}
			}
		},{
			text: 'Cancel',
			listeners: {
				click: function() {
					this.up().up().close();
					storeRR.loadPage(storeRR_curpage);
				}
			}
		}]
	}).show();
}