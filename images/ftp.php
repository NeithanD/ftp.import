<?php

$_SERVER["DOCUMENT_ROOT"] = '*********';
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
set_time_limit(0);
require($_SERVER["DOCUMENT_ROOT"].'/testimport/ImagesImport.php');
$arConfig = [
    'iblockId' => '14',
    'siteId' => 's1',
    'ftpHost' => '*********',
    'ftpLogin' => '*********',
    'ftpPasswd' => '*********',
	'ftpPath' => '*********',
    'tmpDir' => $_SERVER["DOCUMENT_ROOT"] . "/testimport/images/tmp/"
];

$handler = new FTP\Import\ImagesImport($arConfig);
$handler->runListener();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php"); 