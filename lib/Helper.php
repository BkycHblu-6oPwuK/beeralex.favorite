<?

namespace Beeralex\Favorite;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;

Loader::includeModule('sale');
Loader::includeModule('iblock');

class Helper
{
    /**
     * Добавляет товар в избранное.
     *
     * @param int $productID
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return bool true, если товар был добавлен в избранное сейчас либо ранее.
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    public static function add(int $productID, int $fUserID = 0): bool
    {
        if ($productID < 1) {
            return false;
        }

        $fUserID = static::checkFuser($fUserID);

        $rsUserProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=PRODUCT_ID' => $productID,
            ],
            'limit'  => 1,
        ]);

        if ($rsUserProducts->fetch()) {
            return true;
        }

        $rsFave = FavoriteTable::add([
            'FUSER_ID'   => $fUserID,
            'PRODUCT_ID' => $productID,
        ]);

        return $rsFave->isSuccess();
    }

    /**
     * Удаляет товар из избранного.
     *
     * @param int|array $productID
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return bool true, если товар был удалён из избранного сейчас либо его там не было.
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    public static function deleteByProductID(int|array $productID, int $fUserID = 0): bool
    {
        $fUserID = static::checkFuser($fUserID);
        $rsProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=PRODUCT_ID' => $productID,
            ],
            'limit'  => 1,
        ]);

        $result = true;
        while ($arProduct = $rsProducts->fetch()) {
            $res = FavoriteTable::delete($arProduct['ID']);
            $result &= $res->isSuccess();
        }

        return $result;
    }

    /**
     * полная очистка избранного пользователя
     */
    public static function clear(int $fUserID = 0): bool
    {
        $fUserID = static::checkFuser($fUserID);
        $rsProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
            ],
        ]);

        $result = true;
        while ($arProduct = $rsProducts->fetch()) {
            $res = FavoriteTable::delete($arProduct['ID']);
            $result &= $res->isSuccess();
        }

        return $result;
    }

    /**
     * Возвращает количество товаров, добавленных в избранное у пользователя.
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return int
     */
    public static function getCountByUser(int $fUserID = 0): int
    {
        $fUserID = static::checkFuser($fUserID);

        return FavoriteTable::getCount(['=FUSER_ID' => $fUserID]);
    }

    /**
     * Возвращает массив ID товаров, добавленных пользователем в избранное.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getIdsByUser(int $fUserID = 0): array
    {
        $fUserID = static::checkFuser($fUserID);

        $rsProducts = FavoriteTable::getList([
            'select' => [
                'PRODUCT_ID',
            ],
            'filter' => [
                '=FUSER_ID' => $fUserID,
            ],
            'order'  => [
                'INSERT_TIME' => 'desc',
            ],
        ]);

        $favIds = [];
        while ($arProduct = $rsProducts->fetch()) {
            $favIds[] = (int) $arProduct['PRODUCT_ID'];
        }

        return $favIds;
    }

    /**
     * Возвращает ID товаров, добавленных пользователем в избранное, предварительно проверив, что элементы с такими ID существуют в ИБ.
     * Удалит из избранного все элементы, которых нет в ИБ.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getByUser(int $fUserID = 0): array
    {
        $favIds = static::getIdsByUser($fUserID);
        $realIds = [];

        $rsReal = ElementTable::getList([
            'filter' => [
                '=ID' => $favIds,
            ],
            'select' => [
                'ID',
                'IBLOCK_ID',
            ],
            'limit'  => count($favIds),
        ]);

        while ($arProduct = $rsReal->fetch()) {
            $realIds[] = (int) $arProduct['ID'];
        }

        // удалим те, которых нет в инфоблоках
        $arDiff = array_diff($favIds, $realIds);
        static::deleteByProductID($arDiff);

        return $realIds;
    }

    /**
     * Проверяет, добавил ли пользователь товар в избранное.
     *
     * @param int|array $productID
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return array|bool Если в $productID был передан массив, вернёт массив с ID избранных товаров. Если был передан ID товара, то boolean.
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function isFavoriteProduct(int|array $productID, int $fUserID = 0): array|bool
    {
        $fUserID = static::checkFuser($fUserID);
        $productIDIsArray = is_array($productID);
        $result = $productIDIsArray ? [] : false;

        $rsProducts = FavoriteTable::getList([
            'select' => [
                'ID',
                'PRODUCT_ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=PRODUCT_ID' => $productID,
            ],
            'order'  => [
                'ID' => 'desc',
            ],
        ]);

        while ($arProduct = $rsProducts->fetch()) {
            if ($productIDIsArray) {
                $result[] = $arProduct['PRODUCT_ID'];
            } else {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Если $fUserID не задан, то получает ID текущего пользователя.
     *
     * @param int $fUserID
     * @return int
     */
    protected static function checkFuser(int $fUserID): int
    {
        if (!$fUserID) {
            $fUserID = Fuser::getId();
        }

        return $fUserID;
    }


    /**
     * Копирует избранные товары от одного пользователя другому
     *
     * @param int $fromFuserId
     * @param int $toFuserId
     */
    public static function copyFavoritesToFuser(int $fromFuserId, int $toFuserId): void
    {
        $productIds = static::getIdsByUser($fromFuserId);
        foreach ($productIds as $productId) {
            static::add($productId, $toFuserId);
        }
    }
}
