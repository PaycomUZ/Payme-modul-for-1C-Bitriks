<?php

class PaymeApi {

	private $errorInfo ="";
	private $errorCod =0;

	private $request_id=0;
	private $responceType=0;

	private $result =true;
	private $inputArray;
	private $lastTransaction;

	private $paymentSystemParams;
	private $paymentSystemId;

	public function construct() {}

	public function parseRequest() {

		if ( (!isset($this->inputArray)) || empty($this->inputArray) ) {

			$this->setErrorCod(-32700);

		} else {

			$parsingJsonError=false;

			switch (json_last_error()){

				case JSON_ERROR_NONE: break;
				default: $parsingJson=true; break;
			}

			if ($parsingJsonError) {

				$this->setErrorCod(-32700);

			} else {

				// Request ID
				if (!empty($this->inputArray['id']) ) {

					$this->request_id = $this->inputArray['id'];
				}

					 if ($_SERVER['REQUEST_METHOD']!='POST') $this->setErrorCod(-32300);
				else if(! isset($_SERVER['PHP_AUTH_USER']))  $this->setErrorCod(-32504,"����� ������");
				else if(! isset($_SERVER['PHP_AUTH_PW']))	 $this->setErrorCod(-32504,"������ ������");
			}
		}

		if ($this->result) {

			global $DB;
			$dbPaySystem = CSalePaySystemAction::GetList(Array("SORT"=>"ASC"), Array("ACTIVE" => "Y", "Name" => 'Payme.uz'), false, false, 	array("ID", "ACTION_FILE", "PARAMS"));

			while($ps = $dbPaySystem->Fetch()) {

				//echo print_r($ps,true);

				$this->paymentSystemParams = CSalePaySystemAction::UnSerializeParams($ps["PARAMS"]); 
				$this->paymentSystemId = $ps["ID"];
			}

			$merchantKey=""; 

			if ($this->paymentSystemParams['TEST_MODE']['VALUE']) {

				$merchantKey=html_entity_decode($this->paymentSystemParams['P_SECURE_KEY_TEST']['VALUE'] );

			} else {

				$merchantKey=html_entity_decode($this->paymentSystemParams['P_SECURE_KEY']['VALUE'] );
			}

			if( $merchantKey != html_entity_decode($_SERVER['PHP_AUTH_PW']) ) {

				$this->setErrorCod(-32504,"������������  ������");

			} else {

				if ( method_exists($this,"payme_".$this->inputArray['method'])) {

					$methodName="payme_".$this->inputArray['method'];
					$this->$methodName();

				} else {

					$this->setErrorCod(-32601, $this->inputArray['method'] );
				}
			}
		}
		$this->GenerateResponse();
	}

	public function payme_CheckPerformTransaction() {

		$order_id = $this->inputArray['params']['account']['order_id'];
 
		// ����� ������ �� order_id
		$order = CSaleOrder::GetByID(filter_var($order_id, FILTER_SANITIZE_NUMBER_INT));

		// ����� �� ������
		if (! $order ) {

			$this->setErrorCod(-31050,'order_id');

		// ����� ������
		} else {

			// ����� ���������� �� order_id
			$this->getLastTransactionForOrder($order_id);

			// ���������� ���
			if (! $this->lastTransaction ) {

				// �������� ��������� ������ 
				if ($order['PAYED']=='Y' ) {

					$this->setErrorCod(-31052, 'order_id');

				// ������ ����� ������ 	
				} else  if ( ($order['PRICE']*100) != $this->inputArray['params']['amount'] ) {

					$this->setErrorCod(-31001, 'order_id'); 

				// Allow true
				} else {

					$this->responceType=1;
				} 

			// ���������� ����������
			} else {

				$this->setErrorCod(-31051, 'order_id');
			}
		}
	}

	public function payme_CreateTransaction() {

		$order_id = $this->inputArray['params']['account']['order_id'];
 
		// ����� ������ �� order_id
		$order = CSaleOrder::GetByID(filter_var($order_id, FILTER_SANITIZE_NUMBER_INT));

		// ����� ���������� �� id
		$this->getLastTransaction($this->inputArray['params']['id']);

		// ���������� ����������
		if ($this->lastTransaction) {

			$paycom_time_integer=$this->datetime2timestamp($this->lastTransaction['create_time']) *1000;
			$paycom_time_integer=$paycom_time_integer+43200000;

			// �������� ��������� ������ 
			if ($order['PAYED']=='Y' ) {

				$this->setErrorCod(-31052, 'order_id');

			// �������� ��������� ����������
			} else if ($this->lastTransaction['state']!=1){

				$this->setErrorCod(-31008, 'order_id');

			// �������� ������� �������� ����������	
			} else if ($paycom_time_integer <= $this->timestamp2milliseconds(time())){

				// ������� reason = 4
				global $DB;
				$DB->Query("UPDATE payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE transaction_id = '".$DB->ForSql($this->lastTransaction['transaction_id']). "'" );

				CSaleOrder::PayOrder   ($this->lastTransaction['cms_order_id'], 'N');
				CSaleOrder::StatusOrder($this->lastTransaction['cms_order_id'], "N");
				CSaleOrder::CancelOrder($this->lastTransaction['cms_order_id'], "Y", '4');

				$this->getLastTransaction($this->inputArray['params']['id']);
				$this->responceType=2;

			// �� OK
			} else {

				$this->responceType=2;
			}

		// ���������� ���
		} else {

			// ����� �� ������
			if (! $order ) {

				$this->setErrorCod(-31050,'order_id');

			// ����� ������
			} else {

				// �������� ��������� ������ 
				if ($order['PAYED']=='Y' ) {

					$this->setErrorCod(-31052, 'order_id');

				// ������ ����� ������ 	
				} else  if ( ($order['PRICE']*100) != $this->inputArray['params']['amount'] ) {

					$this->setErrorCod(-31001, 'order_id');

				// ������ ���������� state=1
				} else {

					// ����� ���������� �� order_id
					$this->getLastTransactionForOrder($order_id);

					// ���������� ���
					if (! $this->lastTransaction ) {

						$this->SaveOrder(($order['PRICE']*100), $order['ORDER_ID'], $order['ID'],
										$this->inputArray['params']['time'],
										$this->timestamp2datetime($this->inputArray['params']['time'] ),
										$this->inputArray['params']['id'] 
										);

						$this->getLastTransactionForOrder($order_id);
						$this->responceType=2;

					// ���������� ����������
					} else {

						$this->setErrorCod(-31051, 'order_id');
					}
				}
			}
		}
	}

	public function payme_CheckTransaction() {

		// ����� ���������� �� id
		$this->getLastTransaction($this->inputArray['params']['id']);

		// ���������� ����������
		if ($this->lastTransaction) {

			$this->responceType=2; 

		// ���������� ���
		} else {

			$this->setErrorCod(-31003);
		}
	}

	public function payme_PerformTransaction() {

		// ����� ���������� �� id
		$this->getLastTransaction($this->inputArray['params']['id']);

		// ���������� ����������
		if ( $this->lastTransaction ) {

			// �������� ��������� ����������
			if ($this->lastTransaction['state']==1) {

				$paycom_time_integer=$this->datetime2timestamp($this->lastTransaction['create_time']) *1000;
				$paycom_time_integer=$paycom_time_integer+43200000;

				// �������� ������� �������� ����������	
				if( $paycom_time_integer <= $this->timestamp2milliseconds(time()) ) {

					// ������� reason = 4
					global $DB;
					$DB->Query("UPDATE payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE transaction_id = '".$DB->ForSql($this->lastTransaction['transaction_id']). "'" );

					CSaleOrder::PayOrder   ($this->lastTransaction['cms_order_id'], 'N');
					CSaleOrder::StatusOrder($this->lastTransaction['cms_order_id'], "N");
					CSaleOrder::CancelOrder($this->lastTransaction['cms_order_id'], "Y", '4');

				// �� Ok
				} else {

					// ������
					global $DB;
					$DB->Query("UPDATE payme_transactions SET state = 2, perform_time =NOW() WHERE transaction_id = '".$DB->ForSql($this->lastTransaction['transaction_id']). "'" );

					CSaleOrder::PayOrder   ($this->lastTransaction['cms_order_id'], 'Y');
					CSaleOrder::StatusOrder($this->lastTransaction['cms_order_id'], "P");
				}

				$this->responceType=2;
				$this->getLastTransaction($this->inputArray['params']['id']);

			// C�������� �� 1
			} else {

				// �������� ��������� ����������
				if ($this->lastTransaction['state']==2) {

					$this->responceType=2;

				// C�������� �� 2
				} else {

					$this->setErrorCod(-31008);
				}
			}

		// ���������� ���
		} else {

			$this->setErrorCod(-31003);
		}
	}

	public function payme_CancelTransaction() {

		// ����� ���������� �� id
		$this->getLastTransaction($this->inputArray['params']['id']);

		// ���������� ����������
		if ($this->lastTransaction) {

			$reasonCencel=filter_var($this->inputArray['params']['reason'], FILTER_SANITIZE_NUMBER_INT);
			
			// �������� ��������� ����������
			if ($this->lastTransaction['state']==1) {

				// ������� state = -1
				global $DB;
				$DB->Query("UPDATE payme_transactions SET state = -1, cancel_time =NOW(), reason=".$reasonCencel." WHERE transaction_id = '".$DB->ForSql($this->lastTransaction['transaction_id']). "'" );

				CSaleOrder::PayOrder   ($this->lastTransaction['cms_order_id'], 'N');
				CSaleOrder::StatusOrder($this->lastTransaction['cms_order_id'], "N");
				CSaleOrder::CancelOrder($this->lastTransaction['cms_order_id'], "Y", $this->inputArray['params']['reason']);

			// C�������� 2
			} else if ($this->lastTransaction['state']==2) {

				// ������� state = -2
				global $DB;
				$DB->Query("UPDATE payme_transactions SET state = -2, cancel_time =NOW(), reason=".$reasonCencel." WHERE transaction_id = '".$DB->ForSql($this->lastTransaction['transaction_id']). "'" );

				CSaleOrder::PayOrder   ($this->lastTransaction['cms_order_id'], 'N');
				CSaleOrder::StatusOrder($this->lastTransaction['cms_order_id'], "N");
				CSaleOrder::CancelOrder($this->lastTransaction['cms_order_id'], "Y", $reasonCencel);

			// C��������
			} else {

				// ������ �� ���� ������
			}

			$this->responceType=2;
			$this->getLastTransaction($this->inputArray['params']['id']);

		// ���������� ���
		} else {

			$this->setErrorCod(-31003);
		}
	}

	protected function getLastTransaction ($v_transaction_id ) {

		global $DB;
		$dbPaySystem = $DB->Query("SELECT * FROM payme_transactions WHERE paycom_transaction_id  = '".$DB->ForSql($v_transaction_id)."'");

		while($payme_transaction = $dbPaySystem->Fetch()) {

			$this->lastTransaction=$payme_transaction;
		}
	}

	protected function getLastTransactionForOrder($v_order_id ) {

		global $DB;
		$dbPaySystem = $DB->Query("SELECT * FROM payme_transactions WHERE cms_order_id  = '".$DB->ForSql($v_order_id)."'");

		while($payme_transaction = $dbPaySystem->Fetch()) {

			$this->lastTransaction=$payme_transaction;
		}
	}

	public function payme_ChangePassword() {

		$this->paymentSystemParams['P_SECURE_KEY_TEST']['VALUE'] = $this->inputArray['params']['password'];		

		$updRes = CSalePaySystemAction::update(

			$this->paymentSystemId,
			array("PARAMS" => CSalePaySystemAction::SerializeParams($this->paymentSystemParams)
		));

		$this->responceType=3;
	}

	public function payme_GetStatement() { 

		global $DB;
		$dbPaySystem = $DB->Query("SELECT 
							t.paycom_time,
							t.paycom_transaction_id,
							t.amount,
							t.order_id,
							t.create_time,
							t.perform_time,
							t.cancel_time,
							t.state,
							t.reason,
							t.receivers
					FROM  payme_transactions t 
					WHERE t.paycom_time_datetime>='".$this->timestamp2datetime($this->inputArray['params']['from'] )."' and 
						  t.paycom_time_datetime<='".$this->timestamp2datetime($this->inputArray['params']['to'] )  ."'
					ORDER BY t.paycom_time_datetime " );

		$responseArray = array();
		$transactions  = array();

		while($row = $dbPaySystem->Fetch()) {

			array_push($transactions,array(

				"id"		   => $row["paycom_transaction_id"],
				"time"		   => $row['paycom_time']  ,
				"amount"	   => $row["amount"],
				"account"	   => array("order_id" => $row["order_id"]),
				"create_time"  => (is_null($row['create_time']) ? null: $this->datetime2timestamp( $row['create_time']) ) ,
				"perform_time" => (is_null($row['perform_time'])? null: $this->datetime2timestamp( $row['perform_time'])) ,
				"cancel_time"  => (is_null($row['cancel_time']) ? null: $this->datetime2timestamp( $row['cancel_time']) ) ,
				"transaction"  => $row["order_id"],
				"state"		   => (int) $row['state'],
				"reason"	   => (is_null($row['reason'])?null:(int) $row['reason']) ,
				"receivers"	=> null
			)) ;
		}

		$responseArray['result'] = array( "transactions"=> $transactions );		

		// ������� �������� ������ � ���������� UTF-8. 
		// return json_encode($responseArray); 

		echo \Bitrix\Main\Web\Json::encode($responseArray);
	}

	public function GenerateResponse() {

		if ($this->errorCod==0) {

			if ($this->responceType==1) {

				$responseArray = array('result'=>array( 'allow' => true )); 

			} else if ($this->responceType==2) {

				$responseArray = array(); 
				$responseArray['id']	 = $this->request_id;
				$responseArray['result'] = array(

					"create_time"	=> $this->datetime2timestamp($this->lastTransaction['create_time']) *1000,
					"perform_time"  => $this->datetime2timestamp($this->lastTransaction['perform_time'])*1000,
					"cancel_time"   => $this->datetime2timestamp($this->lastTransaction['cancel_time']) *1000,
					"transaction"	=>  $this->lastTransaction['cms_order_id'], //$this->order_id,
					"state"			=> (int)$this->lastTransaction['state'],
					"reason"		=> (is_null($this->lastTransaction['reason'])?null:(int)$this->lastTransaction['reason'])
				);

			} else if ($this->responceType==3) {

				$responseArray = array('result'=>array( 'success' => true )); 
			}

		} else {

			$responseArray['id']	= $this->request_id;
			$responseArray['error'] = array (

				'code'   =>(int)$this->errorCod,
				'message'=> array(

					"ru"=>$this->getGenerateErrorText($this->errorCod,"ru"),
					"uz"=>$this->getGenerateErrorText($this->errorCod,"uz"),
					"en"=>$this->getGenerateErrorText($this->errorCod,"en"),
					"data" =>$this->errorInfo
			));
		}

		// ������� �������� ������ � ���������� UTF-8. 
		//echo json_encode($responseArray);

		echo \Bitrix\Main\Web\Json::encode($responseArray);
	}

	public function SaveOrder($amount,$orderId,$cmsOrderId,$paycomTime,$paycomTimeDatetime,$paycomTransactionId ) {

		global $DB;
		$dbPaySystem = $DB->Query("SELECT 
									t.state, 
									t.amount,
									t.order_id
								FROM  payme_transactions t 
								WHERE t.cms_order_id = '".$DB->ForSql((is_null( $cmsOrderId )? 0:$cmsOrderId ))."' and t.order_id =".(is_null( $orderId )? 0:$orderId). " and t.amount=".$amount);

		$transactionCnt=0;
		while($payme_transaction = $dbPaySystem->Fetch()) {

			$transactionCnt=$transactionCnt+1;
		}

		if ($transactionCnt == 0) {

			global $DB;
			$dbPaySystem = $DB->Query(

			 "INSERT INTO payme_transactions SET 
			 `create_time`=NOW(),
			 `amount`=".$amount.",
			 `state`=1,
			 `order_id`=".	 (is_null( $orderId )  ?  0:$orderId).",
			 `cms_order_id`='".$DB->ForSql((is_null( $cmsOrderId )? 0:$cmsOrderId ))."',
			 `paycom_time`='".			$DB->ForSql($paycomTime)."',
			 `paycom_time_datetime`='". $DB->ForSql($paycomTimeDatetime)."',
			 `paycom_transaction_id`='".$DB->ForSql($paycomTransactionId)."'
			 "
			);
		}
	}

	public function getGenerateErrorText($codeOfError,$codOfLang ){

		$listOfError=array ('-31001' => array(
										  "ru"=>'�������� �����.',
										  "uz"=>'�������� �����.',
										  "en"=>'�������� �����.'
										),
							'-31003' => array(
										  "ru"=>'���������� �� �������.',
										  "uz"=>'���������� �� �������.',
										  "en"=>'���������� �� �������.'
										),
							'-31008' => array(
										  "ru"=>'���������� ��������� ��������.',
										  "uz"=>'���������� ��������� ��������.',
										  "en"=>'���������� ��������� ��������.'
										),
							'-31050' => array(
										  "ru"=>'����� �� ������.',
										  "uz"=>'����� �� ������.',
										  "en"=>'����� �� ������.'
										),
							'-31051' => array(
										  "ru"=>'���������� ����������.',
										  "uz"=>'���������� ����������.',
										  "en"=>'���������� ����������.'
										),
							'-31052' => array(
											"ru"=>'����� ��� �������.',
											"uz"=>'����� ��� �������.',
											"en"=>'����� ��� �������.'
										),
										
							'-32300' => array(
										  "ru"=>'������ ��������� ���� ����� ������� �� POST.',
										  "uz"=>'������ ��������� ���� ����� ������� �� POST.',
										  "en"=>'������ ��������� ���� ����� ������� �� POST.'
										),
							'-32600' => array(
										  "ru"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������',
										  "uz"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������',
										  "en"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������'
										),
							'-32700' => array(
										  "ru"=>'������ �������� JSON.',
										  "uz"=>'������ �������� JSON.',
										  "en"=>'������ �������� JSON.'
										),
							'-32600' => array(
										  "ru"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������.',
										  "uz"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������.',
										  "en"=>'����������� ������������ ���� � RPC-������� ��� ��� ����� �� ������������� ������������.'
										),
							'-32601' => array(
										  "ru"=>'������������� ����� �� ������. � RPC-������� ��� �������������� ������ ���������� � ���� data.',
										  "uz"=>'������������� ����� �� ������. � RPC-������� ��� �������������� ������ ���������� � ���� data.',
										  "en"=>'������������� ����� �� ������. � RPC-������� ��� �������������� ������ ���������� � ���� data.'
										),
							'-32504' => array(
										  "ru"=>'������������ ���������� ��� ���������� ������.',
										  "uz"=>'������������ ���������� ��� ���������� ������.',
										  "en"=>'������������ ���������� ��� ���������� ������.'
										),
							'-32400' => array(
										  "ru"=>'��������� (���������� ������). ������ ������� ������������ � ������ ��������� �����: ������ ���� ������, ������ �������� �������, ��������������� ��������� � �.�.',
										  "uz"=>'��������� (���������� ������). ������ ������� ������������ � ������ ��������� �����: ������ ���� ������, ������ �������� �������, ��������������� ��������� � �.�.',
										  "en"=>'��������� (���������� ������). ������ ������� ������������ � ������ ��������� �����: ������ ���� ������, ������ �������� �������, ��������������� ��������� � �.�.'
										)
							);

		return $listOfError[$codeOfError][$codOfLang];
	}

	public function timestamp2datetime($timestamp){
		// if as milliseconds, convert to seconds
		if (strlen((string)$timestamp) == 13) {
			$timestamp = $this->timestamp2seconds($timestamp);
		}

		// convert to datetime string
		return date('Y-m-d H:i:s', $timestamp);
	}

	public function timestamp2seconds($timestamp) {
		// is it already as seconds
		if (strlen((string)$timestamp) == 10) {
			return $timestamp;
		}

		return floor(1 * $timestamp / 1000);
	}

	public function timestamp2milliseconds($timestamp) {
		// is it already as milliseconds
		if (strlen((string)$timestamp) == 13) {
			return $timestamp;
		}

		return $timestamp * 1000;
	}

	public function datetime2timestamp($datetime) {

		if ($datetime) {

			return strtotime($datetime);
		}

		return $datetime;
	}

	public function setErrorCod($cod_,$info=null) {

		$this->errorCod=$cod_;

		if ($info!=null) $this->errorInfo=$info;

		if ($cod_!=0) {

			$this->result=false;
		}
	}

	public function getInputArray() {

		return $this->inputArray;
	}

	public function setInputArray($i_Array) {

		$this->inputArray = json_decode($i_Array, true); 
	}
}
