
var storeEQ = Ext.create("Ext.data.Store", {
	model: "Equityasset",
	storeId: "Equityassets",
	proxy:{"type":"ajax","api":{"read":"\/equityasset\/request\/read","create":"\/equityasset\/request\/create","update":"\/equityasset\/request\/update","destroy":"\/equityasset\/request\/destroy"},"actionMethods":{"create":"POST","destroy":"POST","read":"POST","update":"POST"},"reader":{"idProperty":"EQUITY_ID","type":"json","root":"data.items","totalProperty":"data.totalCount"},"writer":{"type":"json","root":"data","writeAllFields":true}},
	sorter: {"property":"EQUITY_ID","direction":"ASC"}});
storeEQ.autoSync = true;
storeEQ.load();

 //var storeEQ = loadStore('Equityassets');
 var cellEditing = Ext.create('Ext.grid.plugin.RowEditing', {
			clicksToMoveEditor: 1,
	        autoCancel: false
	    });
	Ext.create('Ext.Window', {
					title: 'Equity Asset',
					resizabe: false,
					modal: true,
					draggable: false,
					resizable: false,
					items: [{
						xtype: 'gridpanel',
						minWidth: 300,
						minHeight: 100,
						border: false,
						store:storeEQ,
						plugins: [cellEditing],
						id: 'investors-equity-asset-data-grid',
						/*
						tbar: [{
							xtype: 'button',
							text: 'Add New Equity Type',
							iconCls: 'icon-go',
							listeners: {
								click: function() {
									Ext.create('Ext.Window', {
										title: 'Add New Equity Type',
										modal: true,
										draggable: true,
										resizable: false,
										items: [{
											xtype: 'form',
											id: 'investors-equity-asset-add-new-form',
											minWidth: 300,
											border: false,
											bodyPadding: '5 5 5 5',
											items: [{
												xtype: 'textfield',
												fieldLabel: 'Equity Type',
												name: 'EQUITY_TYPE',
												allowBlank: false
											},{
												xtype: 'numberfield',
												fieldLabel: 'Min.Value',
												name: 'MIN_VALUE',
												allowBlank: false,
												value: 0,
												minValue: 0
											},{
												xtype: 'numberfield',
												fieldLabel: 'Max.Value',
												name: 'MAX_VALUE',
												allowBlank: false,
												value: 0,
												minValue: 0
											}]
										}],
										buttons: [{
											text: 'Save',
											listeners: {
												click: function() {
													var form = Ext.getCmp('investors-equity-asset-add-new-form');
													if(form.getForm().isValid()) {
														form.getForm().submit({
															url: sd.baseUrl + '/equityasset/request/create',
															waitMsg: 'Saving data, please wait..',
															success: function(d, e) {
																var json = Ext.decode(e.response.responseText);
																Ext.Msg.alert('Message', 'Saving data success.');
																storeEQ.load();
																form.getForm().reset();
															},
															failure: function(d, e) {
																var json = Ext.decode(e.response.responseText);
																Ext.Msg.alert('Error', json.error_message);
															}
														})
													}
												}
											}
										},{
											text: 'Cancel',
											listeners: {
												click: function() {
													this.up().up().close();
												}
											}
										}]
									}).show();
								}
							}
						},{
							xtype: 'button',
							text: 'Delete',
							iconCls: 'icon-stop',
							listeners: {
								click: function() {
									var __c = Ext.getCmp('investors-equity-asset-data-grid');
									var __selected = __c.getSelectionModel().getSelection();
									if(__selected.length > 0) {
										var __data = __selected[0].data;
										Ext.create('Ext.Window', {
											html: 'Are you sure want do delete selected item(s) ?',
											bodyPadding: '20 5 5 17',
											title: 'Confirmation',
											resizable: false,
											modal: true,
											closable: false,
											draggable: false,
											width: 300,
											height: 120,
											buttons: [{
												text: 'Yes',
												listeners: {
													click: function() {
														showLoadingWindow();
														this.up().up().close();
														Ext.Ajax.request({
															url: sd.baseUrl + '/equityasset/request/destroy',
															params: {
																EQUITY_ID: __data.EQUITY_ID
															},
															success: function(d) {
																var json = Ext.decode(d.responseText); // Decode responsetext | Json to Javasript Object
																closeLoadingWindow();
																storeEQ.load();
															},
															failure: function(d) {
																var json = Ext.decode(d.responseText); // Decode responsetext | Json to Javasript Object
																closeLoadingWindow();
																Ext.Msg.alert('Error', json.error_message);
															}
														});
													}
												}
											},{
												text: 'No',
												listeners: {
													click: function() {
														this.up().up().close();
													}
												}
											}]
										}).show();
									} else {
										Ext.Msg.alert('Message', 'You did not select any data.');
									}
								}
							}
						}],
						*/
						columns: [{
							text: 'Equity Type',
							flex: 1,
							dataIndex: 'EQUITY_TYPE'
						},{
							text: 'Min.Value',
							width: 120,
							flex:1,
							align: 'center',
							dataIndex: 'MIN_VALUE',
							editor:{
								name:'MIN_VALUE',
								xtype:'numberfield',
								allowBlank:false,
								minValue:0
							}
						},{
							text:'Max.Value',
							flex:1,
							dataIndex:'MAX_VALUE',
							editor:{
								name:'MAX_VALUE',
								xtype:'numberfield',
								allowBlank:false,
								minValue:0
							}
						}]
					}]
				}).show();
