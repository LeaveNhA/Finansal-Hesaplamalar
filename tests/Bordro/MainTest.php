<?php

namespace Bordro\Tests;

use PHPUnit\Framework\TestCase;
use function Bordro\hesapla;

final class MainTest extends TestCase
{
    public function testExpectedResult(): void
    {
        $parametreler = [
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
            'brütAsgariÜcret' => 3577,5,
            'SGKTavanOranı' => 7.5,
            'SSKİşVerenPrimiOranı' => 15.5,
            'SSKİşçiPrimiOranı' => 0.14,
            'SSKİşsizlikİşçiPrimi' => 0.01,
            'işsizlikİşçiPrimi' => 0.01,
            'işsizlikİşVerenPrimiOranı' => 2
        ];

        $girdilerIhbar = [
            'adSoyad' => 'Seçkin KÜKRER',
            'sskNo' => '???',
            'işeGiriş' => "2016-01-01",
            'iştenÇıkış' => "2021-01-01",
            'aylıkBrütÜcret' => 15000,
            'kümülatifGelirVergisiMatrahı' => 56000
        ];

        $parametrelerKidem = [
            'damgaVergisiKatsayısı' => 232.86,
            'kıdemTazminatıTavan' => 7638.69,
            'damgaVergisiKatkısı' => 0.00759
        ];
        $girdilerKidem = [
            'adSoyad' => 'Seçkin KÜKRER',
            'işeGiriş' => "2016-01-01",
            'iştenÇıkış' => "2020-01-01",
            'aylıkBrütÜcret' => 100000,
            'ekÖdemeÜcret' => 0
        ];

        $agiGirdiler = ['medeniDurum' => 'evli', 'eşininÇalışmaDurumu' => 'çalışmıyor', 'çocukSayısı' => 4];
        $brutNetGirdi = array_merge(['aylıkBrütÜcret' => 7133.77], $agiGirdiler);

        $this->assertTrue(hesapla('brütnet', $parametreler, $brutNetGirdi), 'Bordro Kıdem hesaplaması temel testi.');
    }
}