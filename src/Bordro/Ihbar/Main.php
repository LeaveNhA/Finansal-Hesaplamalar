<?php

function ihbarTazminatiHesapla($parametreler, $girdiler)
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

