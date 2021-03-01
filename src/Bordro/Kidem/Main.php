<?php
namespace Bordro\Kidem;

use function Bordro\Alan\apply;
use function Bordro\Alan\applyer;
use function Bordro\Alan\arrayMapWrapper;
use function Bordro\Alan\arrayReduceWrapper;
use function Bordro\Alan\arrayFilterWrapper;
use function Bordro\Alan\Is\agiCikti;
use function Bordro\Alan\Is\agiOran;
use function Bordro\Alan\Is\brutMaas;
use function Bordro\Alan\Is\damgaVergisi;
use function Bordro\Alan\Is\gelirVergisi;
use function Bordro\Alan\Is\gelirVergisiDilimle;
use function Bordro\Alan\Is\gelirVergisiMatrahi;
use function Bordro\Alan\Is\issizlikIsci;
use function Bordro\Alan\Is\issizlikIsveren;
use function Bordro\Alan\Is\kidemTazminatTavan;
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

# TODO: ortak hesaplamaları ekle!

function kidemTazminatiHesapla($parametreler, $girdiler)
{
    $toDateTime = function ($a) {
        return new \DateTime($a);
    };

    $calistigiGunKisit = function($gun){
        if($gun < 366)
            throw new \Exception('Kıdem tazminatı için gerekli çalışma süresi, kesintisiz en az bir yıldır!');
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
            'girdiler' => 'Bordro\Alan\calistigiGunHesapla'
        ]),
        applyer([
            'girdiler' => applyer([
                'çalıştığıGünSayısı' => apply($calistigiGunKisit)
            ])
        ]),
        # Brüt Kıdem Tazminat:
        function ($veriler) {
            $kidemTazminatTavan = kidemTazminatTavan($veriler['parametreler'])
            ($veriler['girdiler']['iştenÇıkış']->format('Y'))
            (intval($veriler['girdiler']['iştenÇıkış']->format('n') < 7 ? 0 : 1));
            $temelCarpan = min($kidemTazminatTavan, $veriler['girdiler']['toplamBrütÜcret']);

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
        },
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
        'girdiler' => $girdiler]);
}