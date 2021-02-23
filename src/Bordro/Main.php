<?php

# Tüm modüller için hesaplama derinliğini çözümle!
# Kıdem'de bir yıl altında hesaplanıyorsa, kıdem alamaz.

namespace Bordro;

use function Bordro\BrutNet\bruttenNeteHesapla;
use function Bordro\Ihbar\ihbarTazminatiHesapla;
use function Bordro\Kidem\kidemTazminatiHesapla;

const KIDEM_TAZMINATI = 'kıdem';
const IHBAR_TAZMINATI = 'ihbar';
const BRUTTEN_NETE = 'brütnet';
const NETTEN_BRUTE = 'netbrüt';

if (!function_exists('Bordro\hesapla')) {
    function hesapla($fn, $parametreler, $girdiler)
    {
        switch ($fn) {
            case KIDEM_TAZMINATI:
                $fn = 'Bordro\Kidem\kidemTazminatiHesapla';
                break;
            case IHBAR_TAZMINATI:
                $fn = 'Bordro\Ihbar\ihbarTazminatiHesapla';
                break;
            case NETTEN_BRUTE:
                $fn = 'Bordro\NetBrut\nettenBrutHesapla';
                break;
            case BRUTTEN_NETE:
            default:
                $fn = 'Bordro\BrutNet\bruttenNeteHesapla';
                break;
        }

        return $fn($parametreler, $girdiler);
    }
}