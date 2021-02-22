<?php

namespace Bordro\Tests;

use PHPUnit\Framework\TestCase;
use function Bordro\hesapla;
use const Bordro\KIDEM_TAZMINATI;
use const Bordro\BRUTTEN_NETE;
use const Bordro\IHBAR_TAZMINATI;


final class MainTest extends TestCase
{
    protected $parametreler = [
        'damgaVergisiKatsayısı' => 232.86,
        'kıdemTazminatıTavan' => 7638.69,
        'damgaVergisiKatkısı' => 0.00759,
        #------------------------------------oranlar tam sayı olarak yazılmıştır-------------------------------
        'ihbarSüresiKısıtları' => [['>', 180, 14], ['>', 540, 28], ['>', 1080, 42], ['<', 1080, 56]],
        'vergiDilimiKısıtları' =>
            [[0, 24000, 15],
                [24000, 53000, 20],
                [53000, 190000, 27],
                [190000, 650000, 35],
                [650000, 99999999999999, 40]],
        #------------------------------------------------------------------------------------------------------
        'ilkİkiÇocukOranı' => 7.5,
        'üçüncüÇocukOranı' => 10,
        'dördüncüÇocukVeSonrasıOranı' => 5,
        'asgariÜcret' => 2825.90,
        'brütAsgariÜcret' => 3577, 5,
        'SGKTavanOranı' => 7.5,
        'SSKİşVerenPrimiOranı' => 15.5,
        'SSKİşçiPrimiOranı' => 0.14,
        'SSKİşsizlikİşçiPrimi' => 0.01,
        'işsizlikİşçiPrimi' => 0.01,
        'işsizlikİşVerenPrimiOranı' => 2
    ];

    public function testExpectedResultOfIhbar(): void
    {
        $girdilerIhbar = [
            'adSoyad' => 'Seçkin KÜKRER',
            'sskNo' => '???',
            'işeGiriş' => "2016-01-01",
            'iştenÇıkış' => "2021-01-01",
            'aylıkBrütÜcret' => 15000,
            'kümülatifGelirVergisiMatrahı' => 56000
        ];

        $beklenenCikti = [
            'çalıştığıGünSayısı' => 1828,
            'ihbarSüresiGünü' => 56,
            'brütİhbarTazminatı' => 28000,
            'damgaVergisi' => 212.52,
            'gelirVergisi' => 7560,
            'netİhbarTazminatı' => 20227.48,
        ];

        $this->assertEquals(hesapla(IHBAR_TAZMINATI, $this->parametreler, $girdilerIhbar)['çıktı'], $beklenenCikti, 'Bordro Kıdem hesaplaması temel testi.');
    }

    public function testExpectedResultOfKidem(): void
    {

        $girdilerKidem = [
            'adSoyad' => 'Seçkin KÜKRER',
            'işeGiriş' => "2016-01-01",
            'iştenÇıkış' => "2020-01-01",
            'aylıkBrütÜcret' => 100000,
            'ekÖdemeÜcret' => 0
        ];

        $beklenenCikti = [
            'brütKıdemTazminatı' => 30575.68791780822,
            'damgaVergisi' => 232.0694712961644,
            'netKıdemTazminatı' => 30343.618446512057,
        ];

        $this->assertEquals(hesapla(KIDEM_TAZMINATI, $this->parametreler, $girdilerKidem)['çıktı'], $beklenenCikti, 'Bordro Kıdem hesaplaması temel testi.');
    }

    public function testExpectedResultOfBrutNet(): void
    {
        $agiGirdiler = ['medeniDurum' => 'evli', 'eşininÇalışmaDurumu' => 'çalışmıyor', 'çocukSayısı' => 4];
        $brutNetGirdi = array_merge(['aylıkBrütÜcret' => 7133.77], $agiGirdiler);

        $beklenenCikti = [
            [
                'brütMaaş' => 7133.77,
                'ay' => 0.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 6064.0,
                'gelirVergisi' => 909.6,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5556.03,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 1.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 12128.0,
                'gelirVergisi' => 909.6,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5556.03,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 2.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 18192.0,
                'gelirVergisi' => 909.6,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5556.03,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 3.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 24256.0,
                'gelirVergisi' => 922.4,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5543.23,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 4.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 30320.0,
                'gelirVergisi' => 1212.8,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5252.83,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 5.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 36384.0,
                'gelirVergisi' => 1212.8,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5252.83,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 6.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 42448.0,
                'gelirVergisi' => 1212.8,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5252.83,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 7.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 48512.0,
                'gelirVergisi' => 1212.8,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5252.83,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 8.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 54576.0,
                'gelirVergisi' => 1323.12,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 5142.51,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 9.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 60640.0,
                'gelirVergisi' => 1637.28,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 4828.35,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 10.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 66704.0,
                'gelirVergisi' => 1637.28,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 4828.35,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 7133.77,
                'ay' => 11.0,
                'SSKİşçi' => 998.73,
                'işsizlikİşçi' => 71.34,
                'gelirVergisiMatrahı' => 6064.0,
                'kümülatifGelirVergisi' => 72768.0,
                'gelirVergisi' => 1637.28,
                'AGİ' => 456.07,
                'damgaVergisi' => 54.15,
                'netÜcret' => 4828.35,
                'SSKİşVeren' => 1105.73,
                'işsizlikİşVeren' => 142.68,
                'toplamMaliyet' => 8382.18,
            ],
            [
                'brütMaaş' => 85605.24,
                'ay' => 66.0,
                'SSKİşçi' => 11984.73,
                'işsizlikİşçi' => 856.05,
                'gelirVergisiMatrahı' => 72768.0,
                'kümülatifGelirVergisi' => 472992.0,
                'gelirVergisi' => 14737.36,
                'AGİ' => 5472.81,
                'damgaVergisi' => 649.74,
                'netÜcret' => 62850.16,
                'SSKİşVeren' => 13268.81,
                'işsizlikİşVeren' => 1712.1,
                'toplamMaliyet' => 100586.16,
            ]
        ];

        $this->assertEquals(hesapla(BRUTTEN_NETE, $this->parametreler, $brutNetGirdi)['çıktı'], $beklenenCikti, 'Bordro Brüt-Net hesaplaması temel testi.');
    }
}