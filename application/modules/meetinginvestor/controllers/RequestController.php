<?php 

class Meetinginvestor_RequestController extends Zend_Controller_Action
{
	protected $_model;
	protected $_limit;
	protected $_start;
	protected $_posts;
	protected $_error_code;
	protected $_error_message;
	protected $_success;
	
	public function init()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_model = new Application_Model_Meetinginvestor();
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
	}
	
	public function createAction()
	{
		$data = array(
			'data' => array()
		);
		//Call Model Investor
		$inModel = new Application_Model_Investors();
		try {
			// Insert Data :
			//get id params
			$in_id = $this->_getParam('id',0);
			if($inModel->isExistByKey('INVESTOR_ID', $in_id)) {
 			$this->_model->insert(array(
					'INVESTOR_ID'=>$in_id,
					'MEETING_ACTIVITIE_ID'=>$this->_posts['MEETING_ACTIVITIE_ID']
 					));
 			$inModel->update(array(
 					'MODIFIED_DATE' => date('Y-m-d H:i:s')
 				),$inModel->getAdapter()->quoteInto('INVESTOR_ID = ?', $in_id));
			}
			else {
				$this->_error_code = 404;
				$this->_error_message = 'INVESTOR_ID NOT FOUND';
				$this->_success = false;
			}
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	public function readAction()
	{	
		/*
		$in_id = (isset($this->_posts['id'])) ? $this->_posts['id'] : 0;
		$data = array(
				'data' => array(
				'items' => $this->_model->getListMeetinginvestorLimit($this->_limit, $this->_start,$in_id),
						'totalCount' => $this->_model->count()
				)
		);
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
		
		$in_id = (isset($this->_posts['id'])) ? $this->_posts['id'] : 0;
		$list = $this->_model->getListMeetinginvestorLimit($this->_limit, $this->_start,$in_id);

		$_temp = '';
		$_temp2 = '';
		$_i = 0;
		foreach($list as $k=>$d) {
			if($_temp == '') {
				$_temp = $d['MEETING_EVENT'];
				//$this->_data['data']['items'][$_i]['IDS'] = '';
			}
			if($_temp != $d['MEETING_EVENT']) {
				$_i++;
				$_temp = $d['MEETING_EVENT'];
				//$this->_data['data']['items'][$_i]['IDS'] = '';
			}
			if(!isset($this->_data['data']['items'][$_i]['MEETING_EVENT'])) {
				$this->_data['data']['items'][$_i]['MEETING_EVENT'] = $d['MEETING_EVENT'];
				$this->_data['data']['items'][$_i]['MEETING_DATE'] = $d['MEETING_DATE'];
				$this->_data['data']['items'][$_i]['NAME'] = $d['NAME'];
				$this->_data['data']['items'][$_i]['MEETING_ACTIVITIE_ID'] = $d['MEETING_ACTIVITIE_ID'];
				$this->_data['data']['items'][$_i]['INVESTOR_ID'] = $d['INVESTOR_ID'];
			}
			//$this->_data['data']['items'][$_i][$d['NAME']] = $d['NAME'];

			// Set Ids :
			if($this->_data['data']['items'][$_i]['NAME'] != $d['NAME']) {
				$this->_data['data']['items'][$_i]['NAME'] .= ','.$d['NAME'].',';
			}
			//$this->_data['data']['items'][$_i]['IDS'] .= $d['NAME'] . '_' . $d['MEETING_ACTIVITIE_ID'];
			//$this->_data['data']['items'][$_i]['NAME'] .= $d['NAME'].',';
		}*/
		$in_id = (isset($this->_posts['id'])) ? $this->_posts['id'] : 0;
		$full = $this->_model->getListMeetinginvestorLimit($this->_limit, $this->_start,$in_id,'MEETING_DATE ASC');

		$_temp = '';
		$_temp2 = '';
		$_i = 0;
		foreach ($full as $k=>$d) {
            if($_temp == '') {
                $_temp = $d['MEETING_ACTIVITIE_ID'];
                $this->_data['data']['items'][$_i]['NAME'] = '';
                $this->_data['data']['items'][$_i]['INITIAL_PART'] = '';
            }
            if($_temp != $d['MEETING_ACTIVITIE_ID']) {
                $_i++;
                $_temp = $d['MEETING_ACTIVITIE_ID'];
                $this->_data['data']['items'][$_i]['NAME'] = '';
                $this->_data['data']['items'][$_i]['INITIAL_PART'] = '';
            }
            if(!isset($this->_data['data']['items'][$_i]['MEETING_ACTIVITIE_ID'])) {
                //$originalDate = $d['MEETING_DATE'];
                //$newDate = date("d-m-Y", strtotime($originalDate));
                $this->_data['data']['items'][$_i]['MEETING_ACTIVITIE_ID'] = $d['MEETING_ACTIVITIE_ID'];
                $this->_data['data']['items'][$_i]['MEETING_DATE'] = $d['MEETING_DATE'];
                $this->_data['data']['items'][$_i]['MEETING_EVENT'] = $d['MEETING_EVENT'];
                $this->_data['data']['items'][$_i]['INVESTOR_ID'] = $d['INVESTOR_ID'];
                $this->_data['data']['items'][$_i]['NOTES'] = $d['NOTES'];
            }
            $Meetingcontact = new Application_Model_Meetingcontact();
            $In_Id = $d['INVESTOR_ID'];
            $Meet_Id = $d['MEETING_ACTIVITIE_ID'];
            $name = $Meetingcontact->getContactName($In_Id,$Meet_Id);
            foreach ($name as $_k=>$_d) {
                // Set Company Name :
                if($this->_data['data']['items'][$_i]['NAME'] != '') {
                    $this->_data['data']['items'][$_i]['NAME'] .= ', ';
                }
                $this->_data['data']['items'][$_i]['NAME'] .= $_d['NAME'];
            }
           
            $meetingParticipant = new Application_Model_Meetingparticipant();
            $initialPart = $meetingParticipant->getInitial($Meet_Id);
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
		try {
			
			
			$this->_model->update(array(
					'MEETING_EVENT' => $data['data']['MEETING_EVENT'],
					'MEETING_DATE'=>$data['data']['MEETING_DATE'],
					'START_TIME'=>$data['data']['START_TIME'],
					'END_TIME'=>$data['data']['END_TIME']
					),
					$this->_model->getAdapter()->quoteInto('MEETING_ACTIVITIE_ID = ?', $id));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function destroyAction()
	{	
		$in_id = (isset($this->_posts['INVESTOR_ID'])) ? $this->_posts['INVESTOR_ID'] : 0;
		$ma_id = (isset($this->_posts['MEETING_ACTIVITIE_ID'])) ? $this->_posts['MEETING_ACTIVITIE_ID'] : 0;
		$data = array(
				'data' => array()
				);
		$modelInvestors = new Application_Model_Investors();
		try {
			 //Delete
			$where=array();
			$where[]= $this->_model->getAdapter()->quoteInto('INVESTOR_ID = ?', $in_id);
			$where[]= $this->_model->getAdapter()->quoteInto('MEETING_ACTIVITIE_ID = ?', $ma_id);
			$this->_model->delete($where);
			$modelInvestors->update(array(
 					'MODIFIED_DATE' => date('Y-m-d H:i:s')
 				),$modelInvestors->getAdapter()->quoteInto('INVESTOR_ID = ?', $in_id));
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
}
