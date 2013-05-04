<?php 

class Locations_RequestController extends MyIndo_Controller_Action
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
		$this->_model = new Application_Model_Locations();
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
							$q->where('LOCATION LIKE ?', '%' . $this->_posts['query'] . '%');
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
				$this->_data['data']['items'] = $this->_model->getListLimit($this->_limit, $this->_start, 'LOCATION ASC');
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
			if(!$this->_model->isExistByKey('LOCATION', $this->_posts['LOCATION'])) {
				try {
					
					// Insert Data :
					$this->_model->insert(array(
							'LOCATION'=> $this->_posts['LOCATION'],
							'CREATED_DATE' => date('Y-m-d H:i:s')
					));
					
				}catch(Exception $e) {
					$this->_error_code = $e->getCode();
					$this->_error_message = $e->getMessage();
					$this->_success = false;
				}
			} else {
				$this->error(201);
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}
	public function updateAction()
	{
		try {
			$posts = $this->getRequest()->getRawBody();
			$posts = Zend_Json::decode($posts);
			
			$this->_model->update(array(
					'LOCATION' => $posts['data']['LOCATION']
					),
					$this->_model->getAdapter()->quoteInto('LOCATION_ID = ?', $posts['data']['LOCATION_ID']));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		$this->json();
	}
	
	public function destroyAction()
	{
		if($this->isPost() && $this->isAjax()) {
			$modInvestor = new Application_Model_Investors();
			if(!$modInvestor->isExistByKey('LOCATION_ID', $this->_posts['LOCATION_ID'])) {
				if($this->_model->isExistByKey('LOCATION_ID', $this->_posts['LOCATION_ID'])) {
					try {
						$this->_model->delete($this->_model->getAdapter()->quoteInto('LOCATION_ID = ?', $this->_posts['LOCATION_ID']));
					} catch(Exception $e) {
						$this->_error_code = $e->getCode();
						$this->_error_message = $e->getMessage();
						$this->_success = false;
					}
				}
			} else {
				$this->error(202);
			}
		} else {
			$this->error(901);
		}
		$this->json();
	}
}