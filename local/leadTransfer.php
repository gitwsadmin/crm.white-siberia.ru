<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

Loader::includeModule('crm');

$leadStatuses = \CCrmStatus::GetStatusList('STATUS');

echo '<pre>';
print_r($leadStatuses);
echo '</pre>';

$arFilter = [
	'CHECK_PERMISSIONS' => 'N',
	'<DATE_CREATE' => new Bitrix\Main\Type\DateTime('31.05.2023 23:59:59'),
	'STATUS_ID' => 'NEW'
];

$res = CCrmLead::GetListEx([], $arFilter, false, false, ['ID', 'TITLE', 'STATUS_ID', 'DATE_CREATE']);

//$leadObject = new \CCrmLead(false);
$arUpdate = [
	'STATUS_ID' => '7'
];

$arLeads = [];
while($arLead = $res->fetch()){

    $arLeads[] = $arLead;

	//$updateResult = $leadObject->Update($arLead['ID'], $arUpdate, true, true, []);

}

echo '<pre>';
print_r($arLeads);
echo '</pre>';

