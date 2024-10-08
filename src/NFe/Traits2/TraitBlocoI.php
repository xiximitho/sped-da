<?php

namespace NFePHP\DA\NFe\Traits2;

/**
 * Bloco cabecalho com a identificação e logo do emitente
 */
trait TraitBlocoI
{
    protected function blocoI($config)
    {

        //$this->bloco1H = 18;
        $y = $this->margem;
        //$aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco1H, '', $aFont, 'T', 'C', true, '', false);
        $emitRazao = $config->razao_social;
        $xFant = $config->nome_fantasia;
        $emitCnpj = $config->cnpj;
        $emitCnpj = str_replace(" ", "", $emitCnpj);
        $emitIE = $config->ie;
        $emitCnpj = $this->formatField($emitCnpj, "###.###.###/####-##");
        $emitLgr = $config->logradouro;
        $emitNro = $config->numero;
        $emitBairro = $config->bairro;
        $emitMun = $config->municipio;
        $emitUF = $config->UF;
        $h = 0;
        $maxHimg = $this->bloco1H - 4;

        if (!empty($this->logomarca)) {

            $xImg = $this->margem;
            $yImg = $this->margem + 1;
            $logoInfo = getimagesize($this->logomarca);
            $logoWmm = ($logoInfo[0]/72)*25.4;
            $logoHmm = ($logoInfo[1]/72)*25.4;
            $nImgW = $this->wPrint/4;
            $nImgH = round($logoHmm * ($nImgW/$logoWmm), 0);
            if ($nImgH > $maxHimg) {
                $nImgH = $maxHimg;
                $nImgW = round($logoWmm * ($nImgH/$logoHmm), 0);
            }
            $xRs = ($nImgW) + $this->margem;
            $wRs = ($this->wPrint - $nImgW);
            $alignH = 'L';
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $xRs = $this->margem;
            $wRs = $this->wPrint;
            $alignH = 'C';
        }
        //COLOCA RAZÃO SOCIAL

        $aFont = ['font'=>$this->fontePadrao, 'size' => 8, 'style' => ''];
        $texto = "{$emitRazao}";
        $y += $this->pdf->textBox(
            $xRs+2,
            $this->margem,
            $wRs-2,
            $this->bloco1H-$this->margem-1,
            $texto,
            $aFont,
            'T',
            $alignH,
            false,
            '',
            true
        );

        $texto = "{$xFant}";
        $y += $this->pdf->textBox(
            $xRs+2,
            $y,
            $wRs-2,
            $this->bloco1H-$this->margem-1,
            $texto,
            $aFont,
            'T',
            $alignH,
            false,
            '',
            true
        );
        if ($this->pdf->fontSizePt < 8) {
            $aFont = ['font'=>$this->fontePadrao, 'size' => $this->pdf->fontSizePt, 'style' => ''];
        }

        $texto = "CNPJ: {$emitCnpj} IE: {$emitIE}";
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitLgr . ", " . $emitNro;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $texto = $emitBairro . " | " . $emitMun . "-" . $emitUF;
        $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        // $texto = $emitMun . "-" . $emitUF;
        // $y += $this->pdf->textBox($xRs+2, $y, $wRs-2, 3, $texto, $aFont, 'T', $alignH, false, '', true);
        $this->pdf->dashedHLine($this->margem, $this->bloco1H, $this->wPrint, 0.1, 30);
        return $this->bloco1H;
    }
}
