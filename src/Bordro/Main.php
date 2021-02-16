<?php

namespace Bordro\Main;

$externalPackageFlag = (bool)1;
if ($externalPackageFlag) {
    require_once('./../../vendor/autoload.php');
}

use function Bordro\BrutNet\bruttenNeteHesapla;
use function Bordro\Ihbar\ihbarTazminatiHesapla;
use function Bordro\Kidem\kidemTazminatiHesapla;


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

