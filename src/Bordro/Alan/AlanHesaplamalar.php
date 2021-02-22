<?php

namespace Bordro\Alan\Is;

use function Bordro\Alan\agiHesapla;
use function Bordro\Alan\apply;
use function Bordro\Alan\arrayFilterWrapper;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\gelirVergisiDilimleri;
use function Bordro\Alan\lookUp;
use function Bordro\Alan\multiplyWith;
use function Bordro\Alan\wrapIt;
use function Bordro\Alan\wrapItWith;
use function Bordro\Alan\zip;

function sskIsci($veriler)
{
    return function($ay) use ($veriler) {
        return \Functional\compose(
            multiplyWith($veriler['parametreler']['SSKİşçiPrimiOranı']),
            wrapIt('SSKİşçi'),
            (\Functional\curry_n(2, 'array_merge'))
            ($ay)
        )
        (min($veriler['girdiler']['SGKTavan'], $ay['brütMaaş']));
    };
}

function issizlikIsci($veriler) {
    return function($ay) use ($veriler) {
        return \Functional\compose(
            multiplyWith($veriler['parametreler']['işsizlikİşçiPrimi']),
            wrapIt('işsizlikİşçi'),
            (\Functional\curry_n(2, 'array_merge'))
            ($ay)
        )
        (min($veriler['girdiler']['SGKTavan'], $ay['brütMaaş']));
    };
}

function gelirVergisiMatrahi($veriler) {
    return function($ay){
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
    };
}

function kumulatifGV(){
    return function($cikti) {
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
            zip($cikti)
        )
        (range(0, count($cikti) - 1));
    };
}

function gelirVergisiDilimle($parametreler){
    return \Functional\compose(
            lookUp('kümülatifGelirVergisi'),
            gelirVergisiDilimleri($parametreler),
            wrapIt('gelirVergisi')
        );
}

function gelirVergisi($parametreler){
    return function($aylar) use ($parametreler) {
        $vergiler_ = \Functional\compose(
            arrayMapWrapper(
                gelirVergisiDilimle($parametreler)
            ),
            \Functional\curry_n(3, 'array_map')
            ('array_merge')
            ($aylar),
        )
        ($aylar);

        return \Functional\compose(
            arrayMapWrapper(function ($rangeEnd) use ($vergiler_) {
                return \Functional\select_keys($vergiler_, range(max(0, $rangeEnd - 1), $rangeEnd));
            }),
            arrayMapWrapper(
                arrayMapWrapper(lookUp('gelirVergisi'))
            ),
            arrayMapWrapper(
                'array_reverse'
            ),
            arrayMapWrapper(
                arrayReduceWrapper(function ($a, $b) {
                    return $b - $a;
                }, 0)
            ),
            arrayMapWrapper('abs'),
            arrayMapWrapper(wrapIt('gelirVergisi')),
            \Functional\curry_n(3, 'array_map')
            ('array_merge')
            ($aylar),
        )
        (range(0, count($aylar) - 1));
    };
}

function agiOran($parametreler){
    return wrapItWith('agiOranı')
        (\Functional\compose(
            agiHesapla($parametreler),
            lookUp('sonuç')
        ));
}

function agi($veriler){
    return \Functional\compose(
        lookUp('brütMaaş'),
        function ($brutUcret) use ($veriler) {
            return min(
                $brutUcret
                * ($veriler['girdiler']['agiOranı'] / 100)
                * ($veriler['parametreler']['vergiDilimiKısıtları'][0][2] / 100),
                $veriler['parametreler']['brütAsgariÜcret']
                * ($veriler['girdiler']['agiOranı'] / 100)
                * ($veriler['parametreler']['vergiDilimiKısıtları'][0][2] / 100),
            );
        },
        wrapIt('AGİ')
    );
}

function agiCikti($veriler){
    return function($aylar) use ($veriler) {
        return \Functional\compose(
            arrayMapWrapper(
                agi($veriler)
            ),
            \Functional\curry_n(3, 'array_map')
            ('array_merge')
            ($aylar),
        )
        ($aylar);
    };
}

function damgaVergisi($veriler){
    return \Functional\compose(
            lookUp('brütMaaş'),
            multiplyWith($veriler['parametreler']['damgaVergisiKatkısı']),
            wrapIt('damgaVergisi')
        );
}

function netUcret(){
    return \Functional\compose(
        function ($a) {
            return \Functional\select_keys($a,
                ['brütMaaş', 'SSKİşçi', 'işsizlikİşçi', 'gelirVergisi', 'AGİ', 'damgaVergisi']);
        },
        function ($v) {
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
    );
}

function sskIsveren($veriler){
    return \Functional\compose(
            lookUp('brütMaaş'),
            \Functional\curry_n(2, 'min')
            ($veriler['girdiler']['SGKTavan']),
            function ($a) use ($veriler) {
                return $veriler['parametreler']['SSKİşVerenPrimiOranı']
                    / 100 * $a;
            },
            wrapIt('SSKİşVeren')
        );
}

function issizlikIsveren($veriler){
    return \Functional\compose(
            lookUp('brütMaaş'),
            \Functional\curry_n(2, 'min')
            ($veriler['girdiler']['SGKTavan']),
            multiplyWith($veriler['parametreler']['işsizlikİşVerenPrimiOranı'] / 100),
            wrapIt('işsizlikİşVeren')
        );
}

function toplamMaliyet(){
    return \Functional\compose(
            function ($v) {
                return \Functional\select_keys($v, ['brütMaaş', 'SSKİşVeren', 'işsizlikİşVeren']);
            },
            arrayReduceWrapper(function ($a, $b) {
                return $a + $b;
            }, 0),
            wrapIt('toplamMaliyet')
        );
}