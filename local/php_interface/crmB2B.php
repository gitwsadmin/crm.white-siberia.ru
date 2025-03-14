<?php

/**
 *
 * @param $dealId
 * @return mixed
 */
function getOrderIdOnDealTitle($dealId) {
    if (!\Bitrix\Main\Loader::includeModule("crm")) {
        LogTG("Ошибка: модуль CRM не загружен");
        return false;
    }
    $deal = \CCrmDeal::GetByID($dealId, false);
    if (preg_match('/№(\d+)/', $deal['TITLE'], $matches)) {
        return $matches[1];
    } else {
//        LogTG($deal);
        return false;
    }
}


function getOrderById($orderId) {

    \Bitrix\Main\Loader::includeModule("sale");

    $order = \Bitrix\Sale\Order::load($orderId);

    if (!$order) {
        return null; // Если заказ не найден
    }

    // Получаем основные данные заказа
    $orderData = [
        'SITE_ID' => $order->getSiteId(), // ID сайта, где создан заказ
    ];

    $propertyCollection = $order->getPropertyCollection();
    foreach ($propertyCollection as $property) {
        $orderData['PROPERTIES'][$property->getField("CODE")] = $property->getValue();
    }

    return $orderData;
}


function updateDealPipelineAndStage($dealId, $newCategoryId, $newStageId = 'C1:NEW', $newAssignedById = 12738) {

    global $USER;
    $USER->Authorize(331); // Авторизация от имени администратора

    if (!\Bitrix\Main\Loader::includeModule("crm")) {
        LogTG("Ошибка: модуль CRM не загружен.");
        return false;
    }

    // Получаем фабрику сделок
    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

    if (!$factory) {
        LogTG("Ошибка: не удалось получить фабрику сделок.");
        return;
    }

    $item = $factory->getItem($dealId);
    if (!$item) {
        LogTG("Сделка с ID {$dealId} не найдена.");
        return;
    }

    // Меняем воронку (CATEGORY_ID)
    $item->setCategoryId($newCategoryId);

    // Меняем стадию (STAGE_ID)
    $item->setStageId($newStageId);

    // Меняем ответственного (ASSIGNED_BY_ID)
    $item->setAssignedById($newAssignedById);

    // Сохраняем изменения
    $operation = $factory->getUpdateOperation($item);
    $result = $operation->launch();

    if ($result->isSuccess()) {
        LogTG("Сделка ID {$dealId} успешно обновлена: воронка изменена на {$newCategoryId}, стадия изменена на {$newStageId}, ответственный изменен на {$newAssignedById}.");
    } else {
        LogTG("Ошибка при обновлении сделки ID {$dealId}: " . implode(", ", $result->getErrorMessages()));
    }
}

AddEventHandler('crm', 'OnAfterCrmDealAdd', 'onAfterDealAdd');

function onAfterDealAdd($event) {
    // Проверяем, что передано: объект `Bitrix\Main\Event` или массив
    if ($event instanceof \Bitrix\Main\Event) {
        $dealFields = $event->getParameters(); // Новый формат (объект события)
    } elseif (is_array($event)) {
        $dealFields = $event; // Старый формат (массив)
    } else {
        LogTG("Ошибка: неизвестный формат данных");
        return;
    }

    // Получаем ID созданной сделки
    $dealId = $dealFields['ID'] ?? null;

    if (!$dealId) {
        LogTG('Ошибка: ID сделки не найден.');
        return;
    }

    // Получаем ID заказа
    $orderId = getOrderIdOnDealTitle($dealId);

    if (!$orderId) {
        LogTG("Сделка ID {$dealId} не связана с заказом.");
        return;
    }

    // Получаем данные заказа
    $orderData = getOrderById($orderId);

    // Проверяем, на каком сайте был создан заказ
    if (isset($orderData['SITE_ID']) && $orderData['SITE_ID'] == "b2") {
        $result = updateDealPipelineAndStage($dealId, 1);
    }
}

function checkAndMoveRecentDeals() {
//    LogTG('start checkAndMoveRecentDeals');

    if (!\Bitrix\Main\Loader::includeModule("crm")) {
        LogTG("Ошибка: модуль CRM не загружен.");
        return false;
    }

    $date = new \Bitrix\Main\Type\DateTime();
    $date->add("-7 minutes");

    $deals = \Bitrix\Crm\DealTable::getList([
        'filter' => [
            '>=DATE_CREATE' => $date
        ],
        'select' => ['ID', 'TITLE', 'CATEGORY_ID']
    ]);

    $processed = false; // Флаг успешной работы

    while ($deal = $deals->fetch()) {
        $dealId = $deal['ID'];
        $orderId = getOrderIdOnDealTitle($dealId);

        if (!$orderId) {
            LogTG("Сделка ID {$dealId} не связана с заказом.");
            continue;
        }

        $orderData = getOrderById($orderId);

        if ($orderData['SITE_ID'] == "b2" && $deal['CATEGORY_ID'] != 1) {
            updateDealPipelineAndStage($dealId, 1);
            $processed = true;
        }
    }
//    LogTG('end checkAndMoveRecentDeals');
    return $processed; // Возвращает true, если хоть одна сделка обработана
}