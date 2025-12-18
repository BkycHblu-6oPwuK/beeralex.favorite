# Модуль beeralex.favorite

## Описание

Модуль `beeralex.favorite` предоставляет функционал избранного (wishlist) для интернет-магазина на Bitrix. Позволяет пользователям добавлять товары в избранное, управлять списком избранных товаров и синхронизировать избранное между сессиями неавторизованных и авторизованных пользователей.

## Основные возможности

- ✅ **Добавление товаров в избранное** для авторизованных и неавторизованных пользователей
- 🔄 **Автоматическая синхронизация** избранного при авторизации
- 🗑️ **Управление списком**: добавление, удаление, очистка
- 📊 **Подсчет количества** избранных товаров
- ✔️ **Проверка статуса** товара (в избранном или нет)
- 🔗 **Связь с Fuser** - использует систему виртуальных пользователей Bitrix
- 🌐 **Мультисайтовость** - поддержка нескольких сайтов

## Архитектура

Модуль построен с использованием:

- **ORM DataManager** - для работы с базой данных
- **Fuser** - система виртуальных пользователей Bitrix Sale
- **Event Handlers** - автоматическая синхронизация при авторизации
- **Dependency Injection** - сервис регистрируется в DI контейнере

## Структура модуля

```
beeralex.favorite/
├── .settings.php             # Регистрация сервисов в DI контейнере
├── install/
│   └── index.php            # Установщик модуля
├── lib/
│   ├── FavouriteService.php # Основной сервис для работы с избранным
│   ├── FavoriteTable.php    # ORM таблица для хранения данных
│   ├── EventHandlers.php    # Обработчики событий
│   └── Options.php          # Настройки модуля
└── docs/                    # Документация
```

## Установка

### Требования

- PHP 8.1+
- Bitrix Framework 22.0+
- Модули: `sale`, `iblock`

### Процесс установки

1. Разместите модуль в директории `/local/modules/beeralex.favorite/`
2. Установите модуль через административную панель Bitrix
3. При установке автоматически создастся таблица `beeralex_favorite_products`
4. Модуль автоматически зарегистрирует сервис `FavouriteService` в DI контейнере

## Структура базы данных

### Таблица beeralex_favorite_products

| Поле | Тип | Описание |
|------|-----|----------|
| `ID` | INT | Первичный ключ |
| `LID` | VARCHAR | ID сайта |
| `FUSER_ID` | INT | ID виртуального пользователя (Fuser) |
| `PRODUCT_ID` | INT | ID товара из инфоблока |
| `CREATED_AT` | DATETIME | Дата добавления в избранное |

**Индексы:**
- Первичный ключ по `ID`
- Составной индекс по `FUSER_ID`, `PRODUCT_ID`, `LID`

## Быстрый старт

### Добавление товара в избранное

```php
use Beeralex\Favorite\FavouriteService;

$favoriteService = service(FavouriteService::class);

// Добавить товар в избранное
$result = $favoriteService->add($productId = 123);

if ($result) {
    echo "Товар добавлен в избранное";
}
```

### Получение списка избранных товаров

```php
// Получить ID товаров в избранном
$favoriteIds = $favoriteService->getByUser();

// Получить количество товаров
$count = $favoriteService->getCountByUser();

echo "В избранном {$count} товаров";
```

### Проверка статуса товара

```php
// Проверить один товар
$isFavorite = $favoriteService->isFavoriteProduct($productId = 123);

if ($isFavorite) {
    echo "Товар уже в избранном";
}

// Проверить несколько товаров
$favoriteIds = $favoriteService->isFavoriteProduct([123, 456, 789]);
// Вернет массив ID тех товаров, которые в избранном: [123, 789]
```

### Удаление из избранного

```php
// Удалить один товар
$favoriteService->deleteByProductID($productId = 123);

// Очистить всё избранное
$favoriteService->clear();
```

## REST API контроллер

Модуль интегрирован с `beeralex.api` и предоставляет REST API endpoints:

### Добавить в избранное

```
POST /api/v1/favorite/store/
Параметры: productID (int)

Ответ:
{
    "success": true,
    "data": {
        "count": 5,
        "items": [123, 456, 789, ...]
    }
}
```

### Удалить из избранного

```
POST /api/v1/favorite/delete/
Параметры: productID (int)

Ответ:
{
    "success": true,
    "data": {
        "count": 4,
        "items": [123, 456, 789, ...]
    }
}
```

### Переключить статус (toggle)

```
POST /api/v1/favorite/toggle/
Параметры: productID (int)

Ответ:
{
    "success": true,
    "data": {
        "action": "added", // или "removed"
        "isFavorite": true,
        "count": 5
    }
}
```

### Получить список избранного

```
GET /api/v1/favorite/get/

Ответ:
{
    "success": true,
    "data": {
        "items": [123, 456, 789],
        "count": 3
    }
}
```

### Очистить избранное

```
POST /api/v1/favorite/clear/

Ответ:
{
    "success": true,
    "data": {
        "count": 0,
        "items": []
    }
}
```

## Основной сервис: FavouriteService

### Методы

#### add()

Добавляет товар в избранное.

```php
public function add(int $productID, int $fUserID = 0): bool
```

**Параметры:**
- `$productID` - ID товара
- `$fUserID` - ID виртуального пользователя (опционально, по умолчанию - текущий)

**Возвращает:** `true` если товар добавлен или уже был в избранном

**Пример:**

```php
$favoriteService->add(123); // Для текущего пользователя
$favoriteService->add(123, 456); // Для конкретного Fuser ID
```

#### deleteByProductID()

Удаляет товар из избранного.

```php
public function deleteByProductID(int|array $productID, int $fUserID = 0): bool
```

**Параметры:**
- `$productID` - ID товара или массив ID
- `$fUserID` - ID виртуального пользователя (опционально)

**Возвращает:** `true` если товар удален или его не было в избранном

**Пример:**

```php
$favoriteService->deleteByProductID(123);
$favoriteService->deleteByProductID([123, 456, 789]); // Удалить несколько
```

#### clear()

Полностью очищает избранное пользователя.

```php
public function clear(int $fUserID = 0): bool
```

**Пример:**

```php
$favoriteService->clear();
```

#### getCountByUser()

Возвращает количество товаров в избранном.

```php
public function getCountByUser(int $fUserID = 0): int
```

**Пример:**

```php
$count = $favoriteService->getCountByUser();
echo "У вас {$count} товаров в избранном";
```

#### getIdsByUser()

Возвращает массив ID товаров в избранном.

```php
public function getIdsByUser(int $fUserID = 0): array
```

**Возвращает:** Массив ID товаров, отсортированный по дате добавления (сначала новые)

**Пример:**

```php
$ids = $favoriteService->getIdsByUser();
// [789, 456, 123]
```

#### getByUser()

Возвращает ID товаров в избранном с проверкой их существования в инфоблоке.

```php
public function getByUser(int $fUserID = 0): array
```

**Особенности:**
- Проверяет существование товаров в инфоблоке
- Автоматически удаляет несуществующие товары из избранного

**Пример:**

```php
$validIds = $favoriteService->getByUser();
// Вернет только ID существующих товаров
```

#### isFavoriteProduct()

Проверяет, добавлен ли товар в избранное.

```php
public function isFavoriteProduct(int|array $productID, int $fUserID = 0): array|bool
```

**Параметры:**
- `$productID` - ID товара или массив ID

**Возвращает:**
- `bool` если передан один ID
- `array` ID избранных товаров, если передан массив

**Пример:**

```php
// Проверка одного товара
if ($favoriteService->isFavoriteProduct(123)) {
    echo "В избранном";
}

// Проверка нескольких товаров
$favoriteIds = $favoriteService->isFavoriteProduct([123, 456, 789]);
// Вернет: [123, 789] (если только эти в избранном)
```

#### copyFavoritesToFuser()

Копирует избранные товары от одного пользователя другому.

```php
public function copyFavoritesToFuser(int $fromFuserId, int $toFuserId): void
```

**Используется:** При авторизации для переноса избранного неавторизованного пользователя

---

## Обработчики событий

### onUserLogin

Автоматически копирует избранное от неавторизованного пользователя к авторизованному.

**Как работает:**

1. Получает Fuser ID из сессии (неавторизованный пользователь)
2. Получает Fuser ID авторизованного пользователя
3. Копирует все избранные товары между Fuser'ами

```php
// Регистрируется автоматически при установке модуля
EventManager::getInstance()->registerEventHandler(
    'main',
    'OnAfterUserLogin',
    'beeralex.favorite',
    EventHandlers::class,
    'onUserLogin'
);
```

### onSaleUserDelete

Сохраняет информацию о Fuser перед его удалением.

### restoreDeletedFuser

Восстанавливает Fuser после авторизации для сохранения привязки к избранным товарам.

---

## Примеры использования

### Компонент избранного

```php
// components/custom/favorites/class.php
use Beeralex\Favorite\FavouriteService;
use Beeralex\Catalog\Service\CatalogService;

class FavoritesComponent extends CBitrixComponent
{
    protected FavouriteService $favoriteService;
    protected CatalogService $catalogService;
    
    public function executeComponent()
    {
        $this->favoriteService = service(FavouriteService::class);
        $this->catalogService = service(CatalogService::class);
        
        // Получаем ID избранных товаров
        $favoriteIds = $this->favoriteService->getByUser();
        
        // Получаем товары с ценами и предложениями
        $products = $this->catalogService->getProductsWithOffers(
            $favoriteIds, 
            true, 
            true
        );
        
        $this->arResult = [
            'PRODUCTS' => $products,
            'COUNT' => count($products),
        ];
        
        $this->includeComponentTemplate();
    }
}
```

### AJAX добавление в избранное

```php
// ajax/add_to_favorite.php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Beeralex\Favorite\FavouriteService;

$productId = (int)($_POST['product_id'] ?? 0);

if (!$productId) {
    die(json_encode(['success' => false, 'error' => 'Не указан товар']));
}

$favoriteService = service(FavouriteService::class);
$result = $favoriteService->add($productId);

if ($result) {
    echo json_encode([
        'success' => true,
        'count' => $favoriteService->getCountByUser(),
        'isFavorite' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка добавления в избранное'
    ]);
}
```

### Кнопка "В избранное" в шаблоне

```html
<div class="product-card">
    <h3><?= $product['NAME'] ?></h3>
    
    <?php
    $favoriteService = service(\Beeralex\Favorite\FavouriteService::class);
    $isFavorite = $favoriteService->isFavoriteProduct($product['ID']);
    ?>
    
    <button class="favorite-btn <?= $isFavorite ? 'active' : '' ?>" 
            data-product-id="<?= $product['ID'] ?>">
        <?= $isFavorite ? '❤️ В избранном' : '🤍 В избранное' ?>
    </button>
</div>

<script>
document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        fetch('/api/v1/favorite/toggle/', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `productID=${productId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.classList.toggle('active');
                this.textContent = data.data.isFavorite ? '❤️ В избранном' : '🤍 В избранное';
                
                // Обновляем счетчик
                document.querySelector('.favorite-counter').textContent = data.data.count;
            }
        });
    });
});
</script>
```

### Массовая проверка товаров

```php
// Проверяем список товаров на странице каталога
$productIds = [123, 456, 789, 101, 112];
$favoriteService = service(FavouriteService::class);

$favoriteIds = $favoriteService->isFavoriteProduct($productIds);
// Вернет: [123, 789] (если только эти в избранном)

foreach ($products as &$product) {
    $product['IS_FAVORITE'] = in_array($product['ID'], $favoriteIds);
}
```

---

## Расширение функционала

### Создание собственного сервиса

Вы можете расширить `FavouriteService` своей реализацией:

```php
namespace App\Service;

use Beeralex\Favorite\FavouriteService as BaseService;

class FavouriteService extends BaseService
{
    /**
     * Добавляет товар с отправкой уведомления
     */
    public function addWithNotification(int $productId): bool
    {
        $result = $this->add($productId);
        
        if ($result) {
            // Отправляем уведомление или событие аналитики
            $this->sendNotification($productId);
        }
        
        return $result;
    }
    
    /**
     * Получает избранное с дополнительной информацией
     */
    public function getWithDetails(): array
    {
        $ids = $this->getByUser();
        
        // Добавляем информацию о товарах
        $catalogService = service(\Beeralex\Catalog\Service\CatalogService::class);
        return $catalogService->getProductsWithOffers($ids, true, true);
    }
    
    private function sendNotification(int $productId): void
    {
        // Ваша логика отправки уведомлений
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

---

## Мультисайтовость

Модуль поддерживает несколько сайтов. Избранное привязано к сайту через поле `LID`.

```php
$favoriteService = service(FavouriteService::class);

// Изменить сайт
$favoriteService->setSiteId('s2');

// Теперь все операции будут для сайта s2
$count = $favoriteService->getCountByUser();
```

---

## Интеграция с другими модулями

### С модулем catalog

```php
use Beeralex\Favorite\FavouriteService;
use Beeralex\Catalog\Service\CatalogService;

$favoriteService = service(FavouriteService::class);
$catalogService = service(CatalogService::class);

// Получаем избранные товары с ценами и скидками
$favoriteIds = $favoriteService->getByUser();
$products = $catalogService->getProductsWithOffers($favoriteIds, true, true);
```

### С модулем API

```javascript
// Frontend интеграция
class FavoriteManager {
    async add(productId) {
        const response = await fetch('/api/v1/favorite/store/', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `productID=${productId}`
        });
        return response.json();
    }
    
    async toggle(productId) {
        const response = await fetch('/api/v1/favorite/toggle/', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `productID=${productId}`
        });
        return response.json();
    }
    
    async getAll() {
        const response = await fetch('/api/v1/favorite/get/');
        return response.json();
    }
}

const favoriteManager = new FavoriteManager();
```

---

## Производительность

### Оптимизация запросов

Модуль оптимизирован для работы с большим количеством товаров:

- Используются индексы БД для быстрой выборки
- Метод `getByUser()` проверяет существование товаров одним запросом
- Кеширование на уровне приложения (можно добавить)

### Рекомендации

1. **Кешируйте результаты** `getByUser()` на время сессии:

```php
$cache = \Bitrix\Main\Data\Cache::createInstance();
$cacheId = 'favorite_' . Fuser::getId();
$cacheDir = '/favorite/';

if ($cache->initCache(3600, $cacheId, $cacheDir)) {
    $favoriteIds = $cache->getVars();
} else {
    $favoriteIds = $favoriteService->getByUser();
    $cache->startDataCache();
    $cache->endDataCache($favoriteIds);
}
```

2. **Используйте `getIdsByUser()`** вместо `getByUser()` если проверка существования не нужна

3. **Массовая проверка** эффективнее множественных одиночных:

```php
// ❌ Плохо
foreach ($products as $product) {
    $product['IS_FAVORITE'] = $favoriteService->isFavoriteProduct($product['ID']);
}

// ✅ Хорошо
$productIds = array_column($products, 'ID');
$favoriteIds = $favoriteService->isFavoriteProduct($productIds);
foreach ($products as &$product) {
    $product['IS_FAVORITE'] = in_array($product['ID'], $favoriteIds);
}
```

---

## Миграции

Модуль использует систему миграций для управления структурой БД.

Файл миграции: `migrations/Version20250417085746.php.php`

При установке модуля таблица создается автоматически.

---

## Отладка

### Логирование

Добавьте логирование для отладки:

```php
namespace App\Service;

use Beeralex\Favorite\FavouriteService as BaseService;

class FavouriteService extends BaseService
{
    public function add(int $productID, int $fUserID = 0): bool
    {
        $result = parent::add($productID, $fUserID);
        
        \Bitrix\Main\Diag\Debug::writeToFile([
            'action' => 'add',
            'productID' => $productID,
            'fUserID' => $fUserID ?: \Bitrix\Sale\Fuser::getId(),
            'result' => $result
        ], '', '/logs/favorite.log');
        
        return $result;
    }
}
```

---

## FAQ

**Q: Что произойдет с избранным при авторизации?**  
A: При авторизации избранное неавторизованного пользователя автоматически копируется к авторизованному.

**Q: Можно ли добавить в избранное товар, которого нет в инфоблоке?**  
A: Да, но метод `getByUser()` автоматически удалит такие товары при проверке.

**Q: Как работает для неавторизованных пользователей?**  
A: Использует систему Fuser (виртуальных пользователей) из модуля Sale, которая привязывается к сессии.

**Q: Можно ли хранить избранное в куках/localStorage вместо БД?**  
A: Модуль использует БД для надежности, но вы можете создать гибридное решение с кешем в localStorage.

---

## Лицензия

Проприетарный модуль. © beeralex

## Поддержка

При возникновении вопросов обращайтесь к разработчикам модуля.
