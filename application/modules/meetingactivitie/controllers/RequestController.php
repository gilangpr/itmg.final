<?php 

class Meetingactivitie_RequestController extends MyIndo_Controller_Action
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
		$this->_model = new Application_Model_Meetingactivitie();
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
	
	public function createAction()
	{
		$data = array(
			'data' => array()
		);
		
		try {
			// Insert Data :
 			$this->_model->insert(array(
 					'MEETING_EVENT' => $this->_posts['MEETING_EVENT'],
					'MEETING_DATE' => $this->_posts['MEETING_DATE'],
					'START_TIME' => $this->_posts['START_TIME'],
					'END_TIME' => $this->_posts['END_TIME'],
					'NOTES' => '',
 					'CREATED_DATE' => date('Y-m-d H:i:s')
 					));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	public function crAction(){
		
		$data = array(
			'data' => array()
		);
		try{
			$inModel = new Application_Model_Investors();
			$miModel = new Application_Model_Meetinginvestor();
			$in_id = $this->_getParam('id',0);
			$this->_model->insert(array(
				'MEETING_EVENT' => $this->_posts['MEETING_EVENT'],
				'MEETING_DATE' => $this->_posts['MEETING_DATE'],
				'START_TIME' => $this->_posts['START_TIME'],
				'END_TIME' => $this->_posts['END_TIME'],
				'NOTES' => '',
				'CREATED_DATE' => date('Y-m-d H:i:s')
			));
			$inModel->update(array(
 					'MODIFIED_DATE' => date('Y-m-d H:i:s')
 				),$inModel->getAdapter()->quoteInto('INVESTOR_ID = ?', $in_id));
			if($this->_model->isExistByKey('MEETING_EVENT',$this->_posts['MEETING_EVENT'])){
				$ma_id = $this->_model->getPkByKey('MEETING_EVENT',$this->_posts['MEETING_EVENT']);
				$miModel->insert(array(
					'MEETING_ACTIVITIE_ID' => $ma_id,
					'INVESTOR_ID' => $in_id,
				));
			}
			else{
				$this->_success = false;
				$this->_error_message = "MEETING EVENT Not Found";
			}

		}catch(Exception $e){
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);


	}
	
	public function readAction()
	{
		if($this->isPost() && $this->isAjax()) {
			if(isset($this->_posts['sort']) || isset($this->_posts['query'])) {
				if(isset($this->_posts['sort'])) {
					// Decode sort JSON :
					$sort = Zend_Json::decode($this->_posts['sort']);
				}
				// Query data
				$q = $this->_model->select();
				if(isset($this->_posts['sort'])) {
					if($sort[0]['property'] == 'MEETING_EVENT' || $sort[0]['property'] == 'MEETING_DATE') {
						$q->order($sort[0]['property'] . ' ' . $sort[0]['direction']);
					}
				}
				if(isset($this->_posts['query'])) {
					if(!empty($this->_posts['query']) && $this->_posts['query'] != '') {
						$q->where('MEETING_EVENT LIKE ?', '%' . $this->_posts['query'] . '%');
					}
				}
				// Count all data
				$rTotal = $q->query()->fetchAll();
				$totalCount = count($rTotal);
				
				// Fetch sorted & limit data
				$q->limit($this->_limit, $this->_start);
				$list = $q->query()->fetchAll();
				foreach($list as $k=>$d) {
					$list[$k]['MEETING_DATE'] = date("d-m-Y", strtotime($d['MEETING_DATE']));
				}
			} else {
				$list = $this->_model->getListLimit($this->_limit, $this->_start, 'MEETING_DATE DESC');
				$totalCount = $this->_model->count();
			}

			
			$modelInvestors = new Application_Model_Investors();
			$modelContacts = new Application_Model_Contacts();
			$modelParticipants = new Application_Model_Participant();
			$modelItmPart = new Application_Model_Itmparticipants();
			$modelActInv = new Application_Model_Meetinginvestor();
			$modelCntInv = new Application_Model_Meetingcontact();
			$modelItmInv = new Application_Model_Meetingparticipant();
			foreach($list as $k=>$d) {
				/* Get List Company */
				$qActInv = $modelActInv->select()->where('MEETING_ACTIVITIE_ID = ?', $d['MEETING_ACTIVITIE_ID']);
				$resActInv = $qActInv->query()->fetchAll();
				foreach($resActInv as $_k=>$_d) {
					$investors = $modelInvestors->getDetailByKey('INVESTOR_ID', $_d['INVESTOR_ID']);
					if($_k > 0) {
						$list[$k]['COMPANY_NAME'] .= ', ' . $investors['COMPANY_NAME'];
					} else {
						$list[$k]['COMPANY_NAME'] = $investors['COMPANY_NAME'];
					}
				}
				/* End of : Get List Company */
				
				/* Get List Contact */
				$qCntInv = $modelCntInv->select()->where('MEETING_ACTIVITIE_ID = ?', $d['MEETING_ACTIVITIE_ID']);
				$resCntInv = $qCntInv->query()->fetchAll();
				foreach($resCntInv as $_k=>$_d) {
					$contacts = $modelContacts->getDetailByKey('CONTACT_ID', $_d['CONTACT_ID']);
					if($_k > 0) {
						$list[$k]['NAME'] .= ', ' . $contacts['NAME'];
					} else {
						$list[$k]['NAME'] = $contacts['NAME'];
					}
				}
				/* End of : Get List Contact */

				/* Get List Participant */
				$qPart = $modelParticipants->select()->where('MEETING_ACTIVITIE_ID = ?', $d['MEETING_ACTIVITIE_ID']);
				$resPart = $qPart->query()->fetchAll();
				foreach($resPart as $_k=>$_d) {
					if($_k > 0) {
						$list[$k]['NAME'] .= ', ' . $_d['NAME'];
					} else {
						if(isset($list[$k]['NAME'])) {
							$list[$k]['NAME'] .= ', ' . $_d['NAME'];
						} else {
							$list[$k]['NAME'] = $_d['NAME'];
						}
					}
				}
				/* End of : Get List Participant */

				/* Get List ITM Participant */
				$qItmPart = $modelItmInv->select()->where('MEETING_ACTIVITIE_ID = ?', $d['MEETING_ACTIVITIE_ID']);
				$resItmPart = $qItmPart->query()->fetchAll();
				foreach($resItmPart as $_k=>$_d) {
					$itmParticipants = $modelItmPart->getDetailByKey('PARTICIPANT_ID', $_d['PARTICIPANT_ID']);
					if($_k > 0) {
						$list[$k]['INITIAL_PART'] .= ', ' . $itmParticipants['INITIAL_PART'];
					} else {
						$list[$k]['INITIAL_PART'] = $itmParticipants['INITIAL_PART'];
					}
				}
				/* End of : ITM Participant */
			}

			$this->_data['data']['items'] = $list;
			$this->_data['data']['totalCount'] = $totalCount;
		} else {
			$this->error(901);
		}
		$this->json();
	}

	public function readOldAction()
	{	
		if(isset($this->_posts['sort']) || isset($this->_posts['query'])) {
			if(isset($this->_posts['sort'])) {
				// Decode sort JSON :
				$sort = Zend_Json::decode($this->_posts['sort']);
			}
			// Query data
			$q = $this->_model->select();
			if(isset($this->_posts['sort'])) {
				if($sort[0]['property'] == 'MEETING_EVENT' || $sort[0]['property'] == 'MEETING_DATE') {
					$q->order($sort[0]['property'] . ' ' . $sort[0]['direction']);
				}
			}
			
			if(isset($this->_posts['query'])) {
				if(!empty($this->_posts['query']) && $this->_posts['query'] != '') {
					$q->where('MEETING_EVENT LIKE ?', '%' . $this->_posts['query'] . '%');
				}
			}
			// Count all data
			$rTotal = $q->query()->fetchAll();
			$totalCount = count($rTotal);
			
			// Fetch sorted & limit data
			$q->limit($this->_limit, $this->_start);
			$full = $q->query()->fetchAll();
		} else {
			$full = $this->_model->getListLimit($this->_limit, $this->_start, 'MEETING_DATE DESC');
		}
		$_temp = '';
		$_temp2 = '';
		$_i = 0;
		foreach ($full as $k=>$d) {
			if($_temp == '') {
				$_temp = $d['MEETING_ACTIVITIE_ID'];
				$this->_data['data']['items'][$_i]['COMPANY_NAME'] = '';
				$this->_data['data']['items'][$_i]['NAME'] = '';
				$this->_data['data']['items'][$_i]['INITIAL_PART'] = '';
			}
			if($_temp != $d['MEETING_ACTIVITIE_ID']) {
				$_i++;
				$_temp = $d['MEETING_ACTIVITIE_ID'];
				$this->_data['data']['items'][$_i]['COMPANY_NAME'] = '';
				$this->_data['data']['items'][$_i]['NAME'] = '';
				$this->_data['data']['items'][$_i]['INITIAL_PART'] = '';
			}
			if(!isset($this->_data['data']['items'][$_i]['MEETING_ACTIVITIE_ID'])) {
				$originalDate = $d['MEETING_DATE'];
				$newDate = date("d-m-Y", strtotime($originalDate));
				$this->_data['data']['items'][$_i]['MEETING_ACTIVITIE_ID'] = $d['MEETING_ACTIVITIE_ID'];				
				$this->_data['data']['items'][$_i]['MEETING_DATE'] = $newDate;
				$this->_data['data']['items'][$_i]['MEETING_EVENT'] = $d['MEETING_EVENT'];
				$this->_data['data']['items'][$_i]['NOTES'] = $d['NOTES'];
			}
			$meetingInvestor = new Application_Model_Meetinginvestor();
			$metId = $d['MEETING_ACTIVITIE_ID'];
			$companyName = $meetingInvestor->getCompanyName($metId);
			$Name = $meetingInvestor->getName($metId);
			//print_r($companyName);
			foreach ($companyName as $_k=>$_d) {		
				// Set Company Name :
				if($this->_data['data']['items'][$_i]['COMPANY_NAME'] != '') {
					$this->_data['data']['items'][$_i]['COMPANY_NAME'] .= ', ';
				} 
				
				$this->_data['data']['items'][$_i]['COMPANY_NAME'] .= $_d['COMPANY_NAME'];
			}
			foreach ($Name as $_j=>$_l) {
				// Set Company Name :
				if($this->_data['data']['items'][$_i]['NAME'] != '') {
					$this->_data['data']['items'][$_i]['NAME'] .= ', ';
				}
			
				$this->_data['data']['items'][$_i]['NAME'] .= $_l['NAME'];
			}
			$participant = new Application_Model_Participant();
			$partName = $participant->getName($metId);
			//print_r($partName);
			foreach ($partName as $n=>$m) {
				if ($this->_data['data']['items'][$_i]['NAME'] != '') {
					$this->_data['data']['items'][$_i]['NAME'] .= ', ';
				}
				$this->_data['data']['items'][$_i]['NAME'] .= $m['NAME'];
			}
			
			$meetingParticipant = new Application_Model_Meetingparticipant();
			$initialPart = $meetingParticipant->getInitial($metId);
			foreach ($initialPart as $_x=>$_y) {
				// Set Company Name :
				if($this->_data['data']['items'][$_i]['INITIAL_PART'] != '') {
					$this->_data['data']['items'][$_i]['INITIAL_PART'] .= ', ';
				}
				$this->_data['data']['items'][$_i]['INITIAL_PART'] .= $_y['INITIAL_PART'];
			}
			
		}
		
		$this->_data['data']['totalCount'] = $this->_model->count();
		
		MyIndo_Tools_Return::JSON($this->_data, $this->_error_code, $this->_error_message, $this->_success);

	}

	public function updateAction()
	{
		$data = array(
				'data' => array()
		);
		$data = $this->getRequest()->getRawBody();
		$data = Zend_Json::decode($data);
		$id = $data['data']['MEETING_ACTIVITIE_ID'];
		$date = $data['data']['MEETING_DATE'];
		$newDate = date("Y-m-d", strtotime($date));
		try {
			
			
			$this->_model->update(array(
					'MEETING_EVENT' => $data['data']['MEETING_EVENT'],
					'MEETING_DATE'=>$newDate
					),
					$this->_model->getAdapter()->quoteInto('MEETING_ACTIVITIE_ID = ?', $id));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	public function updatenotesAction(){
		
		$data = array(
			'data' => array()
		);
		//Call Model Investor
		$modelInvestors = new Application_Model_Investors();
		$maModel = new Application_Model_Meetingactivitie();
		try {
			// Insert Data :
			//get id params
			$ma_id = $this->_posts['id'];
			$in_id = $this->_posts['INVESTOR_ID'];
			if($maModel->isExistByKey('MEETING_ACTIVITIE_ID', $ma_id)) {
 				$this->_model->update(array(
					'NOTES' => $this->_posts['NOTES']),
 				$this->_model->getAdapter()->quoteInto('MEETING_ACTIVITIE_ID = ?', $ma_id));
 				$modelInvestors->update(array(
 					'MODIFIED_DATE' => date('Y-m-d H:i:s')
 				),$modelInvestors->getAdapter()->quoteInto('INVESTOR_ID = ?', $in_id));
			}
			else {
				$this->_error_code = 404;
				$this->_error_message = 'MEETING_ACTIVITIE_ID NOT FOUND';
				$this->_success = false;
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
		//$meetingAc_id = (isset($this->_posts['id'])) ? $this->_posts['id'] : 0;
		$data = array(
				'data' => array()
				);
		try {
			 //Delete
			$this->_model->delete(
 					$this->_model->getAdapter()->quoteInto(
 				'MEETING_ACTIVITIE_ID = ?', $this->_posts['MEETING_ACTIVITIE_ID']
 							));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
}
