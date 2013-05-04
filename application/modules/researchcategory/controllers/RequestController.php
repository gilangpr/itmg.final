<?php 

class Researchcategory_RequestController extends Zend_Controller_Action
{
	protected $_model;
	protected $_limit;
	protected $_start;
	protected $_posts;
	protected $_error_code;
	protected $_error_message;
	protected $_success;
	protected $_data;
	protected $_pk;
	protected $_name;
	
	public function init()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_model = new Application_Model_ResearchReportCategory();
		
		if($this->getRequest()->isPost()) {
			$this->_posts = $this->getRequest()->getPost();
		} else {
			$this->_posts = array();
		}
		
		$this->_start = (isset($this->_posts['start'])) ? $this->_posts['start'] : 0;
		$this->_limit = (isset($this->_posts['limit'])) ? $this->_posts['limit'] : $this->view->default_limit;
		
		$this->_error_code = 0;
		$this->_error_message = '';
		$this->_success = true;
		
		$this->_data = array(
				'data' => array(
						'items' => array(),
						'totalCount' => 0
						)
				);
		
		$this->_pk = 'RESEARCH_REPORT_CATEGORY_ID';
		$this->_name = 'RESEARCH_REPORT_CATEGORY';
	}
	
	public function readAction()
	{
		if($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
			try {
				if(isset($this->_posts['all']) && $this->_posts['all'] == 1) {
					$this->_limit = $this->_model->count();
				}
				if(!isset($this->_posts['query']) || $this->_posts['query'] == '' || empty($this->_posts['query'])) {
					if(!isset($this->_posts['sort'])) {
						$list = $this->_model->getListLimit($this->_limit, $this->_start, $this->_name . ' ASC');
					} else {
						$sort = Zend_Json::decode($this->_posts['sort']);
						$q = $this->_model->select();
						$q->order($sort[0]['property'] . ' ' . $sort[0]['direction']);
						$q->limit($this->_limit, $this->_start);
						$list = $q->query()->fetchAll();
					}
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
		} else {
			$this->_error_code = 901;
			$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($this->_data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function updateAction()
	{
		if($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
			
			try {
				$data = $this->getRequest()->getRawBody();
				$data = Zend_Json::decode($data);
				if(isset($data['data'][$this->_pk])) {
					if($this->_model->isExistByKey($this->_pk, $data['data'][$this->_pk])) {
						$this->_model->update(array(
								$this->_name => $data['data'][$this->_name]
								),$this->_model->getAdapter()->quoteInto($this->_pk . ' = ?', $data['data'][$this->_pk]));
						$this->_data['data']['items'] = $this->_model->getDetailByKey($this->_pk, $data['data'][$this->_pk]);
					} else {
						$this->_error_code = 101;
						$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
						$this->_success = false;
					}
				} else {
					$this->_error_code = 901;
					$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
					$this->_success = false;
				}
			}catch (Exception $e) {
				$this->_error_code = $e->getCode();
				$this->_error_message = $e->getMessage();
				$this->_success = false;
			}
			
		} else {
			$this->_error_code = 901;
			$this->_error_message = 'Invalid Request.';
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($this->_data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function createAction()
	{
		if($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest() && isset($this->_posts[$this->_name])) {
			if(!$this->_model->isExistByKey($this->_name, $this->_posts[$this->_name])) {
				
				try {
					$this->_model->insert(array(
							$this->_name => $this->_posts[$this->_name],
							'CREATED_DATE' => date('Y-m-d H:i:s')
							));
				}catch(Exception $e) {
					$this->_error_code = $e->getCode();
					$this->_error_message = $e->getMessage();
					$this->_success = false;
				}
				
			} else {
				$this->_error_code = 201;
				$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
				$this->_success = false;
			}
		} else {
			$this->_error_code = 901;
			$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
			$this->_success = false;
		}
		
		MyIndo_Tools_Return::JSON($this->_data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function destroyAction()
	{
		if($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest() && isset($this->_posts[$this->_pk])) {
			if($this->_model->isExistByKey($this->_pk, $this->_posts[$this->_pk])) {
				
				try {
					$researchModel = new Application_Model_ResearchReports();
					$q = $researchModel->select()
					->where('RESEARCH_REPORT_CATEGORY_ID = ?', $this->_posts[$this->_pk]);
					$count = $q->query()->fetchAll();
					if(count($count) == 0) {
						$this->_model->delete($this->_model->getAdapter()->quoteInto($this->_pk . ' = ?', $this->_posts[$this->_pk]));
					} else {
						$this->_error_code = 202;
						$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
						$this->_success = false;
					}
				}catch(Exception $e) {
					$this->_error_code = $e->getCode();
					$this->_error_message = $e->getMessage();
					$this->_success = false;
				}
				
			} else {
				$this->_error_code = 101;
				$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
				$this->_success = false;
			}
		} else {
			$this->_error_code = 901;
			$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($this->_data, $this->_error_code, $this->_error_message, $this->_success);
	}
}