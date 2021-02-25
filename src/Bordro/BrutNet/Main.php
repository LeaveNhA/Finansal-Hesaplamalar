<?php

namespace Bordro\BrutNet;

use function Bordro\Alan\apply;
use function Bordro\Alan\applyer;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\arrayFilterWrapper;
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
use function Bordro\Alan\wrapIt;
use function Bordro\Alan\wrapItWith;
use function Bordro\Alan\agiHesapla;
use function Bordro\Alan\lookUp;
use function Bordro\Alan\multiplyWith;
use function Bordro\Alan\zip;
use function Bordro\Alan\gelirVergisiDilimleri;

function bruttenNeteHesapla($parametreler, $girdiler)
{
    # Brütten Nete!
    return \Functional\compose(
    # Yıllık Brüt Ücret:
        applyer([
            'girdiler' => function ($girdiler) {
                $girdiler['yıllıkBrütÜcret'] = $girdiler['aylıkBrütÜcret'] * 12;

                return $girdiler;
            }
        ]),
        # Aylık Dilimler:
        applyer([
            'çıktı' => function () {
                return arrayMapWrapper('Bordro\Alan\identity')
                (range(0, 11));
            }
        ]),
        applyer([
            'çıktı' => arrayMapWrapper(
                function ($ay) {
                    return ['ay' => $ay];
                }
            )
        ]),
        # Aylık Brüt Ücret:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    (\Functional\curry_n(2, 'array_merge'))
                    (['brütMaaş' => $veriler['girdiler']['aylıkBrütÜcret']])
                ) ($cikti);
            }
        ]),
        # SGK Tavan:
        applyer([
            'girdiler' => 'Bordro\Alan\Is\SGKTavan'
        ]),
        # SSK İşçi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    sskIsci($veriler)
                )($cikti);
            }
        ]),
        # İşsizlik İşçi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    issizlikIsci($veriler),
                )($cikti);
            }
        ]),
        # Gelir Vergisi Matrahı:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    gelirVergisiMatrahi($veriler)
                )($cikti);
            }
        ]),
        # Kümülatif GV:
        applyer([
            'çıktı' => kumulatifGV()
        ]),
        # Gelir Vergisi: (hazır)
        applyer([
            'çıktı' => gelirVergisi($parametreler)
        ]),
        # AGİ Oran:
        applyer([
            'girdiler' =>
                agiOran($parametreler)
        ]),
        # AGİ:
        applyer([
            'çıktı' => function($cikti, $veriler){
                return agiCikti($veriler)($cikti);
            }
        ]),
        # Damga Vergisi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        damgaVergisi($veriler)
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # Net Ücret:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        netUcret()
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # SSK İş veren:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        sskIsveren($veriler)
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # İşsizlik İş veren:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        issizlikIsveren($veriler)
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # Toplam Maaliyet:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        toplamMaliyet()
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # Alan Toplamları:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayReduceWrapper(mergeWith(function ($a, $b) {
                        return $a + $b;
                    }), []),
                    wrapIt(0),
                    \Functional\curry_n(2, 'array_merge')($cikti),
                )
                ($cikti);
            }
        ]),
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
        'girdiler' => $girdiler,
        'çıktı' => []]);
}
