<?php

use Beeralex\Favorite\FavouriteService;
use Beeralex\Favorite\Options;

return [
	'services' => [
		'value' => [
			Options::class => [
				'className' => Options::class
			],
			FavouriteService::class => [
				'className' => FavouriteService::class
			],
		]
	],
];
