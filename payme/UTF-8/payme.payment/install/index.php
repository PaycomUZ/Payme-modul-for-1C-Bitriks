<?php

IncludeModuleLangFile(__FILE__);

Class payme_payment extends CModule {

	const MODULE_ID = 'payme.payment';
	var $MODULE_ID = 'payme.payment';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	var $strError = '';

	function __construct() {

		$arModuleVersion = array();
		include(dirname(__FILE__) . "/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("PAYME_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("PAYME_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("PAYME_MODULE_NAME");
		$this->PARTNER_URI = "https://www.payme.uz";
	}

	function InstallEvents() {
		return true;
	}

	function UnInstallEvents() {
		return true;
	}

	function DoInstall() {

		global $APPLICATION;
		$this->InstallFiles();
		RegisterModule(self::MODULE_ID);
	}

	function DoUninstall() {

		global $APPLICATION;
		UnRegisterModule(self::MODULE_ID);
		$this->UnInstallFiles();
	}

	function UnInstallFiles() {

		DeleteDirFilesEx('/bitrix/modules/sale/payment/payme');
		DeleteDirFilesEx('/bitrix/php_interface/include/sale_payment/payme');
		DeleteDirFilesEx('/personal/order/notification.php');

		global $DB;
		$DB->Query("DROP TABLE IF EXISTS `payme_transactions`;");
		return true;
	}

	function InstallFiles($arParams = array()) {

		if (!is_dir($ipn_dir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/')) {
			mkdir($ipn_dir, 0755);
		}

		if (is_dir($source = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install')) {

			$this->copyDir($source . "/handler", $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/payment'); 
			$this->copyDir($source . "/handler", $ipn_dir);

			if (!is_dir($personal_dir = $_SERVER['DOCUMENT_ROOT'] . '/personal/')) {
				mkdir($personal_dir, 0755);
			}

			if (!is_dir($order_dir = $_SERVER['DOCUMENT_ROOT'] . '/personal/order/')) {
				mkdir($order_dir, 0755);
			}

			copy($source . "/notifications/notification.php", $order_dir . 'notification.php');
 
			global $DB;
			$DB->Query("
			CREATE TABLE IF NOT EXISTS `payme_transactions` (
			`transaction_id` bigint(11) NOT NULL AUTO_INCREMENT COMMENT 'идентификатор транзакции ',
			`paycom_transaction_id` char(25) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Номер или идентификатор транзакции в биллинге мерчанта. Формат строки определяется мерчантом.',
			`paycom_time` varchar(13) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`paycom_time_datetime` datetime DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`create_time` datetime NOT NULL COMMENT 'Время добавления транзакции в биллинге мерчанта.',
			`perform_time` datetime DEFAULT NULL COMMENT 'Время проведения транзакции в биллинге мерчанта',
			`cancel_time` datetime DEFAULT NULL COMMENT 'Время отмены транзакции в биллинге мерчанта.',
			`amount` int(11) NOT NULL COMMENT 'Сумма платежа в тийинах.',
			`state` int(11) NOT NULL DEFAULT '0' COMMENT 'Состояние транзакции',
			`reason` tinyint(2) DEFAULT NULL COMMENT 'причина отмены транзакции.',
			`receivers` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'JSON array of receivers',
			`order_id` bigint(20) NOT NULL COMMENT 'заказ',
			`cms_order_id` char(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'номер заказа CMS',
			`is_flag_test` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`transaction_id`),
			UNIQUE KEY `paycom_transaction_id` (`paycom_transaction_id`),
			UNIQUE KEY `order_id` (`order_id`,`paycom_transaction_id`),
			KEY `state` (`state`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;"); 
		}

		return true;
	}

	function copyDir($source, $destination) {

		if (is_dir($source)) {

			@mkdir($destination, 0755);
			$directory = dir($source);
			while (FALSE !== ($readdirectory = $directory->read())) {
				if ($readdirectory == '.' || $readdirectory == '..') continue;
				$PathDir = $source . '/' . $readdirectory;
				if (is_dir($PathDir)) {
					$this->copyDir($PathDir, $destination . '/' . $readdirectory);
					continue;
				}
				copy($PathDir, $destination . '/' . $readdirectory);
			}
			$directory->close();
		} else {
			copy($source, $destination);
		}
	}
}

?>
