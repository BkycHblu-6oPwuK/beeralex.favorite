<?

namespace Beeralex\Favorite;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;

class FavouriteService
{
    protected string $siteId;

    public function __construct(?string $siteId = null)
    {
        $this->siteId = $siteId ?? Context::getCurrent()->getSite() ?? 's1';
        Loader::requireModule('sale');
        Loader::requireModule('iblock');
    }

    public function setSiteId(string $siteId): void
    {
        $this->siteId = $siteId;
    }
    
    /**
     * Добавляет товар в избранное.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return bool true, если товар был добавлен в избранное сейчас либо ранее.
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    public function add(int $productID, int $fUserID = 0): bool
    {
        if ($productID < 1) {
            return false;
        }

        $fUserID = $this->checkFuser($fUserID);

        $rsUserProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=PRODUCT_ID' => $productID,
                '=LID' => $this->siteId,
            ],
            'limit'  => 1,
        ]);

        if ($rsUserProducts->fetch()) {
            return true;
        }

        $rsFave = FavoriteTable::add([
            'FUSER_ID'   => $fUserID,
            'PRODUCT_ID' => $productID,
            'LID' => $this->siteId,
        ]);

        return $rsFave->isSuccess();
    }

    /**
     * Удаляет товар из избранного.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return bool true, если товар был удалён из избранного сейчас либо его там не было.
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     */
    public function deleteByProductID(int|array $productID, int $fUserID = 0): bool
    {
        $fUserID = $this->checkFuser($fUserID);
        $rsProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=PRODUCT_ID' => $productID,
                '=LID' => $this->siteId,
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
    public function clear(int $fUserID = 0): bool
    {
        $fUserID = $this->checkFuser($fUserID);
        $rsProducts = FavoriteTable::getList([
            'select' => [
                'ID',
            ],
            'filter' => [
                '=FUSER_ID'   => $fUserID,
                '=LID' => $this->siteId,
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
     */
    public function getCountByUser(int $fUserID = 0): int
    {
        $fUserID = $this->checkFuser($fUserID);

        return FavoriteTable::getCount(['=FUSER_ID' => $fUserID, '=LID' => $this->siteId]);
    }

    /**
     * Возвращает массив ID товаров, добавленных пользователем в избранное.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getIdsByUser(int $fUserID = 0): array
    {
        $fUserID = $this->checkFuser($fUserID);

        $rsProducts = FavoriteTable::getList([
            'select' => [
                'PRODUCT_ID',
            ],
            'filter' => [
                '=FUSER_ID' => $fUserID,
                '=LID' => $this->siteId,
            ],
            'order'  => [
                'CREATED_AT' => 'desc',
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
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getByUser(int $fUserID = 0): array
    {
        $favIds = $this->getIdsByUser($fUserID);
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
        $this->deleteByProductID($arDiff);

        return $realIds;
    }

    /**
     * Проверяет, добавил ли пользователь товар в избранное.
     *
     * @param int $fUserID необязательный параметр. По умолчанию получит FUserID текущего пользователя.
     * @return array|bool Если в $productID был передан массив, вернёт массив с ID избранных товаров. Если был передан ID товара, то boolean.
     * @throws \Bitrix\Main\ArgumentException
     */
    public function isFavoriteProduct(int|array $productID, int $fUserID = 0): array|bool
    {
        $fUserID = $this->checkFuser($fUserID);
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
                '=LID' => $this->siteId,
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
     */
    protected function checkFuser(int $fUserID): int
    {
        if (!$fUserID) {
            $fUserID = Fuser::getId();
        }

        return $fUserID;
    }


    /**
     * Копирует избранные товары от одного пользователя другому
     */
    public function copyFavoritesToFuser(int $fromFuserId, int $toFuserId): void
    {
        $productIds = $this->getIdsByUser($fromFuserId);
        foreach ($productIds as $productId) {
            $this->add($productId, $toFuserId);
        }
    }
}
