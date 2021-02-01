<?php

require_once('./Alan/OrtakHesaplamalar.php');

use Bordro\Alan\toDateTime;

# Cover the CONSTANTS!
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

$externalPackageFlag = (bool)1;
if ($externalPackageFlag) {
    require_once('./../../vendor/autoload.php');
}


function hesapla_($parametreler, $girdiler)
{
    # İhbar Tazminatı!
    return \Functional\compose(
    # Girdiyi yapılandır!
        applyer([
            'girdiler' => applyer([
                'işeGiriş' => 'toDateTime',
                'iştenÇıkış' => 'toDateTime'
            ])
        ]),
        # Çalıştığı Gün hespalama!
        applyer([
            'girdiler' => 'calistigiGunHesapla'
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
        }
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler]);
}

function hesapla($parametreler, $girdiler)
{
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
    # TODO: İşsizlik İş veren:
    # TODO: Toplam Maaliyet:
    # TODO: Alan Toplamları:
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler,
        'çıktı' => []]);
}

$parametreler = [
    'damgaVergisiKatsayısı' => 232.86,
    'kıdemTazminatıTavan' => 7638.69,
    'damgaVergisiKatkısı' => 0.00759,
    #------------------------------------oranlar 10 ile çarpılmıştır---------------------------------------
    'ihbarSüresiKısıtları' => [['>', 180, 14], ['>', 540, 28], ['>', 1080, 42], ['<', 1080, 56]],
    'vergiDilimiKısıtları' => [[24000, 15], [53000, 20], [190000, 27], [650000, 35], [99999999999999, 40]],
    #------------------------------------------------------------------------------------------------------
    'ilkİkiÇocukOranı' => 7.5,
    'üçüncüÇocukOranı' => 10,
    'dördüncüÇocukVeSonrasıOranı' => 5,
    'asgariÜcret' => 2825.90,
    'SGKTavanOranı' => 7.5,
    'SSKİşVerenPrimiOranı' => 15.5,
    'SSKİşçiPrimiOranı' => 0.14,
    'SSKİşsizlikİşçiPrimi' => 0.01,
    'işsizlikİşçiPrimi' => 0.01
];
$girdilerIhbar = [
    'adSoyad' => 'Seçkin KÜKRER',
    'sskNo' => '???',
    'işeGiriş' => "2016-01-01",
    'iştenÇıkış' => "2021-01-01",
    'aylıkBrütÜcret' => 15000,
    'kümülatifGelirVergisiMatrahı' => 56000
];

$agiGirdiler = ['medeniDurum' => 'evli', 'eşininÇalışmaDurumu' => 'çalışmıyor', 'çocukSayısı' => 2];

var_dump(
    hesapla($parametreler, array_merge(['aylıkBrütÜcret' => 4000], $agiGirdiler))
# ihbarSuresiHesaplama($parametreler)($girdiler)
# gelirVergisiDilimleri($parametreler)($girdiler['kümülatifGelirVergisiMatrahı'])
# gelirVergisiDilimleri($parametreler)(84000)
# applyer(['a' => 'identity', 'b' => function(){return 1;}])(['a' => 1])
# agiHesapla($parametreler)($agiGirdiler)
# agiCocukSayisi($parametreler)($agiGirdiler)
);