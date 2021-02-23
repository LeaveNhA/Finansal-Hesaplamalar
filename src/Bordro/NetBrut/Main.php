<?php

namespace Bordro\NetBrut;

use function Bordro\Alan\apply;
use function Bordro\Alan\applyer;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\arrayFilterWrapper;
use function Bordro\Alan\Is\agiCikti;
use function Bordro\Alan\Is\agiOran;
use function Bordro\Alan\Is\brutIlkleyici;
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
use function Functional\compose;

function nettenBrutHesapla($parametreler, $girdiler)
{

    $aylar = [[]];
    # veri ata, sonrasında hesaplanacak beş değeri al ve bu değere tekrar ata, hesaplanan değerleri tekrar hesapla ve yakınlık çıkana kadar tekrar et!

    # Eşitlik sağlamak için:
    $ayHesapla = function ($veriler) {
        return function ($ayNo) use ($veriler) {
            return \Functional\compose(
            # Brüt İlkleme:
            function($aylar) use ($ayNo) {
                return applyer([$ayNo =>
                    brutIlkleyici(1.398777468492538)
                ])
                ($aylar);
            },
            # SSK İşçi:
            function($aylar) use ($veriler, $ayNo) {
                return applyer([
                    $ayNo =>
                    sskIsci($veriler)
                ])
                ($aylar);
            },
            # İşsizlik İşçi:
            function($aylar){
            },
            # GV Matrahı:
            # Kümülatif GV (tüm veri):
            # Gelir Vergisi:
            # Damga Vergisi:
            );
        };
    };


    return compose(
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
        # Aylık Net Ücret:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    (\Functional\curry_n(2, 'array_merge'))
                    (['netÜcret' => $veriler['girdiler']['aylıkNetÜcret']])
                ) ($cikti);
            }
        ]),
        applyer([
            'girdiler' => function ($girdiler, $veriler) {
                $girdiler['SGKTavan'] =
                    $veriler['parametreler']['asgariÜcret']
                    *
                    $veriler['parametreler']['SGKTavanOranı'];

                return $girdiler;
            }
        ]),
        applyer([
            'çıktı' => function($cikti, $veriler) use ($ayHesapla) {
                return $ayHesapla($veriler)(0)($cikti);
            }
        ])
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler,
        'çıktı' => []]);
}
