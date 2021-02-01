<?php

# TODO: ortak hesaplamaları ekle!

function hesapla($parametreler, $girdiler)
{
    $toDateTime = function ($a) {
        return new \DateTime($a);
    };

    return \Functional\compose(
    # Girdiyi yapılandır!
        applyer([
            'girdiler' => applyer([
                'işeGiriş' => $toDateTime,
                'iştenÇıkış' => $toDateTime
            ])
        ]),
        applyer([
            'girdiler' => function ($girdiler) {
                $girdiler['toplamBrütÜcret'] =
                    $girdiler['aylıkBrütÜcret'] + $girdiler['ekÖdemeÜcret'];

                return $girdiler;
            }
        ]),
        # Hesaplamaları yap!
        # Çalıştığı Gün Sayısı:
        applyer([
            'girdiler' => 'calistigiGunHesapla'
        ]),
        # Brüt Kıdem Tazminat:
        function ($veriler) {
            $temelCarpan = min($veriler['parametreler']['kıdemTazminatıTavan'], $veriler['girdiler']['toplamBrütÜcret']);

            $veriler['girdiler']['brütKıdemTazminatı'] = $temelCarpan * $veriler['girdiler']['çalıştığıGünSayısı'] / 365;

            return $veriler;
        },
        # Damga Vergisi Katkısı:
        function ($veriler) {
            $veriler['girdiler']['damgaVergisi'] = $veriler['girdiler']['brütKıdemTazminatı'] * $veriler['parametreler']['damgaVergisiKatkısı'];

            return $veriler;
        },
        # Net Kıdem Tazminatı
        function ($veriler) {
            $veriler['girdiler']['netKıdemTazminatı'] = $veriler['girdiler']['brütKıdemTazminatı'] - $veriler['girdiler']['damgaVergisi'];

            return $veriler;
        },
        # Çıktı bilgilerini çek.
        function ($veriler) {
            $veriler['çıktı'] = \Functional\select_keys($veriler['girdiler'], ['çalıştığıGün', 'brütKıdemTazminatı', 'damgaVergisi', 'netKıdemTazminatı']);

            return $veriler;
        }
    )
    (['parametreler' => $parametreler,
        'girdiler' => $girdiler]);
}

/*
 * Test değerleri:
 */

/*
$parametreler = [
    'damgaVergisiKatsayısı' => 232.86,
    'kıdemTazminatıTavan' => 7638.69,
    'damgaVergisiKatkısı' => 0.00759
];
$girdiler = [
    'adSoyad' => 'Seçkin KÜKRER',
    'işeGiriş' => "2016-01-01",
    'iştenÇıkış' => "2020-01-01",
    'aylıkBrütÜcret' => 100000,
    'ekÖdemeÜcret' => 0
];

var_dump(
    hesapla($parametreler, $girdiler)
);
*/