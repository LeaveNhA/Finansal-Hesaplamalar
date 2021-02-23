<?php

namespace Bordro\Ihbar;

use function Bordro\Alan\apply;
use function Bordro\Alan\applyer;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\arrayFilterWrapper;
use function Bordro\Alan\ihbarSuresiHesaplama;
use function Bordro\Alan\Is\agiCikti;
use function Bordro\Alan\Is\agiOran;
use function Bordro\Alan\Is\brutMaas;
use function Bordro\Alan\Is\damgaVergisi;
use function Bordro\Alan\Is\gelirVergisi;
use function Bordro\Alan\Is\gelirVergisiDilimle;
use function Bordro\Alan\Is\gelirVergisiMatrahi;
use function Bordro\Alan\Is\issizlikIsci;
use function Bordro\Alan\Is\issizlikIsveren;
use function Bordro\Alan\Is\kumulatifGV;
use function Bordro\Alan\Is\netUcret;
use function Bordro\Alan\Is\sskIsci;
use function Bordro\Alan\Is\sskIsveren;
use function Bordro\Alan\Is\toplamMaliyet;
use function Bordro\Alan\mergeWith;
use function Bordro\Alan\toDateTime;
use function Bordro\Alan\wrapIt;
use function Bordro\Alan\wrapItWith;
use function Bordro\Alan\agiHesapla;
use function Bordro\Alan\lookUp;
use function Bordro\Alan\multiplyWith;
use function Bordro\Alan\zip;
use function Bordro\Alan\gelirVergisiDilimleri;

function ihbarTazminatiHesapla($parametreler, $girdiler)
{
    # İhbar Tazminatı!
    return \Functional\compose(
    # Girdiyi yapılandır!
        applyer([
            'girdiler' => applyer([
                'işeGiriş' => 'Bordro\Alan\toDateTime',
                'iştenÇıkış' => 'Bordro\Alan\toDateTime'
            ])
        ]),
        # Çalıştığı Gün hespalama!
        applyer([
            'girdiler' => 'Bordro\Alan\calistigiGunHesapla'
        ]),
        # Çalıştığı gün +1
        applyer([
            'girdiler' => applyer([
                'çalıştığıGünSayısı' => function ($a) {
                    return $a + 1;
                }
            ])
        ]),
        applyer([
            'girdiler' => ihbarSuresiHesaplama($parametreler)
        ]),
        applyer([
            'girdiler' => function ($girdi) {
                $girdi['brütİhbarTazminatı'] = $girdi['aylıkBrütÜcret'] * $girdi['ihbarSüresiGünü'] / 30;

                return $girdi;
            }
        ]),
        applyer([
            'girdiler' => function ($girdi) use ($parametreler) {
                $girdi['kümülatifGelirVergisiMatrahıSonuc'] =
                    gelirVergisiDilimleri($parametreler)($girdi['kümülatifGelirVergisiMatrahı']);

                return $girdi;
            }
        ]),
        applyer([
            'girdiler' => function ($girdi) use ($parametreler) {
                $girdi['gelirVergisiMatrahıSonucu'] =
                    gelirVergisiDilimleri($parametreler)($girdi['kümülatifGelirVergisiMatrahı'] + $girdi['brütİhbarTazminatı']);

                return $girdi;
            }
        ]),
        applyer([
            'girdiler' => function ($girdi) use ($parametreler) {
                $girdi['damgaVergisi'] = $girdi['brütİhbarTazminatı'] * $parametreler['damgaVergisiKatkısı'];

                return $girdi;
            }
        ]),
        applyer([
            'girdiler' => function ($girdi) {
                $girdi['gelirVergisi'] =
                    abs(
                        $girdi['kümülatifGelirVergisiMatrahıSonuc']
                        -
                        $girdi['gelirVergisiMatrahıSonucu']
                    );

                return $girdi;
            }
        ]),
        applyer([
            'girdiler' => function ($girdi) {
                $girdi['netİhbarTazminatı'] =
                    $girdi['brütİhbarTazminatı']
                    -
                    $girdi['gelirVergisi']
                    -
                    $girdi['damgaVergisi'];

                return $girdi;
            }
        ]),
        function ($veriler) {
            $veriler['çıktı'] = \Functional\select_keys($veriler['girdiler'],
                ['çalıştığıGünSayısı',
                    'ihbarSüresiGünü',
                    'brütİhbarTazminatı',
                    'gelirVergisi',
                    'damgaVergisi',
                    'netİhbarTazminatı']);

            return $veriler;
        },
        # Rakamsal Derinlik:
        applyer([
            'çıktı' => function ($cikti) {
                array_walk_recursive($cikti,
                    function (&$value) {
                        $value = round($value, 2);
                    }
                );

                return $cikti;
            }
        ])
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler]);
}

