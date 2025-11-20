<?php


namespace Beeralex\Favorite;


use Bitrix\Sale\Internals\FuserTable;
use Beeralex\Core\Helpers\FuserHelper;
use Beeralex\Core\Service\FuserService;

class EventHandlers
{
    public static function onSaleUserDelete($id)
    {
        // запоминаем пользователя fuser, который будет удален,
        // если он привязан к зарегистрированному пользователю

        $fuserRow = FuserTable::getRow([
            'filter' => ['ID' => $id]
        ]);

        if ($fuserRow['USER_ID']) {
            $GLOBALS['DELETED_FUSER_ROW'] = $fuserRow;
        }
    }


    /**
     * Восстанавливаем пользователя который был удален, чтобы сохранить привязку к его избранным товарам
     * Должен вызывается после всех обработчиков onUserLogin
     */
    public static function restoreDeletedFuser()
    {
        if (isset($GLOBALS['DELETED_FUSER_ROW'])) {
            FuserTable::add($GLOBALS['DELETED_FUSER_ROW']);
        }
    }


    public static function onUserLogin($newUserId)
    {
        $fuserService = service(FuserService::class);
        // переносим все избранные товары от незарегистрированного пользователя к текущему

        // Получаем fuserId неавторизованного пользователя
        $fromFuserId = $fuserService->getFuserIdFromSession();
        if ($fromFuserId <= 0) {
            return;
        }

        // Получаем fuserId пользователя под которым авторизуются
        $toFuserId = $fuserService->getFuserIdForUser($newUserId);
        if (!$toFuserId) {
            $toFuserId = $fuserService->addFuserForUser($newUserId);
        }

        if ($fromFuserId != $toFuserId) {
            Helper::copyFavoritesToFuser($fromFuserId, $toFuserId);
        }
    }
}
