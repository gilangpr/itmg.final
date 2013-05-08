function showNewsSearch() {
	var storeRR = loadStore('Newss');
	var storeRRC = loadStore('NewsCategorys');
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
		title: 'Search News',
		width: 500,
		id: 'news-search-window',
		modal: true,
		draggable: false,
		resizable: false,
		items: [{
			xtype: 'form',
			layout: 'form',
			id: 'news-search-form',
			bodyPadding: '5 5 5 5',
			waitMsgTarget: true,
			border: false,
			items: [{
				xtype: 'combobox',
				fieldLabel: 'Title',
				name: 'TITLE',
				id: 'news-title',
				store: storeRR,
				displayField: 'TITLE',
				typeAhead: true,
				allowBlank: true,
				minChars: 2,
				emptyText: 'All News'
			},{
				xtype: 'combobox',
				fieldLabel: 'Category',
				name: 'NEWS_CATEGORY',
				id: 'news-category',
				store: storeRRC,
				displayField: 'NEWS_CATEGORY',
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
					var rTitle = Ext.getCmp('news-title').getValue();
					var rCategory = Ext.getCmp('news-category').getValue();
					if(typeof(rTitle) === 'undefined') {
						rTitle = '';
					}
					if(typeof(rCategory) === 'undefined') {
						rCategory = '';
					}
					this.up().up().close();
					var _storeNews = Ext.create("Ext.data.Store", {
						model: "News",
						storeId: "Newss__",
						proxy:{"type":"ajax","api":{"read":"\/news\/request\/read","create":"\/news\/request\/create","update":"\/news\/request\/update","destroy":"\/news\/request\/destroy"},"actionMethods":{"create":"POST","destroy":"POST","read":"POST","update":"POST"},"reader":{"idProperty":"DATE","type":"json","root":"data.items","totalProperty":"data.totalCount"},"writer":{"type":"json","root":"data","writeAllFields":true},
							extraParams: {
								search: 2,
								title: rTitle,
								category: rCategory
							}}});
					_storeNews.load({
						callback: function(d, i, e) {
							closeLoadingWindow();
							if(d.length == 0) {
								Ext.Msg.alert('Message', 'No data found.');
							} else {
								Ext.Msg.alert('Message', 'Result: ' + d.length + ' data(s) found.');
								var c = Ext.getCmp('main-content');
								var xyz = Math.random();
								var id = 'news-reports-search-result-' + xyz;
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
										store: _storeNews,
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
										_storeNews.pageSize = parseInt(_records[0].get('id'), 10);
										_storeNews.loadPage(1);
									}, this);
									c.add({
										title: 'News Search Result',
										id: id,
										closable: true,
										bbar: bbar,
										items: [{
											xtype: 'gridpanel',
											border: false,
											store: _storeNews,
											columns: [{
												text: 'Title',
												width: 200,
												flex: 1,
												dataIndex: 'TITLE'
											},{
												text: 'Category',
												width: 100,
												align: 'center',
												dataIndex: 'NEWS_CATEGORY'
											},{
												text: 'Company',
												width: 100,
												align: 'left',
												dataIndex: 'COMPANY_NAME'
											},{
												text: 'Source',
												width: 100,
												align: 'left',
												dataIndex: 'SOURCE'
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
													document.location = sd.baseUrl + '/news/request/download/id/' + i.data.NEWS_ID;
													var store = loadStore('Newss');
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