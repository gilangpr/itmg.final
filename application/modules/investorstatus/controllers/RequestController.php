<?php 

class Investorstatus_RequestController extends MyIndo_Controller_Action
{
	protected $_model;
	protected $_limit;
	protected $_start;
	protected $_posts;
	protected $_error_code;
	protected $_error_message;
	protected $_success;
	protected $_data;
	
	public function init()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_model = new Application_Model_InvestorStatus();
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
	
	public function  readAction() 
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
							$q->where('INVESTOR_STATUS LIKE ?', '%' . $this->_posts['query'] . '%');
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
				$this->_data['data']['items'] = $this->_model->getListLimit($this->_limit, $this->_start);
				$this->_data['data']['totalCount'] = $this->_model->count();
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}	
		
	public function createAction()
	{
		if($this->isPost() && $this->isAjax()) {
			if($this->_model->isExistByKey('INVESTOR_STATUS', $this->_posts['INVESTOR_STATUS'])) {
				$this->error(201);
			} else {
				try {
					// Do insert query :
					$this->_model->insert(array(
							'INVESTOR_STATUS'=> $this->_posts['INVESTOR_STATUS'],
							'CREATED_DATE' => date('Y-m-d H:i:s')
					));
				
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
		
	public function updateAction()
	{
		if($this->isPost() && $this->isAjax()) {
			$data = $this->getRequest()->getRawBody();//mengambil data json
			$data = Zend_Json::decode($data);//merubah data json menjadi array
			$id = $data['data']['INVESTOR_STATUS_ID'];
		
			try {
				if(!$this->_model->isExistByKey('INVESTOR_STATUS', $data['data']['INVESTOR_STATUS'])){
					$this->_model->update(array(
						'INVESTOR_STATUS' => $data['data']['INVESTOR_STATUS'],
					),$this->_model->getAdapter()->quoteInto('INVESTOR_STATUS_ID = ?', $id));
				} else {
					$this->error(202);
				}
			}catch(Exception $e) {
				$this->_error_code = $e->getCode();
				$this->_error_message = $e->getMessage();
				$this->_success = false;
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}
		
	public function destroyAction()
	{
		if($this->isPost() && $this->isAjax()) {
			try {
				// Delete
				$share = new Application_Model_Shareholdings();
				$val = $this->_model->getValueByKey('INVESTOR_STATUS_ID', $this->_posts['INVESTOR_STATUS_ID'], 'INVESTOR_STATUS');
				$query = $share->select()
				->where('INVESTOR_STATUS_ID = ?', $this->_posts['INVESTOR_STATUS_ID']);
				
				$total = $query->query()->fetchAll();
				if (count($total) > 0) {
		        	$this->error(202);
		        } else {
		        	$this->_model->delete(
		            $this->_model->getAdapter()->quoteInto(
		            	'INVESTOR_STATUS_ID = ?', $this->_posts['INVESTOR_STATUS_ID']
		            ));
		        	$this->_error_message = 'Data successfully deleted.';
		        	$this->_success = false;
				}
			} catch(Exception $e) {
				$this->_error_code = $e->getCode();
				$this->_error_message = $e->getMessage();
				$this->_success = false;
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}
		
	public function  autocomAction() 
	{
		if ($this->_posts['query'] == '') {
	 		$this->_data = array(
	 			'data' => array(
	 				'items' => $this->_model->getListLimit($this->_limit, $this->_start, 'INVESTOR_STATUS ASC'),
	 				'totalCount' => $this->_model->count()
	 			)
	 		);
 		} else {
			$this->_data = array(
				'data' => array(
					'items' => $this->_model->getAllLike($this->_posts['query'], $this->_limit, $this->_start),
					'totalCount' => $this->_model->count()
				)
			);
		}
		$this->json();
	}	
}