<?php

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
                return arrayMapWrapper('identity')
                (range(0, 12));
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
            'girdiler' => function ($girdiler, $veriler) {
                $girdiler['SGKTavan'] =
                    $veriler['parametreler']['asgariÜcret']
                    *
                    $veriler['parametreler']['SGKTavanOranı'];

                return $girdiler;
            }
        ]),
        # SSK İşçi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    function ($ay) use ($veriler) {
                        return \Functional\compose(
                            multiplyWith($veriler['parametreler']['SSKİşçiPrimiOranı']),
                            wrapIt('SSKİşçi'),
                            (\Functional\curry_n(2, 'array_merge'))
                            ($ay)
                        )
                        (min($veriler['girdiler']['SGKTavan'], $ay['brütMaaş']));
                    }
                )($cikti);
            }
        ]),
        # İşsizlik İşçi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    function ($ay) use ($veriler) {
                        return \Functional\compose(
                            multiplyWith($veriler['parametreler']['işsizlikİşçiPrimi']),
                            wrapIt('işsizlikİşçi'),
                            (\Functional\curry_n(2, 'array_merge'))
                            ($ay)
                        )
                        (min($veriler['girdiler']['SGKTavan'], $ay['brütMaaş']));
                    }
                )($cikti);
            }
        ]),
        # Gelir Vergisi Matrahı:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return arrayMapWrapper(
                    function ($ay) use ($veriler) {
                        return \Functional\compose(
                            function ($ay) {
                                return \Functional\select_keys($ay, ['brütMaaş', 'SSKİşçi', 'işsizlikİşçi']);
                            },
                            arrayReduceWrapper(function ($a, $b) {
                                return abs((int)$a) - (int)$b;
                            }),
                            wrapIt('gelirVergisiMatrahı'),
                            (\Functional\curry_n(2, 'array_merge'))
                            ($ay)
                        )
                        ($ay);
                    }
                )($cikti);
            }
        ]),
        # Kümülatif GV:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        \Functional\compose(
                            function ($ayNo) use ($cikti) {
                                return arrayFilterWrapper(function ($ay) use ($ayNo) {
                                    return $ay['ay'] <= $ayNo;
                                })
                                ($cikti);
                            },
                            arrayReduceWrapper(function ($toplam, $ay) {
                                return $toplam + $ay['gelirVergisiMatrahı'];
                            }, 0),
                            wrapIt('kümülatifGelirVergisi'),
                        )
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                    function ($a) {
                        return array_slice($a, 0, 12);
                    }
                )
                (range(0, 11));
            }
        ]),
        # Gelir Vergisi: (hazır)
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        \Functional\compose(
                            lookUp('gelirVergisiMatrahı'),
                            gelirVergisiDilimleri($veriler['parametreler']),
                            wrapIt('gelirVergisi')
                        )
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
                wrapItWith('agiOranı')
                (\Functional\compose(
                    agiHesapla($parametreler),
                    lookUp('sonuç')
                ))
        ]),
        # AGİ:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        \Functional\compose(
                            lookUp('brütMaaş'),
                            function ($a) use ($veriler) {
                                return $a / 100 * $veriler['girdiler']['agiOranı']
                                    / 100 * $veriler['parametreler']['vergiDilimiKısıtları'][0][1];
                            },
                            wrapIt('AGİ')
                        )
                    ),
                    \Functional\curry_n(3, 'array_map')
                    ('array_merge')
                    ($cikti),
                )
                ($cikti);
            }
        ]),
        # Damga Vergisi:
        applyer([
            'çıktı' => function ($cikti, $veriler) {
                return \Functional\compose(
                    arrayMapWrapper(
                        \Functional\compose(
                            lookUp('brütMaaş'),
                            function ($a) use ($veriler) {
                                return $a * $veriler['parametreler']['damgaVergisiKatkısı'];
                            },
                            wrapIt('damgaVergisi')
                        )
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
                        \Functional\compose(
                            function ($a) {
                                return \Functional\select_keys($a,
                                    ['brütMaaş', 'SSKİşçi', 'işsizlikİşçi', 'gelirVergisi', 'AGİ', 'damgaVergisi']);
                            },
                            function ($v){
                                return
                                    $v['brütMaaş']
                                    -
                                    $v['SSKİşçi']
                                    -
                                    $v['işsizlikİşçi']
                                    -
                                    $v['gelirVergisi']
                                    +
                                    $v['AGİ']
                                    -
                                    $v['damgaVergisi'];
                            },
                            wrapIt('netÜcret')
                        )
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
                        \Functional\compose(
                            lookUp('brütMaaş'),
                            \Functional\curry_n(2, 'min')
                            ($veriler['girdiler']['SGKTavan']),
                            function($a) use ($veriler) {
                                return $veriler['parametreler']['SSKİşVerenPrimiOranı']
                                    / 100 * $a;
                            },
                            wrapIt('SSKİşVeren')
                        )
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
                        \Functional\compose(
                            lookUp('brütMaaş'),
                            \Functional\curry_n(2, 'min')
                            ($veriler['girdiler']['SGKTavan']),
                            function($a) use ($veriler) {
                                return $veriler['parametreler']['işsizlikİşVerenPrimiOranı']
                                    / 100 * $a;
                            },
                            wrapIt('işsizlikİşVeren')
                        )
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
                        \Functional\compose(
                            function($v){
                                return \Functional\select_keys($v, ['brütMaaş', 'SSKİşVeren', 'işsizlikİşVeren']);
                            },
                            arrayReduceWrapper(function($a, $b){ return $a + $b; }, 0),
                            wrapIt('toplamMaliyet')
                        )
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
                    arrayReduceWrapper(mergeWith(function($a, $b){ return $a + $b; }), []),
                    wrapIt(0),
                    \Functional\curry_n(2, 'array_merge')($cikti),
                )
                ($cikti);
            }
        ])
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler,
        'çıktı' => []]);
}