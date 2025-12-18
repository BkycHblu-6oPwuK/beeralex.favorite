<?php

namespace Sprint\Migration;

use Beeralex\Favorite\FavouriteService;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Beeralex\Favorite\Helper;

class Version20250417085746 extends Version
{
    protected $author = "admin";

    protected $description = "миграция для переноса избранного c модуля enterego.likefavorites на модуль beeralex";

    protected $moduleVersion = "4.18.0";

    public function up()
    {
        Loader::requireModule('beeralex.favorite');
        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();

        $result = $connection->query("
    SELECT F_USER_ID, I_BLOCK_ID 
    FROM enterego_like_favorite 
    WHERE FAVORITE = '1'
");

        while ($row = $result->fetch()) {
            if (!$row['I_BLOCK_ID'] || !$row['F_USER_ID']) continue;
            try {
                service(FavouriteService::class)->add((int)$row['I_BLOCK_ID'],(int)$row['F_USER_ID']);
            } catch (\Exception $e) {
                echo 'Ошибка: ' . $e->getMessage() . PHP_EOL;
            }
        }
    }

    public function down() {}
}
