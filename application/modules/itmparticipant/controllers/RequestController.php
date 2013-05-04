<?php 

class Itmparticipant_RequestController extends MyIndo_Controller_Action
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
		$this->_model = new Application_Model_Itmparticipants();
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
		if($this->isAjax() && $this->isPost()) {
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
							$q->where('NAME_PART LIKE ?', '%' . $this->_posts['query'] . '%');
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
			try {
				// Insert Data :
				$this->_model->insert(array(
						'NAME_PART'=>$this->_posts['NAME_PART'],
						'PHONE_PART1'=>$this->_posts['PHONE_PART1'],
						'PHONE_PART2'=>$this->_posts['PHONE_PART2'],
						'EMAIL_PART'=>$this->_posts['EMAIL_PART'],
						'ADDRESS_PART'=>$this->_posts['ADDRESS_PART'],
						'SEX_PART'=>$this->_posts['SEX_PART'],
						'INITIAL_PART' => $this->_posts['INITIAL_PART'],
						'POSITION_PART'=>$this->_posts['POSITION_PART'],
	 					'CREATED_DATE' => date('Y-m-d H:i:s')
				));
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
	 			$this->_model->delete(
	 					$this->_model->getAdapter()->quoteInto(
	 							'PARTICIPANT_ID = ?', $this->_posts['PARTICIPANT_ID']
	 							));
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
	public function updateAction() 
	{
		if($this->isPost() && $this->isAjax()) {
		    $data = $this->getRequest()->getRawBody();//mengambil data json
			$data = Zend_Json::decode($data);//merubah data json menjadi array
			try {
				
				$this->_model->update(array(
						'NAME_PART' => $data['data']['NAME_PART'],
						'POSITION_PART' => $data['data']['POSITION_PART'],
						'EMAIL_PART'=>$data['data']['EMAIL_PART'],
						'PHONE_PART1'=>$data['data']['PHONE_PART1'],
						'PHONE_PART2'=>$data['data']['PHONE_PART2'],
						'ADDRESS_PART'=>$data['data']['ADDRESS_PART'],
						'INITIAL_PART'=>$data['data']['INITIAL_PART']
				),$this->_model->getAdapter()->quoteInto('PARTICIPANT_ID = ?', $data['data']['PARTICIPANT_ID']));
				
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
}