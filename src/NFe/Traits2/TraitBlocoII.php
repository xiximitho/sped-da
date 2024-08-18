<?php

namespace NFePHP\DA\NFe\Traits2;

/**
 * Bloco sub cabecalho com a identificação e logo do emitente
 */
trait TraitBlocoII
{
    protected function blocoII($y)
    {
        //$this->bloco2H = 12;
        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco2H, '', $aFont, 'T', 'C', true, '', false);

        $texto = "Documento auxiliar não fiscal";
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $y1 = $this->pdf->textBox(
            $this->margem,
            $this->bloco1H-2,
            $this->wPrint,
            $this->bloco2H,
            $texto,
            $aFont,
            'C',
            'C',
            false,
            '',
            true
        );

        $this->pdf->dashedHLine($this->margem, $this->bloco2H+$y, $this->wPrint, 0.1, 30);
        return $this->bloco2H + $y;
    }
}
