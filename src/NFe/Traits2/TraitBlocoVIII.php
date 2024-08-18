<?php
namespace NFePHP\DA\NFe\Traits2;

use Com\Tecnick\Barcode\Barcode;

/**
 * Bloco QRCode
 */
trait TraitBlocoVIII
{
    protected function blocoVIII($y)
    {
        //$this->bloco8H = 50;
        $y += 1;
        
        /*
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco8H, '', $aFont, 'T', 'C', true, '', false);
        */
        
        $maxW = $this->wPrint;
        $w = ($maxW*1)+4;
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj(
            'QRCODE,M',
            $this->qrCode,
            -4,
            -4,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        $qrcode = $bobj->getPngData();
        $wQr = 50;
        $hQr = 50;
        $yQr = ($y);
        $xQr = ($w/2) - ($wQr/2);
        $pic = 'data://text/plain;base64,' . base64_encode($qrcode);
        $info = getimagesize($pic);
        $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');


        // if($this->venda->tipo_pagamento == 17 && $this->venda->qr_code_base64 != ""){

        //     $pic = 'data:image/jpeg;base64,' . $this->venda->qr_code_base64;

        //     $y += 1;
        //     $maxW = $this->wPrint;
        //     $w = ($maxW*1)+4;
        //     $barcode = new Barcode();
        //     $bobj = $barcode->getBarcodeObj(
        //         'QRCODE,M',
        //         $this->qrCode,
        //         -4,
        //         -4,
        //         'black',
        //         array(-2, -2, -2, -2)
        //     )->setBackgroundColor('white');
        //     $qrcode = $bobj->getPngData();
        //     $wQr = 50;
        //     $hQr = 50;
        //     $yQr = ($y)+54;
        //     $xQr = ($w/2) - ($wQr/2);

        //     // $this->pTextBox(25, $xp+1, 50, 10, "Utilize o QrCode para pagar", $aFont, 'T', 'L', 0, '', false);
        //     $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');

        //     $this->pdf->textBox(
        //         $this->margem,
        //         $y+50,
        //         $this->wPrint,
        //         2,
        //         "Utilize o QrCode abaixo para pagar",
        //         $aFont,
        //         'T',
        //         'C',
        //         false,
        //         '',
        //         false
        //     );


        //     $this->pdf->image($pic, $xQr, $yQr, $wQr, $hQr, 'PNG');

        // }

        return $this->bloco8H+$y;
    }
}
