<?php

namespace Bordro\Alan;

# Essential functions for FP.

use function Functional\compose;

function mergeIt($m){
    return \Functional\curry_n(3, 'array_map')
    ('array_merge')
    (m);
}

function inRange($a){
    return function($b) use ($a) {
        return function($v) use ($b, $a) {
            return ($v >= $a && $v <= $b);
        };
    };
}

function iterate($iterateFn)
{
    return function ($stopFn, $stopCount = 100) use ($iterateFn) {
        $fn_ = (new class {
            function __invoke($value, $stopCount, $iterateFn, $stopFn) {
                return ($stopCount < 0 || $stopCount === 0) || ($stopFn($value) === true) ? $value : $this($iterateFn($value), $stopCount - 1, $iterateFn, $stopFn);
            }
        });

        return function($value) use ($stopCount, $stopFn, $iterateFn, $fn_) {
            return $fn_($value, $stopCount, $iterateFn, $stopFn);
        };
    };
}

function identity($a)
{
    return $a;
}

function id($a){
    return $a;
}

function apply($fn)
{
    return function ($i) use ($fn) {
        $fn($i);
        return $i;
    };
}

function wrapItWith($key)
{
    return function ($fn) use ($key) {
        return function ($value) use ($key, $fn) {
            $value[$key] = $fn($value);

            return $value;
        };
    };
}

function multiplyWith($a)
{
    return function ($b) use ($a) {
        return $a * $b;
    };
}

function zip($arr_)
{
    return function ($arr2_) use ($arr_) {
        return array_map('array_merge', $arr_, $arr2_);
    };
}

function lookUp($key)
{
    return function ($value) use ($key) {
        return $value[$key];
    };
}

function wrapIt($key)
{
    return function ($value) use ($key) {
        return [$key => $value];
    };
}

function mergeWith($fn)
{
    return function ($arr1, $arr2) use ($fn) {
        $result = [];

        foreach ($arr1 as $k => $v)
            $result[$k] = $fn($v, $arr2[$k]);

        return array_merge($arr1, $arr2, $result);
    };
}

function ifFn($trueFn, $falseFn, $condition)
{
    if ($condition === true) {
        return $trueFn();
    } else {
        return $falseFn();
    }
}

function nullWrapper($fn)
{
    return function ($arg) use ($fn) {
        return is_null($arg) ? null : $fn($arg);
    };
}

function arrayMapWrapper($fn)
{
    return function ($data) use ($fn) {
        return array_map($fn, $data);
    };
}

function arrayReduceWrapper($fn, $init = null)
{
    return function ($data) use ($init, $fn) {
        return $init === null ? array_reduce($data, $fn) : array_reduce($data, $fn, $init);
    };
}

function arrayFilterWrapper($fn)
{
    return function ($data) use ($fn) {
        return array_filter($data, $fn);
    };
}

function arrayMerger()
{
    return function ($arr) {
        return array_reduce(
            $arr,
            function ($acc, $arr) {
                return array_merge($acc, $arr);
            },
            []
        );
    };
}

function imageToBase64Image()
{
    return \Functional\compose(
        'file_get_contents',
        'base64_encode'
    );
}

function applyer($applyMap)
{
    return function ($data) use ($applyMap) {
        $arrayData = (array)$data;

        $result = array_map(
            function ($key, $value) use ($applyMap, $data) {
                if (!isset($value) || is_null($value))
                    return [$key => null];

                if (array_key_exists($key, $applyMap)) {
                    $fn = $applyMap[$key];
                } else {
                    $fn = function ($a) {
                        return $a;
                    };
                }

                return [$key => $fn($value, $data)];
            },
            array_keys($arrayData),
            $arrayData
        );

        return array_reduce($result,
            function ($acc, $v) {
                return array_merge($acc, $v);
            }, []);
    };
}

# Hesaplamalar:

function toDateTime($a)
{
    return new \DateTime($a);
}

function calistigiGunHesapla($girdi)
{
    $girdi['çalıştığıGünSayısı'] = $girdi['iştenÇıkış']->diff($girdi['işeGiriş'])->format("%a");

    return $girdi;
}

function karsilastirma($deger)
{
    return function ($kisitFn) use ($deger) {
        return $kisitFn($deger);
    };
}

function vergiDilimiHesaplariDonusumleri($degerYapisi, $kisit)
{
    $deger = $degerYapisi['deger'];
    $kisitDegeri = $kisit[0];
    $kisitOrani = $kisit[1];

    $kisitOranSonucu = $deger <= 0 ? 0 : ($deger - $kisitDegeri >= 0 ? $kisitDegeri : $deger);
    $yeniDeger = $deger <= 0 ? 0 : ($deger >= $kisitDegeri ? ($deger - $kisitDegeri) : 0);
    $sonuc = array_merge($kisit, [$kisitOranSonucu / 100 * $kisitOrani]);

    # var_dump([$deger, $kisitDegeri, $yeniDeger, $kisitOranSonucu, $sonuc]);

    $degerYapisi['deger'] = $yeniDeger;
    $degerYapisi['degerler'] = array_merge($degerYapisi['degerler'], [$sonuc]);

    return $degerYapisi;
}

function karsilastirmaFonksiyonuDonusumleri($kisit)
{
    return ([
        '<' => function ($a) use ($kisit) {
            return function ($b) use ($kisit, $a) {
                return $a < $b ? $kisit : false;
            };
        },
        '>' => function ($a) use ($kisit) {
            return function ($b) use ($kisit, $a) {
                return $a > $b ? $kisit : false;
            };
        },
        '=' => function ($a) use ($kisit) {
            return function ($b) use ($kisit, $a) {
                return $a == $b ? $kisit : false;
            };
        }
    ])[$kisit[0]]($kisit[1]);
}

function ihbarSuresiHesaplama($parametreler)
{
    return function ($girdiler) use ($parametreler) {
        $girdiler['ihbarSüresiGünü'] = \Functional\compose(
            arrayMapWrapper(
                function ($a) {
                    return karsilastirmaFonksiyonuDonusumleri($a);
                }
            ),
            arrayMapWrapper(
                function ($f) use ($girdiler) {
                    return $f($girdiler['çalıştığıGünSayısı']);
                }
            ),
            # identity placeholder for some strange PHP bug.
            arrayFilterWrapper(function ($a) {
                return $a;
            }),
            'array_values',
            function ($array) {
                return $array[0];
            },
            function ($array) {
                return $array[2];
            },
        )
        ($parametreler['ihbarSüresiKısıtları']);

        return $girdiler;
    };
}

function agiEsDurumu($parametreler)
{
    $calismayanEsOrani = $parametreler['çalışmayanEşOranı'] = 10;

    return function ($m) use ($calismayanEsOrani) {
        return $m['medeniDurum'] == 'bekar' ? 0 :
            ($m['eşininÇalışmaDurumu'] == 'çalışmıyor' ? $calismayanEsOrani : 0);
    };
}

function agiCocukSayisi($parametreler)
{
    $ilkIkiCocukOrani = $parametreler['ilkİkiÇocukOranı'];
    $ucuncuCocukOrani = $parametreler['üçüncüÇocukOranı'];
    $dortVeSonrasiOrani = $parametreler['dördüncüÇocukVeSonrasıOranı'];

    return function ($veriler) use ($ilkIkiCocukOrani, $ucuncuCocukOrani, $dortVeSonrasiOrani) {
        $m = $veriler['çocukSayısı'];

        switch ($m) {
            case 1:
                return $ilkIkiCocukOrani;
            case 2:
                return $ilkIkiCocukOrani * 2;
            case 3:
                return $ilkIkiCocukOrani * 2 + $ucuncuCocukOrani;
            default:
                return ($m > 3) ?
                    $ilkIkiCocukOrani * 2 + $ucuncuCocukOrani + ($m * $dortVeSonrasiOrani)
                    : 0;
        }
    };
}

function agiHesapla($parametreler)
{
    return function ($agiVerileri) use ($parametreler) {
        return \Functional\compose(
        # Kendi için:
            function ($veriler) {
                $veriler['sonuç'] = 50;

                return $veriler;
            },
            # Eş çalışma durumu:
            function ($veriler) use ($parametreler) {
                $veriler['sonuç'] += agiEsDurumu($parametreler)($veriler);

                return $veriler;
            },
            # Çocuklar için:
            function ($veriler) use ($parametreler) {
                $veriler['sonuç'] += agiCocukSayisi($parametreler)($veriler);

                return $veriler;
            },
            applyer([
                'sonuç' => function ($n) {
                    return min($n, 85);
                }
            ])
        )
        ($agiVerileri);
    };
}

function vergiDilimiIslemi($deger)
{
    return function ($vergiDilimi) use ($deger) {
        list($baslangicKisit, $bitisKisit, $oran) = $vergiDilimi;
        return array_merge(
            $vergiDilimi,
            [
                max(min($deger - $baslangicKisit, ($bitisKisit - $baslangicKisit)), 0) / 100 * $oran
            ]
        );
    };
}

function gelirVergisiDilimleri($parametreler)
{
    $kisitlar = $parametreler['vergiDilimiKısıtları'];

    return function ($deger) use ($kisitlar) {
        return \Functional\compose(
            arrayMapWrapper(vergiDilimiIslemi($deger)),
            arrayMapWrapper('\Functional\last'),
            arrayReduceWrapper(function ($a, $b) {
                return $a + $b;
            })
        )
        ($kisitlar);
    };
}

function gelirVergisiDilimiBul($parametreler)
{
    $kisitlar = $parametreler['vergiDilimiKısıtları'];

    return function ($deger) use ($kisitlar) {
        return \Functional\compose(
            arrayMapWrapper(vergiDilimiIslemi($deger)),
            arrayFilterWrapper(
                \Functional\compose(
                    '\Functional\last',
                    'identity'
                )
            ),
            '\Functional\last',
            lookUp(2)
        )
        ($kisitlar);
    };
}
