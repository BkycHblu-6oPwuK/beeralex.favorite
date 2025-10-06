<?
use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}

if ($ex = $APPLICATION->GetException()) {
    $message = new CAdminMessage([
        'TYPE'    => 'ERROR',
        'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
        'DETAILS' => $ex->GetString(),
        'HTML'    => true,
    ]);

    echo $message->Show();
} else {
    $message = new CAdminMessage([
        'TYPE'    => 'OK',
        'MESSAGE' => Loc::getMessage('MOD_INST_OK'),
    ]);

    echo $message->Show();
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
</form>
