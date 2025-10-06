<?

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Beeralex\Favorite\EventHandlers;
use Beeralex\Favorite\FavoriteTable;

Loc::loadMessages(__FILE__);

class beeralex_favorite extends CModule
{
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_ID = 'beeralex.favorite';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('ITB_FAVORITE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ITB_FAVORITE_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = Loc::getMessage('ITB_FAVORITE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('ITB_FAVORITE_PARTNER_URI');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if ($this->isVersionD7()) {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
        } else {
            $APPLICATION->ThrowException(Loc::getMessage('ITB_FAVORITE_INSTALL_ERROR_D7'));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage('ITB_FAVORITE_INSTALL_TITLE'), __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = \Bitrix\Main\Context::getCurrent();
        $request = $context->getRequest();
        Loader::includeModule($this->MODULE_ID);
        if ($request['step'] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('ITB_FAVORITE_UNINSTALL_TITLE'), __DIR__ . '/unstep1.php');
        } else {
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            if ($request['savedata'] !== 'Y') {
                $this->UnInstallDB();
            }

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage('ITB_FAVORITE_UNISTALL_TITLE'), __DIR__ . '/unstep2.php');
        }
    }

    public function InstallDB()
    {
        FavoriteTable::createTable();
    }

    public function UnInstallDB()
    {
        FavoriteTable::dropTable();
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('main', 'OnUserLogin', $this->MODULE_ID, EventHandlers::class, 'restoreDeletedFuser', 200);
        $eventManager->registerEventHandler('main', 'OnUserLogin', $this->MODULE_ID, EventHandlers::class, 'onUserLogin', 50);
        $eventManager->registerEventHandler('sale', 'OnSaleUserDelete', $this->MODULE_ID, EventHandlers::class, 'onSaleUserDelete');
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('main', 'OnUserLogin', $this->MODULE_ID, EventHandlers::class, 'restoreDeletedFuser');
        $eventManager->unRegisterEventHandler('main', 'OnUserLogin', $this->MODULE_ID, EventHandlers::class, 'onUserLogin');
        $eventManager->unRegisterEventHandler('sale', 'OnSaleUserDelete', $this->MODULE_ID, EventHandlers::class, 'onSaleUserDelete');
    }

    protected function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.0.0');
    }
}
