# Установка

добавьте в composer.json экстра опцию, чтобы композер поставил пакет в local/modules

```json
"extra": {
  "installer-paths": {
    "local/modules/{$name}/": ["type:bitrix-module"]
  }
}
```

```bash
composer require beeralex/beeralex.favorite
```

# beeralex.favorite

для работы обязателен модуль ```beeralex.core```

запросы кидаются на контроллер

- метод добавления - /bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.add&productID={id}
- метод удаления - /bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.delete&productID={id}
- метод toggle (добавит или удалит) - /bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.toggle&productID={id}
- метод clear (полная очистка избранного пользователя) - /bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.clear
- метод получения массива ид товаров избранного - /bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.get

Так же избранное переносится в момент авторизации пользователю.

в example есть js файл с примером работы с модулем на стороне js без общей структуры

в migrations есть миграция для переноса избранного c модуля enterego.likefavorites на модуль beeralex