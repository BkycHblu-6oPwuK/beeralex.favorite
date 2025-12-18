<?php

use Beeralex\Favorite\FavouriteService;
use Beeralex\Favorite\Options;
use Bitrix\Main\Context;

return [
	'services' => [
		'value' => [
			Options::class => [
				'className' => Options::class
			],
			FavouriteService::class => [
				'constructor' => static function () {
					return new FavouriteService(
						Context::getCurrent()->getSite() ?? 's1'
					);
				}
			],
		]
	],
];
