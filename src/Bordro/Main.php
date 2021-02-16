<?php

require_once('./Alan/OrtakHesaplamalar.php');
require_once('./BrutNet/Main.php');
require_once('./Ihbar/Main.php');
require_once('./Kidem/Main.php');


$externalPackageFlag = (bool)1;
if ($externalPackageFlag) {
    require_once('./../../vendor/autoload.php');
}

const KIDEM_TAZMINATI = 'kıdem';
const IHBAR_TAZMINATI = 'ihbar';
const BRUTTEN_NETE = 'brütnet';

function hesapla($fn, $parametreler, $girdiler)
{
    switch ($fn) {
        case KIDEM_TAZMINATI:
            $fn = 'kidemTazminatiHesapla';
            break;
        case IHBAR_TAZMINATI:
            $fn = 'ihbarTazminatiHesapla';
            break;
        case BRUTTEN_NETE:
        default:
            $fn = 'bruttenNeteHesapla';
            break;
    }

    return $fn($parametreler, $girdiler);
}

$parametreler = [
    'damgaVergisiKatsayısı' => 232.86,
    'kıdemTazminatıTavan' => 7638.69,
    'damgaVergisiKatkısı' => 0.00759,
    #------------------------------------oranlar tam sayı olarak yazılmıştır-------------------------------
    'ihbarSüresiKısıtları' => [['>', 180, 14], ['>', 540, 28], ['>', 1080, 42], ['<', 1080, 56]],
    'vergiDilimiKısıtları' =>
        [[0, 24000, 15],
            [24000, 53000, 20],
            [53000, 190000, 27],
            [190000, 650000, 35],
            [650000, 99999999999999, 40]],
    #------------------------------------------------------------------------------------------------------
    'ilkİkiÇocukOranı' => 7.5,
    'üçüncüÇocukOranı' => 10,
    'dördüncüÇocukVeSonrasıOranı' => 5,
    'asgariÜcret' => 2825.90,
    'SGKTavanOranı' => 7.5,
    'SSKİşVerenPrimiOranı' => 15.5,
    'SSKİşçiPrimiOranı' => 0.14,
    'SSKİşsizlikİşçiPrimi' => 0.01,
    'işsizlikİşçiPrimi' => 0.01,
    'işsizlikİşVerenPrimiOranı' => 2
];

$girdilerIhbar = [
    'adSoyad' => 'Seçkin KÜKRER',
    'sskNo' => '???',
    'işeGiriş' => "2016-01-01",
    'iştenÇıkış' => "2021-01-01",
    'aylıkBrütÜcret' => 15000,
    'kümülatifGelirVergisiMatrahı' => 56000
];

$parametrelerKidem = [
    'damgaVergisiKatsayısı' => 232.86,
    'kıdemTazminatıTavan' => 7638.69,
    'damgaVergisiKatkısı' => 0.00759
];
$girdilerKidem = [
    'adSoyad' => 'Seçkin KÜKRER',
    'işeGiriş' => "2016-01-01",
    'iştenÇıkış' => "2020-01-01",
    'aylıkBrütÜcret' => 100000,
    'ekÖdemeÜcret' => 0
];

$agiGirdiler = ['medeniDurum' => 'evli', 'eşininÇalışmaDurumu' => 'çalışmıyor', 'çocukSayısı' => 4];

var_dump(
 hesapla(BRUTTEN_NETE, $parametreler, array_merge(['aylıkBrütÜcret' => 10000], $agiGirdiler))
# hesapla(IHBAR_TAZMINATI, $parametreler, $girdilerIhbar)
# hesapla(KIDEM_TAZMINATI, $parametreler, $girdilerKidem)
# -------------------------------------------------------------------------------------------------
# parçalı hesaplamalar:
# ihbarSuresiHesaplama($parametreler)($girdiler)
# gelirVergisiDilimleri($parametreler)($girdiler['kümülatifGelirVergisiMatrahı'])
# gelirVergisiDilimleri($parametreler)(84000)
# applyer(['a' => 'identity', 'b' => function(){return 1;}])(['a' => 1])
# agiHesapla($parametreler)($agiGirdiler)
# agiCocukSayisi($parametreler)($agiGirdiler)
# arrayReduceWrapper(mergeWith(function($a, $b){ return $a + $b; }), [])([['a' => 1], ['a' => 1, 'b' => 2]])
# arrayMapWrapper(vergiDilimiIslemi(56000))($parametreler['vergiDilimiKısıtları'])
# gelirVergisiDilimiBul($parametreler)(25500) / 100 * 10000
# arrayMapWrapper(gelirVergisiDilimleri($parametreler))([8500, 17000, 25500, 34000, 42500, 51000, 59500, 68000, 76500, 85000, 93500, 102000])
# \Functional\select_keys([1,2,3,4,5,6,7], range(0,8))
);