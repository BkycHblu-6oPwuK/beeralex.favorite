# Модуль beeralex.favorite

Модуль избранного (wishlist) для интернет-магазина на Bitrix. Позволяет пользователям добавлять товары в избранное с автоматической синхронизацией при авторизации.

## Основные возможности

- ✅ Добавление/удаление товаров в избранное
- 🔄 Автоматическая синхронизация избранного при авторизации  
- 📊 Подсчет количества избранных товаров
- ✔️ Проверка статуса товара (в избранном или нет)
- 🔗 Работа с авторизованными и неавторизованными пользователями
- 🌐 Поддержка мультисайтовости
- 🚀 REST API для интеграции с фронтендом

## Требования

- PHP 8.1+
- Bitrix Framework 22.0+
- Модули: `sale`, `iblock`

## Установка

1. Разместите модуль в `/local/modules/beeralex.favorite/`
2. Установите через административную панель Bitrix
3. Модуль автоматически создаст таблицу и зарегистрирует сервисы

## Быстрый старт

```php
use Beeralex\Favorite\FavouriteService;

$favoriteService = service(FavouriteService::class);

// Добавить в избранное
$favoriteService->add($productId = 123);

// Получить список избранного
$favoriteIds = $favoriteService->getByUser();
$count = $favoriteService->getCountByUser();

// Проверить статус
$isFavorite = $favoriteService->isFavoriteProduct(123);

// Удалить из избранного
$favoriteService->deleteByProductID(123);

// Очистить всё избранное
$favoriteService->clear();
```

## REST API

Модуль интегрирован с `beeralex.api`:

```javascript
// Добавить в избранное
POST /api/v1/favorite/store/
Параметры: productID

// Переключить статус
POST /api/v1/favorite/toggle/
Параметры: productID

// Получить список
GET /api/v1/favorite/get/

// Удалить из избранного
POST /api/v1/favorite/delete/
Параметры: productID

// Очистить избранное
POST /api/v1/favorite/clear/
```

## Основной сервис

### FavouriteService

Методы:
- `add(int $productID, int $fUserID = 0): bool` - добавить товар
- `deleteByProductID(int|array $productID, int $fUserID = 0): bool` - удалить товар
- `clear(int $fUserID = 0): bool` - очистить избранное
- `getCountByUser(int $fUserID = 0): int` - количество товаров
- `getIdsByUser(int $fUserID = 0): array` - получить ID товаров
- `getByUser(int $fUserID = 0): array` - получить ID с проверкой существования
- `isFavoriteProduct(int|array $productID, int $fUserID = 0): array|bool` - проверить статус
- `copyFavoritesToFuser(int $fromFuserId, int $toFuserId): void` - копировать между пользователями

## Автоматическая синхронизация

При авторизации пользователя избранное автоматически копируется из сессии неавторизованного пользователя:

```php
// Регистрируется автоматически
EventHandlers::onUserLogin($newUserId);
```

## Интеграция с каталогом

```php
use Beeralex\Favorite\FavouriteService;
use Beeralex\Catalog\Service\CatalogService;

$favoriteService = service(FavouriteService::class);
$catalogService = service(CatalogService::class);

// Получаем избранные товары с ценами
$favoriteIds = $favoriteService->getByUser();
$products = $catalogService->getProductsWithOffers($favoriteIds, true, true);
```

## Пример компонента

```php
class FavoritesComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $favoriteService = service(\Beeralex\Favorite\FavouriteService::class);
        $catalogService = service(\Beeralex\Catalog\Service\CatalogService::class);
        
        $favoriteIds = $favoriteService->getByUser();
        $this->arResult['PRODUCTS'] = $catalogService->getProductsWithOffers(
            $favoriteIds, 
            true, 
            true
        );
        $this->arResult['COUNT'] = count($favoriteIds);
        
        $this->includeComponentTemplate();
    }
}
```

## Расширение функционала

Создайте свой класс-наследник:

```php
namespace App\Service;

use Beeralex\Favorite\FavouriteService as BaseService;

class FavouriteService extends BaseService
{
    public function addWithNotification(int $productId): bool
    {
        $result = $this->add($productId);
        
        if ($result) {
            // Ваша логика уведомлений
        }
        
        return $result;
    }
}
```

Зарегистрируйте в `/local/.settings_extra.php`:

```php
use Beeralex\Favorite\FavouriteService;
use App\Service\FavouriteService as AppFavouriteService;

return [
    'services' => [
        'value' => [
            FavouriteService::class => [
                'constructor' => static function () {
                    return new AppFavouriteService(
                        \Bitrix\Main\Context::getCurrent()->getSite() ?? 's1'
                    );
                }
            ],
        ]
    ]
];
```

## Документация

Полная документация доступна в [docs/README.md](./docs/README.md)

## Лицензия

Проприетарный модуль. © beeralex
