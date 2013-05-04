{
	title: 'Portfolio Distributions',
	style: {
		float: 'right',
		width: '50%'
	},
	minHeight: 300,
	items: [{
		xtype: 'chart',
		animate: {
			easing: 'bounceOut',
			duration: 400
		},
		store: storePD,
		shadow: true,
		width: 580,
		height: 300,
		style: 'background: #FFF',
		theme: 'Base:gradients',
		axes: [{
			type: 'Numeric',
			position: 'left',
			fields: ['VALUE'],
			label: {
				renderer: Ext.util.Format.numberRenderer('0,0')
			},
			grid: true,
			minimum: 0
		},{
			type: 'Category',
			position: 'bottom',
			fields: ['TITLE'],
		}],
		series: [{
			type: 'column',
			axis: 'left',
			highlight: true,
			label: {
				display: 'insideEnd',
                  'text-anchor': 'middle',
                   field: 'VALUE',
                   orientation: 'horizontal',
                   fill: '#FFF',
                   font: '14px Arial'
			},
			xField: 'TITLE',
            yField: 'VALUE'
		}]
	}],
	tbar: [{
		xtype: 'button',
		text: 'Manage Data',
		iconCls: 'icon-detail',
		listeners: {
			click: function() {
				Ext.create('Ext.Window', {
					title: 'Portfolio Distributions Data',
					resizabe: false,
					modal: true,
					draggable: false,
					resizable: false,
					items: [{
						xtype: 'gridpanel',
						minWidth: 500,
						minHeight: 200,
						border: false,
						plugins:[cellEditing4],
						id: 'investors-details-portofolio-distribution-grid-data-' + data.INVESTOR_ID,
						store: storePD,
						tbar: [{
							xtype: 'button',
							text: 'Add New Portfolio Distributions Data',
							iconCls: 'icon-go',
							listeners: {
								click: function() {
									Ext.create('Ext.Window', {
										title: 'Add New Portfolio Distributions Data',
										modal: true,
										draggable: false,
										resizable: false,
										items: [{
											xtype: 'form',
											id: 'investors-detail-portfolio-distributions-add-new-form-' + data.INVESTOR_ID,
											minWidth: 300,
											border: false,
											bodyPadding: '5 5 5 5',
											items: [{
												xtype: 'textfield',
												fieldLabel: 'Title <span style="color:red;">*</span>',
												name: 'TITLE',
												allowBlank: false
											},{
												xtype: 'numberfield',
												fieldLabel: 'Value (USD Bn)',
												name: 'VALUE',
												allowBlank: false,
												value: 0,
												minValue: 0
											}]
										}],
										buttons: [{
											text: 'Save',
											listeners: {
												click: function() {
													var form = Ext.getCmp('investors-detail-portfolio-distributions-add-new-form-' + data.INVESTOR_ID);
													if(form.getForm().isValid()) {
														form.getForm().submit({
															url: sd.baseUrl + '/portfoliodistribution/request/create/id/' + data.INVESTOR_ID,
															waitMsg: 'Saving data, please wait..',
															params: {
																id: data.INVESTOR_ID
															},
															success: function(d, e) {
																var json = Ext.decode(e.response.responseText);
																Ext.Msg.alert('Message', 'Savind data success.');
																storePD.load({
																	params: {
																		id: data.INVESTOR_ID
																	}
																});
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
									var __c = Ext.getCmp('investors-details-portofolio-distribution-grid-data-' + data.INVESTOR_ID);
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
															url: sd.baseUrl + '/portfoliodistribution/request/destroy',
															params: {
																PORTFOLIO_DISTRIBUTION_ID: __data.PORTFOLIO_DISTRIBUTION_ID,
																INVESTOR_ID:data.INVESTOR_ID
															},
															success: function(d) {
																var json = Ext.decode(d.responseText); // Decode responsetext | Json to Javasript Object
																closeLoadingWindow();
																storePD.load({
																	params: {
																		id: data.INVESTOR_ID
																	}
																});
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
						columns: [{
							text: 'Title',
							flex: 1,
							dataIndex: 'TITLE',
							editor:{
								name:'TITLE'
							}
						},{
							text: 'Value<br/>(USD Bn)',
							width: 120,
							align: 'center',
							dataIndex: 'VALUE',
							editor:{
								name:'VALUE',
								xtype:'numberfield'
							}
						}]
					}]
				}).show();
			}
		}
	}]
}