<?php
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/bx_root.php");
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

	CModule::IncludeModule('sale');

	header('Content-type: application/json charset=cp-1251');

	include(dirname(__FILE__) . "/api/PaymeApi.php");

	$api = new PaymeApi();
	$api->setInputArray(file_get_contents("php://input"));
	$api->parseRequest();
?>