<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

include(GetLangFileName(dirname(__FILE__) . "/", "/.description.php"));

$psTitle = "Payme.uz";
$psDescription = "<a href=\"https://www.payme.uz\" target=\"_blank\">https://www.payme.uz</a>";


$arPSCorrespondence = array( 

	'PAYME_CALLBACK_TIME' => array(
		'NAME'  => GetMessage('PAYME_CALLBACK'),
		'DESCR' => GetMessage('PAYME_CALLBACK'),
		'SORT'  => 1009,
		'VALUE' => array(
			'0'	 => array( 'NAME' => GetMessage('INSTANTLY'), 'VALUE' => '0'	),
			'15000' => array( 'NAME' => GetMessage('S15'),	   'VALUE' => '15000'),
			'30000' => array( 'NAME' => GetMessage('S30'),	   'VALUE' => '30000'),
			'60000' => array( 'NAME' => GetMessage('S60'),	   'VALUE' => '60000')),
		'TYPE'  => 'SELECT',
		'DEFAULT' => '0'
	),

	'PAYME_PRINT' => array(
		'NAME'  => GetMessage('PAYME_PRINT_CHECK'),
		'DESCR' => GetMessage('PAYME_PRINT_CHECK'),
		'SORT'  => 1008,
		'VALUE' => array(
			'no'  => array( 'NAME' => GetMessage('PAYME_NO'), 'VALUE' => 'no' ),
			'yes' => array( 'NAME' => GetMessage('PAYME_YES'),'VALUE' => 'yes')),
		'TYPE'  => 'SELECT',
		'DEFAULT' => 'no'
	),

	'ENDPOINT_URL' => array(
		'NAME'  => GetMessage('PAYME_END_POINT_URL'),
		'DESCR' => GetMessage('PAYME_END_POINT_URL'),
		'SORT'  => 1007,
		'VALUE' => '',
		'TYPE'  => 'VALUE',
		'DEFAULT' => array(
				'PROVIDER_VALUE' =>'www.' . $_SERVER['SERVER_NAME'] . '/personal/order/notification.php',
				'PROVIDER_KEY' => 'VALUE',
			)
	),

	'BACK_AFTER_PAYMENT_URL' => array(
		'NAME'  => GetMessage('SITE_BACK_URL'),
		'DESCR' => GetMessage('SITE_BACK_URL'),
		'SORT'  => 1007,
		'VALUE' => '',
		'TYPE'  => 'VALUE',
		'DEFAULT' => array(
				'PROVIDER_VALUE' => 'www.' . $_SERVER['SERVER_NAME'] . '/personal/order/',
				'PROVIDER_KEY' => 'VALUE',
			)
	),

	'CHECKOUT_URL_TEST' => array(
		'NAME'  => GetMessage('PAYME_CHECKOUT_URL_TEST'),
		'DESCR' => GetMessage('PAYME_CHECKOUT_URL_TEST'),
		'SORT'  => 1006,
		'VALUE' => '',
		'TYPE'  => 'VALUE',
		'DEFAULT' => array(
				'PROVIDER_VALUE' => 'https://test.paycom.uz',
				'PROVIDER_KEY' => 'VALUE',
			)
	),

	'CHECKOUT_URL' => array(
		'NAME'  => GetMessage('PAYME_CHECKOUT_URL'),
		'DESCR' => GetMessage('PAYME_CHECKOUT_URL'),
		'SORT'  => 1005,
		'VALUE' => '',
		'TYPE'  => 'VALUE',
		'DEFAULT' => array(
				'PROVIDER_VALUE' => 'https://checkout.paycom.uz',
				'PROVIDER_KEY' => 'VALUE',
			)
	),

	'PAYME_URL' => array(
		'NAME'  => GetMessage('PAYME_SERVER_URL'),
		'DESCR' => GetMessage('PAYME_SERVER_URL'),
		'SORT'  => 1004,
		'VALUE' => '',
		'TYPE'  => 'VALUE',
		'DEFAULT' => array(
				'PROVIDER_VALUE' => 'www.payme.uz',
				'PROVIDER_KEY' => 'VALUE',
			)
	),
	
	'TEST_MODE' => array(
		'NAME'  => GetMessage('PAYME_TEST_MODE'),
		'DESCR' => GetMessage('PAYME_TEST_MODE'),
		'SORT'  => 1003,
		'INPUT' => array(
				'TYPE' => 'Y/N'
			)
	),	

	'P_SECURE_KEY_TEST' => array(
		'NAME'  => GetMessage('PAYME_SECURE_KEY_TEST'),
		'DESCR' => GetMessage('PAYME_SECURE_KEY_TEST_DEF'),
		'SORT'  => 1002,
		'VALUE' => '',
		'TYPE'  => 'VALUE'
	),

	'P_SECURE_KEY' => array(
		'NAME'  => GetMessage('PAYME_SECURE_KEY'),
		'DESCR' => GetMessage('PAYME_SECURE_KEY_DEF'),
		'SORT'  => 1001,
		'VALUE' => '',
		'TYPE'  => 'VALUE'
	),

	'P_MERCHANT'  => array(
		'NAME'  => GetMessage('PAYME_MERCHANT'),
		'DESCR' => GetMessage('PAYME_MERCHANT_DEF'),
		'SORT'  => 1000,
		'VALUE' => '',
		'TYPE'  => 'VALUE'
	)
);
?>