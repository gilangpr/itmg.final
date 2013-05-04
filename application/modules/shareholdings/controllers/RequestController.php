<?php 

class Shareholdings_RequestController extends Zend_Controller_Action
{
	protected $_model;
	protected $_limit;
	protected $_start;
	protected $_offset;
	protected $_posts;
	protected $_error_code;
	protected $_error_message;
	protected $_success;
	protected $_data;
	
	public function init()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_model = new Application_Model_Shareholdings();
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
		//CHECK INVESTOR NAME EXIST OR NOT
		
		if($this->_model->isExistByKey('INVESTOR_NAME', $this->_posts['INVESTOR_NAME'])) {
			
			$this->_success = false;
			$this->_error_message = 'Investor Name already exist.';
		} else {
			try {
				$shareAmount = new Application_Model_ShareholdingAmounts();
				$status = new Application_Model_InvestorStatus();
				$SID = $status->getPkByKey('INVESTOR_STATUS', $this->_posts['INVESTOR_STATUS']); 					// Do insert query :
				$id = $this->_model->insert(array(
 					'INVESTOR_NAME'=> $this->_posts['INVESTOR_NAME'],
 					'INVESTOR_STATUS_ID'=> $SID,
 					'ACCOUNT_HOLDER'=> $this->_posts['ACCOUNT_HOLDER'],
 					'CREATED_DATE' => date('Y-m-d H:i:s')
 				));
				$shareAmount->insert(array(
						'SHAREHOLDING_ID'=> $id,
						'AMOUNT'=> 0,
						'CREATED_DATE' => date('Y-m-d H:i:s'),
						'DATE' => date('Y-m-d')
						));
 			}catch(Exception $e) {
 				$this->_error_code = $e->getCode();
 				$this->_error_message = $e->getMessage();
 				$this->_success = false;
 			}
		}

		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function amountAction()
	{
		$data = array(
				'data' => array()
		);
		
		try {		
			$shareholder_id = $this->_model->getValueByKey('INVESTOR_NAME', $this->_posts['INVESTOR_NAME'], 'SHAREHOLDING_ID');
			$table = new Application_Model_ShareholdingAmounts();
			$Amount = $table->getValueByKey('SHAREHOLDING_ID', $shareholder_id, 'AMOUNT');
			//print_r($Amount);print_r($shareholder_id);die;
			if ($Amount != 0) {	
				$table->insert(array(
						'SHAREHOLDING_ID' => $shareholder_id,
						'AMOUNT' => $this->_posts['AMOUNT'],
						'CREATED_DATE' => date('Y-m-d H:i:s'),
						'DATE' => $this->_posts['DATE']
				));
			} else {
				$table->update(array(
						'AMOUNT' => $this->_posts['AMOUNT'],
						'DATE' => $this->_posts['DATE']
				),$table->getAdapter()->quoteInto('SHAREHOLDING_ID = ?', $shareholder_id));
			}
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function updateAction()
	{

		$data = array(
				'data' => array()
		);

		$data = $this->getRequest()->getRawBody();//mengambil data json
		$data = Zend_Json::decode($data);//merubah data json menjadi array
		$id = $data['data']['SHAREHOLDING_ID'];
 		
		try {
			$status = new Application_Model_InvestorStatus();
			if(!$this->_model->isExistByKey('INVESTOR_NAME', $data['data']['INVESTOR_NAME'])){
				$val = $status->getPkByKey('INVESTOR_STATUS', $data['data']['INVESTOR_STATUS']);
				$this->_model->update(array(
						'INVESTOR_NAME' => $data['data']['INVESTOR_NAME'],
						'INVESTOR_STATUS_ID' => $val,
						'ACCOUNT_HOLDER' => $data['data']['ACCOUNT_HOLDER'],
				),$this->_model->getAdapter()->quoteInto('SHAREHOLDING_ID = ?', $id));
			} else {
				
				$this->_error_message = 'Data Being Used';
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
		
		if(!isset($this->_posts['sort'])) {
			$modelSA = new Application_Model_ShareholdingAmounts();
			$sort = Zend_Json::decode($this->_posts['sort']);
		    $list = $this->_model->getListInvestorsLimit($this->_limit, $this->_start, 'INVESTOR_NAME ASC');
	        $lastId = $this->_model->getLastId();
		    /* start sum amount */
	        $data = $modelSA->getTotal();
	        $jml = 0;
	        foreach ($data as $k => $e) {
	        	
	        	$shareholdingid = $data[$k]['SHAREHOLDING_ID'];
	        	$idmax = $modelSA->getIdamount( $e['SHAREHOLDING_ID']); //max date terakhir
	        	foreach ($idmax as $x => $l) {
	        		$amountid = $modelSA->getMaxAmounth($l,$shareholdingid); //value amounth
	        		$jml += $amountid['AMOUNT'];
	        	}
	        }
			foreach($list as $k=>$d) {	
			    $list[$k]['AMOUNT'] = $modelSA->getAmount($d['SHAREHOLDING_ID']);

			}
			$sum = 0;
			foreach($list as $k=>$d) {
				$sum += $d['AMOUNT'];
			}
	        
			foreach($list as $k=>$d) {
				if($sum > 0) {
	               $list[$k]['PERCENTAGE'] = number_format(($d['AMOUNT'] / $sum) * 100,2);
				}
			}
			
			$c = count($list);
			$list[$c]['SHAREHOLDING_ID'] = $lastId+1;
			$list[$c]['ACCOUNT_HOLDER'] = 'TOTAL';
			$list[$c]['AMOUNT'] = $jml;
			$list[$c]['PERCENTAGE'] = 100;		
			$c = count($list);

		 	$data = array(
		 			'data' => array(
		 					'items' => $list,
		 					'Total' => count($list),
		 					'totalCount' => $this->_model->count(),
		 			));
		 	
		 }  else {
				try {
					$modelSA = new Application_Model_ShareholdingAmounts();
					$sort = Zend_Json::decode($this->_posts['sort']);
					$q = $this->_model->select()
					->setIntegrityCheck(false)
					->from('SHAREHOLDINGS', array('*'))
					->join('INVESTOR_STATUS', 'INVESTOR_STATUS.INVESTOR_STATUS_ID = SHAREHOLDINGS.INVESTOR_STATUS_ID', array('INVESTOR_STATUS'))
					->join('SHAREHOLDING_AMOUNTS', 'SHAREHOLDING_AMOUNTS.SHAREHOLDING_ID = SHAREHOLDINGS.SHAREHOLDING_ID', array('AMOUNT'))
					->group('SHAREHOLDING_ID')
					->limit($this->_limit, $this->_start);

					if($sort[0]['property'] == 'INVESTOR_STATUS') {
						$q->order('INVESTOR_STATUS.' . $sort[0]['property'] . ' ' . $sort[0]['direction']);
					} else if($sort[0]['property'] == 'AMOUNT') {
						$q->order('SHAREHOLDING_AMOUNTS.' . $sort[0]['property'] . ' ' . $sort[0]['direction']);
					} else {
						$q->order('SHAREHOLDINGS.' . $sort[0]['property'] . ' ' .$sort[0]['direction']);
					}

					$lastId = $this->_model->getLastId();
					$data = $modelSA->getTotal();
					$jml = 0;
					foreach ($data as $k => $e) {
						 
						$shareholdingid = $data[$k]['SHAREHOLDING_ID'];
						$idmax = $modelSA->getIdamount( $e['SHAREHOLDING_ID']); 
						foreach ($idmax as $x => $l) {
							$amountid = $modelSA->getMaxAmounth($l,$shareholdingid);
							$jml += $amountid['AMOUNT'];
						}
					}
					$c = $q->query()->fetchAll();
					
					foreach($c as $k=>$d) {
						$c[$k]['AMOUNT'] = $modelSA->getAmount($d['SHAREHOLDING_ID']);
					}
					foreach($c as $k=>$d) {
						$c[$k]['DATE'] = $modelSA->getDate($d['SHAREHOLDING_ID']);
					}
		            foreach($c as $k=>$d) {
		            	if($jml > 0) {
		            		$c[$k]['PERCENTAGE'] = number_format(($d['AMOUNT'] / $jml) * 100,2);
		            	}
		            }

		            $t = count($c);
		            $c[$t]['SHAREHOLDING_ID'] = $lastId+1;
		            $c[$t]['ACCOUNT_HOLDER'] = 'TOTAL';
		            $c[$t]['AMOUNT'] = $jml;
		            $c[$t]['PERCENTAGE'] = 100;
		            $t = count($c);
		            $data = array(
		            		'data' => array(
		            				'items' => $c,
		            				'Total' => count($c),
		            				'totalCount' => $this->_model->count(),
		            		));
		            
				}catch(Exception $e) {
					echo $e->getMessage();
				}
				MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
		 }
	}
	
	public function destroyAction()
	{
		$data = array(
				'data' => array()
		);
		try {
			$delAmount = new Application_Model_ShareholdingAmounts();
			
			$id = $this->_posts['SHAREHOLDING_ID'];
			$where = $delAmount->getAdapter()->quoteInto('SHAREHOLDING_ID = ?', $id);
			$delAmount->delete($where);
			 			$this->_model->delete(
			 					$this->_model->getAdapter()->quoteInto(
			 							'SHAREHOLDING_ID = ?', $this->_posts['SHAREHOLDING_ID']
		 							));
			}
			catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function getListAmountAction()
	{

		$modelSA = new Application_Model_ShareholdingAmounts();
		$list = $modelSA->getListByKey('SHAREHOLDING_ID', $this->_posts['id']);
		
		$data = array(
				'data' => array(
						'items' => $list,
						'totalCount' => $this->_model->count()
				)
		);
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function desAction()
	{
		$data = array(
				'data' => array()
		);
		
		try {				
			$modelSA = new Application_Model_ShareholdingAmounts();
		    $id = $this->_posts['id'];
			$where = $modelSA->getAdapter()->quoteInto(
							'SHAREHOLDING_AMOUNT_ID = ?', $id
					);
			$modelSA->delete($where);
			
		} catch (Exception $e) {
			
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
	public function upamountAction()
	{
		$data = array(
				'data' => array()
		);

	    $models = new Application_Model_ShareholdingAmounts();
		$data = $this->getRequest()->getRawBody();//mengambil data json
		$data = Zend_Json::decode($data);//merubah data json menjadi array
		$id = $data['data']['SHAREHOLDING_AMOUNT_ID'];
	
		try {
			
			$models->update(array(
					'AMOUNT' => $data['data']['AMOUNT']
			),$models->getAdapter()->quoteInto('SHAREHOLDING_AMOUNT_ID = ?', $id));
			
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
	
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
   public function uploadAction()
	{
			
		$data = array(
				'data' => array()
		);
	try{
		$upload = new Zend_File_Transfer_Adapter_Http();	
		$upload->setDestination(APPLICATION_PATH . '/../public/upload');
		$upload->addValidator('Extension',false,'xls,xlsx');
        
		if ($upload->isValid()) {	
			$upload->receive();
			$fileInfo = $upload->getFileInfo();
			$filExt = explode('.', $fileInfo['FILE']['name']);
			$filExt = explode('_', $fileInfo['FILE']['name']);
			$date = explode('.', $filExt[2]);

			/* Get file extension */
			$filExt = explode('.',$fileInfo['FILE']['name']);
			$filExt = '.' . strtolower($filExt[count($filExt)-1]);
			/* End of : Get file extension */
	
			/* Rename file */
			$new_name = microtime() . $filExt ;
			rename($upload->getDestination() . '/' . $fileInfo['FILE']['name'], $upload->getDestination() . '/' . $new_name);
			/* End of : Rename file */
		//}
		
		try
		{
			$inputFileName = $upload->getDestination() . '/' . $new_name;
			$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($inputFileName);
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $inputFileType);
		    $objWriter->setPreCalculateFormulas(false);

			foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
				$worksheetTitle     =  $worksheet->getTitle();
				$highestRow         =  $objPHPExcel->setActiveSheetIndex(0)->getHighestRow();  
				$highestColumn      =  $objPHPExcel->setActiveSheetIndex(0)->getHighestColumn();
				$nrColumns = ord($highestColumn) - 64;
				
                $highestColumn++;
				for ($row = 2; $row < $highestRow + 1; $row++) {
					
					$val=array();
					for ($col = 'B'; $col != $highestColumn; $col++) {
						$val[] = $objPHPExcel->setActiveSheetIndex(0)->getCell($col . $row)->getValue();
					};
					
					/* START STATUS TABEL INPUT */	
					$status = new Application_Model_InvestorStatus();
					if(!is_null($val[0])){
						if (!$status->isExistByKey('INVESTOR_STATUS', $val[1])) {
							$Sid = $status->insert(array (
									'INVESTOR_STATUS' => $val[1],
									'CREATED_DATE' => date('Y-m-d H:i:s')
							));
						} else {
							$Sid = $status->getPkByKey('INVESTOR_STATUS', $val[1]);
							$status->update(array(
									'INVESTOR_STATUS' => $val[1]
							),$status->getAdapter()->quoteInto('INVESTOR_STATUS_ID = ?', $Sid));
						}
					}
					/* END EXECUTE */
					if(!is_null($val[0])){
// 					$query = $status->select()
// 					->where('INVESTOR_STATUS = ?', $val[1]);

					if(!$this->_model->isExistByKey('INVESTOR_NAME', strtoupper($val[0]))) {
						//if ($query->query()->rowCount() > 0) {
						$id = $this->_model->insert(array(
								'INVESTOR_NAME' => $val[0],
								'INVESTOR_STATUS_ID' => $Sid,
								'ACCOUNT_HOLDER' => $val[2],
								'CREATED_DATE' => date('Y-m-d H:i:s')
						));
					} else {
						$Uid = $status->getPkByKey('INVESTOR_STATUS', $val[1]);
						$id = $this->_model->getPkByKey('INVESTOR_NAME', $val[0]);
						$this->_model->update(array(
								'INVESTOR_STATUS_ID' => $Uid,
								'ACCOUNT_HOLDER' => $val[2]
						),$this->_model->getAdapter()->quoteInto('SHAREHOLDING_ID = ?', $id));
					//}
                    }
					}
					
					$modelAmount = new Application_Model_ShareholdingAmounts();
					
					if(!is_null($val[0])){
					$id = $this->_model->getPkByKey('INVESTOR_NAME', $val[0]);
					/*--Search And Update from Two Table--*/
					$query = $modelAmount->select()
					->where('SHAREHOLDING_ID = ?', $id)//id from table id
					->where('DATE = ?', $date[0]);
					$_x = $query->query()->fetchAll();
					if (count($_x) > 0) { //record amount sudah ada
						$replace = ',';
						$with = '';
						$amount = str_replace($replace, $with, $val[3]);
						$modelAmount->update(array(
								'AMOUNT' => $val[3]
								), array(
								$modelAmount->getAdapter()->quoteInto('SHAREHOLDING_ID = ?', $id),
								$modelAmount->getAdapter()->quoteInto('DATE = ?', $date[0])
								));
					} else {
						$modelAmount->insert(array(
								'SHAREHOLDING_ID' => $id,
								'AMOUNT' => $val[3],
								'CREATED_DATE' => date('Y-m-d H:i:s'),
								'DATE' => $date[0]
						));
					}
				}
				}
 			}
 			/* End of : Insert data from excel to database */
 			unlink($inputFileName);
		} catch (Exception $e) { 
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
		} else {
			$this->_error_code = 902;
			$this->_error_message = MyIndo_Tools_Error::getErrorMessage($this->_error_code);
			$this->_success = false;
		}
		}catch(Exception $e) {
			$this->_error_code = $e->getCode();
			$this->_error_message = $e->getMessage();
			$this->_success = false;
		}
	
		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
	}
	
 	public function searchAction()
 	{		
 		$modelSearch = new Application_Model_ShareholdingAmounts();
 		$start_date = explode('T',$this->_posts['START_DATE']);
 		$end_date = explode('T',$this->_posts['END_DATE']);
        
        
 		if(isset($this->_posts['INVESTOR_NAME'])) {
 			if($this->_model->isExistByKey('INVESTOR_NAME', $this->_posts['INVESTOR_NAME'])) {
 				$ID = $this->_model->getPkByKey('INVESTOR_NAME', $this->_posts['INVESTOR_NAME']);
 				
 		         $list = $modelSearch->select()
 		         ->setIntegrityCheck(false)
 		         ->from('SHAREHOLDING_AMOUNTS', array('*'))		         
 		         ->where('SHAREHOLDINGS.SHAREHOLDING_ID = ?', $ID)
 		         ->where('DATE >= ?',  $start_date[0])
 		         ->where('DATE <= ?',  $end_date[0])
 		         ->join('SHAREHOLDINGS','SHAREHOLDINGS.SHAREHOLDING_ID = SHAREHOLDING_AMOUNTS.SHAREHOLDING_ID', array('*'));
 		         
 			} else {
 				$list = $modelSearch->select()
 				->setIntegrityCheck(false)
 				->from('SHAREHOLDING_AMOUNTS', array('*'))
 				->where('DATE >= ?',  $start_date[0])
 				->where('DATE <= ?',  $end_date[0])
 		        ->join('SHAREHOLDINGS','SHAREHOLDINGS.SHAREHOLDING_ID = SHAREHOLDING_AMOUNTS.SHAREHOLDING_ID', array('*'));
 			}
 		}
 		         $list = $list->query()->fetchAll();
 		         $data = array(
 		         		'data' => array(
 		         				'items' => $list,
 		         				'totalCount' => count($list)
 		         		)
 		         );
 		         
 		         MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
 	}
 	
 	public function autocomAction() 
 	{
 		if ($this->_posts['query'] == '') {
 			
 			$data = array(
 					'data' => array(
 							'items' => $this->_model->getListLimit($this->_limit, $this->_start, 'INVESTOR_NAME ASC'),
 							'totalCount' => $this->_model->count()
 							)
 					);
 		} else {
 			$data = array(
 					'data' => array(
 							'items' => $this->_model->getAllLike($this->_posts['query'], $this->_limit, $this->_start),
 							'totalCount' => $this->_model->count()
 					)
 			);
 		}
 		
 		MyIndo_Tools_Return::JSON($data, $this->_error_code, $this->_error_message, $this->_success);
 	}
}