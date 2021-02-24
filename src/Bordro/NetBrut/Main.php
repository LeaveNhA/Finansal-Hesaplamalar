<?php

namespace Bordro\NetBrut;

use function Bordro\Alan\apply;
use function Bordro\Alan\applyer;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayMerger;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\arrayFilterWrapper;
use function Bordro\Alan\inRange;
use function Bordro\Alan\Is\agiCikti;
use function Bordro\Alan\Is\agiOran;
use function Bordro\Alan\Is\brut;
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
use function Bordro\Alan\Is\toplamEleGecen;
use function Bordro\Alan\Is\toplamMaliyet;
use function Bordro\Alan\iterate;
use function Bordro\Alan\mergeIt;
use function Bordro\Alan\mergeWith;
use function Bordro\Alan\wrapIt;
use function Bordro\Alan\wrapItWith;
use function Bordro\Alan\agiHesapla;
use function Bordro\Alan\lookUp;
use function Bordro\Alan\multiplyWith;
use function Bordro\Alan\zip;
use function Bordro\Alan\gelirVergisiDilimleri;
use function Functional\compose;
use function Functional\select_keys;

function nettenBrutHesapla($parametreler, $girdiler)
{
    # Eşitlik sağlamak için:
    $ayHesapla = function ($veriler) {
        return function ($ayNo) use ($veriler) {
            return \Functional\compose(
                # Başlangıç Brüt Hesaplayıcısı:
                applyer([
                    $ayNo => brut()
                ]),
                # SSK İşçi:
                function ($aylar) use ($veriler, $ayNo) {
                    return applyer([
                        $ayNo =>
                            sskIsci($veriler)
                    ])
                    ($aylar);
                },
                # İşsizlik İşçi:
                function ($aylar) use ($ayNo, $veriler) {
                    return applyer([
                        $ayNo =>
                            issizlikIsci($veriler)
                    ])
                    ($aylar);
                },
                # GV Matrahı:
                function ($aylar) use ($ayNo, $veriler) {
                    return applyer([
                        $ayNo => gelirVergisiMatrahi($veriler)
                    ])
                    ($aylar);
                },
                # Kümülatif GV (tüm veri):
                kumulatifGV(),
                # Gelir Vergisi:
                gelirVergisi($veriler['parametreler']),
                # Damga Vergisi:
                applyer([
                    $ayNo => function ($ay) use ($veriler) {
                        $result_ = damgaVergisi($veriler)($ay);
                        return array_merge($ay, $result_);
                    }
                ]),
            );
        };
    };

    ini_set('memory_limit', '2048M');

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
                    (['netMaaş' => $veriler['girdiler']['aylıkNetMaaş']])
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
        # Brüt ilkleyici:
        applyer([
            'çıktı' => arrayMapWrapper(
                brutIlkleyici(1.398777468492538)
            )
        ]),
        function ($veriler) use ($ayHesapla) {
            $cikti_ = arrayReduceWrapper(function ($cikti, $ayNo) use ($ayHesapla, $veriler) {
                return iterate(
                    $ayHesapla($veriler)($ayNo)
                )
                (function ($aylar) use ($ayNo) {
                    $a_ = array_key_exists('brütMaaş', $aylar[$ayNo]) ? $aylar[$ayNo]['brütMaaş'] : false;
                    $brut_ = brut()($aylar[$ayNo]);
                    $b_ = array_key_exists('brütMaaş', $brut_) ? $brut_['brütMaaş'] : false;

                    if($a_ === false || $b_ === false)
                        return false;

                    $diff_ = abs($a_ - $b_);
                    $result_ = inRange(0)(0.1)
                    ($diff_);
                    return $result_;
                })
                ($cikti);
            }, $veriler['çıktı'])
            (range(0, count($veriler['çıktı']) - 1));

            $veriler['çıktı'] = $cikti_;

            return $veriler;
        },
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
        # Toplam Ele Geçen:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        toplamEleGecen()
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