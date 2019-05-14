<?
	if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

	if ( isset($arResult['ORDER_ID']) ) {

		$ORDER_ID = $arResult['ORDER_ID'];

	} else {

		$ORDER_ID = $_GET['ORDER_ID'];
	}

	// Get order
	$order_info = CSaleOrder::GetByID( filter_var($ORDER_ID, FILTER_SANITIZE_NUMBER_INT) );

	$t_currency="";
	     if( $order_info['CURRENCY'] == 'UZS') $t_currency = 860;
	else if( $order_info['CURRENCY'] == 'USD') $t_currency = 840;
	else if( $order_info['CURRENCY'] == 'RUB') $t_currency = 643;
	else if( $order_info['CURRENCY'] == 'EUR') $t_currency = 978;
	else									   $t_currency = 860;

	$formFields = array(

		'merchant'         => CSalePaySystemAction::GetParamValue("P_MERCHANT"),
		'callback'         => CSalePaySystemAction::GetParamValue("BACK_AFTER_PAYMENT_URL"),
		'callback_timeout' => CSalePaySystemAction::GetParamValue("PAYME_CALLBACK_TIME"),
		'currency'         => $t_currency,
		'detail'           => null, 
	    'account[order_id]'=> $order_info['ID'],
		'amount'           => $order_info['PRICE']*100
	);

	$oplataArgsArray = array();

	foreach ($formFields as $key => $value) {

		$oplataArgsArray[] = "<input type='hidden' name='$key' value='$value'/>";
	}

	$payme_gt_url=CSalePaySystemAction::GetParamValue("CHECKOUT_URL");

	if (CSalePaySystemAction::GetParamValue("TEST_MODE")=='Y') {

		$payme_gt_url=CSalePaySystemAction::GetParamValue("CHECKOUT_URL_TEST");
	}

	echo '<form action="'.$payme_gt_url.'" method="post" id="oplata_payment_form"> ' . implode('', $oplataArgsArray).
		 '</form>'.
		 '<script> setTimeout(function() { document.getElementById("oplata_payment_form").submit();}, 100); </script>';
