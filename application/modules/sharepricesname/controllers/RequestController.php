<?php

class Sharepricesname_RequestController extends MyIndo_Controller_Action
{
	protected $_model;
	protected $_limit;
	protected $_start;
	protected $_posts;
	protected $_error_code;
	protected $_error_message;
	protected $_success;
	protected $_name;
	
	public function init()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_model = new Application_Model_SharepricesName();
		if($this->getRequest()->isPost()) {
			$this->_posts = $this->getRequest()->getPost();
		} else {
			$this->_posts = array();
		}
		
		$this->_start = (isset($this->_posts['start'])) ? $this->_posts['start'] : 0;
		$this->_limit = (isset($this->_posts['limit'])) ? $this->_posts['limit'] : 25;
		
		$this->_error_code = 0;
		$this->_error_message = '';
		$this->_success = true;
		$this->_data = array(
				'data' => array(
						'items' => array(),
						'totalCount' => 0
				)
		);
	}
	
public function readAction()
	{
		if($this->isPost() && $this->isAjax()) {
			if(isset($this->_posts['sort']) || isset($this->_posts['query'])) {
				try {
					if(isset($this->_posts['sort'])) {
						// Decode sort JSON :
						$sort = Zend_Json::decode($this->_posts['sort']);
					}
					// Query data
					$q = $this->_model->select();
						
					if(isset($this->_posts['sort'])) {
						$q->order($sort[0]['property'] . ' ' . $sort[0]['direction']);
					}
						
					if(isset($this->_posts['query'])) {
						if(!empty($this->_posts['query']) && $this->_posts['query'] != '') {
							$q->where('SHAREPRICES_NAME LIKE ?', '%' . $this->_posts['query'] . '%');
						}
					}
						
					// Count all data
					$rTotal = $q->query()->fetchAll();
					$totalCount = count($rTotal);
						
					// Fetch sorted & limit data
					$q->limit($this->_limit, $this->_start);
					$result = $q->query()->fetchAll();
						
					$this->_data['data']['items'] = $result;
					$this->_data['data']['totalCount'] = $totalCount;
				} catch (Exception $e) {
					$this->_error_code = $e->getCode();
					$this->_error_message = $e->getMessage();
					$this->_success = false;
				}
			} else {
				try {
					if(isset($this->_posts['all']) && $this->_posts['all'] == 1) {
						$this->_limit = $this->_model->count();
					}
					if(!isset($this->_posts['query']) || $this->_posts['query'] == '' || empty($this->_posts['query'])) {
						$list = $this->_model->getListLimit($this->_limit, $this->_start, $this->_name . ' ASC');
					} else {
						$where = $this->_model->getAdapter()->quoteInto($this->_name . ' LIKE ?', '%' . $this->_posts['query'] . '%');
						$list = $this->_model->getListLimit($this->_limit, $this->_start, $this->_name . ' ASC', $where);
					}
						
					$this->_data['data']['items'] = $list;
					$this->_data['data']['totalCount'] = $this->_model->count();
				}catch(Exception $e) {
					$this->_error_code = $e->getCode();
					$this->_error_message = $e->getMessage();
					$this->_success = false;
				}
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}
	
	public function createAction()
	{
		$this->_model2 = new MyIndo_Ext_ContentColumns();
		$this->_model3 = new MyIndo_Ext_ModelFields();
		$this->_modelSp = new Application_Model_Shareprices();
		$this->_modelLog = new Application_Model_SharepricesLog();
		
		$data = array(
				'data' => array()
		);
		
		if ($this->_model->isExistByKey('SHAREPRICES_NAME', $this->_posts['SHAREPRICES_NAME']))
		{
			
			$this->_success = false;
			$this->_error_message = 'Shareprices name already exist';
		}
		else 
		{
			try {
				$this->_model3->insert(array(
						'MODEL_ID' => 6,
						'NAME' => $this->_posts['SHAREPRICES_NAME'],
						'TYPE' => 'float',
						'CREATED_DATE' => date('Y-m-d H:i:s')
				));
				$id = $this->_model3->getPkByKey('NAME', $this->_posts['SHAREPRICES_NAME']);
				// Insert Data :
				$getSNid = $this->_model->insert(array(
						'SHAREPRICES_NAME_ID'=> $id,
						'SHAREPRICES_NAME'=> $this->_posts['SHAREPRICES_NAME'],
						'CREATED_DATE' => date('Y-m-d H:i:s')
				));
				$this->_model2->insert(array(
						'CONTENT_COLUMN_ID' => $id,
						'CONTENT_ID' => 6,
						'TEXT' => $this->_posts['SHAREPRICES_NAME'],
						'DATAINDEX' => $this->_posts['SHAREPRICES_NAME'],
						'DATATYPE' => 'float',
						'ALIGN' => 'center',
						'WIDTH' => '100',
						'EDITABLE' => 1,
						'FLEX' => 1,
						'INDEX' => 0,
						'CREATED_DATE' => date('Y-m-d H:i:s')
				));

				/* Set Value */
				$_qSp = $this->_modelSp->select()
				->from('SHAREPRICES', array('DATE'))
				->distinct(true);
				$_result = $_qSp->query()->fetchAll();
				foreach($_result as $k=>$d) {
					$this->_modelSp->insert(array(
						'DATE'=> $d['DATE'],
						'SHAREPRICES_NAME' => $this->_posts['SHAREPRICES_NAME'],
						'VALUE' => 0,
						'CREATED_DATE' => date('Y-m-d H:i:s'),
						'SHAREPRICES_NAME_ID' => $getSNid
						));
					//insert shareprices log
					$this->_modelLog->insert(array(
							'DATE'=> $d['DATE'],
							'SHAREPRICES_NAME' => $this->_posts['SHAREPRICES_NAME'],
							'VALUE_BEFORE' => 0,
							'VALUE_AFTER' => 0,
							'CREATED_DATE' => date('Y-m-d H:i:s'),
							'SHAREPRICES_NAME_ID' => $getSNid
					));
				}
				
			}catch(Exception $e) {
				$this->_error_code = $e->getCode();
				$this->_error_message = $e->getMessage();
				$this->_success = false;
			}
		}		

		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}

	public function updateAction()
	{
		$this->_model2 = new MyIndo_Ext_ContentColumns();
		$this->_model3 = new MyIndo_Ext_ModelFields();
		$this->_model4 = new Application_Model_Shareprices();
		$this->_model5 = new Application_Model_SharepricesLog();
		$data = array(
				'data' => array()
		);

		try {
			$posts = $this->getRequest()->getRawBody();
			$posts = Zend_Json::decode($posts);
			if ($this->_model->isExistByKey('SHAREPRICES_NAME', $posts['data']['SHAREPRICES_NAME'])) {
				$this->_error_message = 'Edit failed';
				$this->_success = false;
			} else {
				$this->_model2->update(array(
						'TEXT' => $posts['data']['SHAREPRICES_NAME'],
						'DATAINDEX' => $posts['data']['SHAREPRICES_NAME']
				),
						$this->_model2
						->getAdapter()->quoteInto('CONTENT_COLUMN_ID = ?', $posts['data']['SHAREPRICES_NAME_ID']));
				
				$this->_model->update(array(
						'SHAREPRICES_NAME' => $posts['data']['SHAREPRICES_NAME']
				),
						$this->_model->getAdapter()->quoteInto('SHAREPRICES_NAME_ID = ?', $posts['data']['SHAREPRICES_NAME_ID']));
					
				$this->_model3->update(array(
						'NAME' => $posts['data']['SHAREPRICES_NAME']
				),
						$this->_model3->getAdapter()->quoteInto('MODEL_FIELD_ID = ?', $posts['data']['SHAREPRICES_NAME_ID']));
					
				$this->_model4->update(array(
						'SHAREPRICES_NAME' => $posts['data']['SHAREPRICES_NAME']
				),
						$this->_model->getAdapter()->quoteInto('SHAREPRICES_NAME_ID = ?', $posts['data']['SHAREPRICES_NAME_ID']));
					
				$this->_model4->update(array(
						'SHAREPRICES_NAME' => $posts['data']['SHAREPRICES_NAME']
				),
						$this->_model->getAdapter()->quoteInto('SHAREPRICES_NAME_ID = ?', $posts['data']['SHAREPRICES_NAME_ID']));
					
			}
			
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
	
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	public function destroyAction()
	{
		$this->_model = new MyIndo_Ext_ContentColumns();
		$this->_model2 = new MyIndo_Ext_ModelFields();
		$this->_model3 = new Application_Model_SharepricesName();
		$this->_modelSp = new Application_Model_Shareprices();

		$data = array(
				'data' => array()
		);
		try {
			$_q = $this->_modelSp->select()
			->from('SHAREPRICES', array('sum(value) as total'))
			->where('SHAREPRICES_NAME = ?', $this->_posts['SHAREPRICES_NAME']);
			$_x = $_q->query()->fetch();
				//->where('SHAREPRICES_NAME = ?', $this->_posts['SHAREPRICES_NAME']);
			if(isset($_x['total']) && $_x['total'] == 0) {
				// Delete
				$this->_modelSp->delete(
						$this->_modelSp->getAdapter()->quoteInto('SHAREPRICES_NAME = ?', $this->_posts['SHAREPRICES_NAME']));
				
				$this->_model3->delete(
						$this->_model3->getAdapter()->quoteInto(
								'SHAREPRICES_NAME_ID = ?', $this->_posts['SHAREPRICES_NAME_ID']
						));
				$this->_model2->delete(
						$this->_model2->getAdapter()->quoteInto(
								'NAME = ?',$this->_posts['SHAREPRICES_NAME']
						));
				$this->_model->delete(
						$this->_model->getAdapter()->quoteInto(
								'DATAINDEX = ?',$this->_posts['SHAREPRICES_NAME']
						));
			}else {
				$this->_error_code = 102;
				$this->_error_message = 'Delete failed, data is being used.';
				$this->_success = false;
			}
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
}