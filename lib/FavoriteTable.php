<?
namespace Beeralex\Favorite;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;
use Beeralex\Core\Traits\TableManagerTrait;
use Bitrix\Main\ORM\Fields\StringField;

class FavoriteTable extends DataManager
{
    use TableManagerTrait;

    public static function getTableName()
    {
        return 'beeralex_favorite_products';
    }

    public static function getMap()
    {
        return [
            'ID' => new IntegerField('ID', [
                'autocomplete' => true,
                'primary'      => true,
            ]),
            'LID' => new StringField('LID', [
                'required' => true,
            ]),
            'FUSER_ID'    => new IntegerField('FUSER_ID', [
                'required' => true,
            ]),
            'PRODUCT_ID'  => new IntegerField('PRODUCT_ID', [
                'required' => true,
            ]),
            'CREATED_AT' => new DatetimeField('CREATED_AT', [
                'default_value' => new DateTime(),
            ]),
        ];
    }
}
