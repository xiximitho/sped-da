<?php

namespace NFePHP\DA\NFe\Traits2;

/**
 * Bloco forma de pagamento
 */
trait TraitBlocoV
{
    protected function blocoV($y, $venda)
    {
        $this->bloco5H = $this->calculateHeightPag();
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        //$this->pdf->textBox($this->margem, $y, $this->wPrint, $this->bloco5H, '', $aFont, 'T', 'C', true, '', false);
        $arpgto = [];
        $linePlus = 0;
        if (sizeof($venda->fatura) > 0) {
            foreach ($venda->fatura as $f) {
                $tipo = \App\Models\VendaCaixa::getTipoPagamento($f->forma_pagamento);
                $valor = number_format($f->valor, 2, ',', '.');
                $arpgto[] = [
                    'tipo' => $tipo,
                    'valor' => $valor
                ];
                $linePlus += 2;
            }

        } else {

            $tipo = \App\Models\VendaCaixa::getTipoPagamento($venda->tipo_pagamento);
            $valor = number_format($venda->valor_total, 2, ',', '.');
            $arpgto[] = [
                'tipo' => $tipo,
                'valor' => $valor
            ];
        }
        $aFont = ['font'=> $this->fontePadrao, 'size' => 7, 'style' => ''];
        $texto = "FORMA PAGAMENTO";
        $this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'L', false, '', false);
        $texto = "VALOR PAGO R$";
        $y1 = $this->pdf->textBox($this->margem, $y, $this->wPrint, 4, $texto, $aFont, 'T', 'R', false, '', false);

        $z = $y + $y1;
        foreach ($arpgto as $p) {
            $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $p['tipo'], $aFont, 'T', 'L', false, '', false);
            $y2 = $this->pdf->textBox(
                $this->margem,
                $z,
                $this->wPrint,
                3,
                $p['valor'],
                $aFont,
                'T',
                'R',
                false,
                '',
                false
            );
            $z += $y2;
        }

        $texto = "Troco R$";
        $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
        $texto =  !empty($this->vTroco) ? number_format((float) $this->vTroco, 2, ',', '.') : '0,00';
        $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
        $z += $y2;

        $texto = "Data";
        $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
        $texto = \Carbon\Carbon::parse($venda->created_at)->format('d/m/Y H:i:s');
        $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
        $y += 2;

        $z += $y2;
        $texto = "Código da venda";
        $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
        $texto = $venda->id;
        $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
        $y += 2;

        if($this->venda->vendedor()){
            $z += $y2;
            $texto = "Vendedor";
            $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
            $texto = $this->venda->vendedor();
            $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
            $y += 2;
        }

        if($this->venda->comissaoAssessor){
            $z += $y2;
            $texto = "Vendedor";
            $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'L', false, '', false);
            $texto = "Assessor " . $this->venda->comissaoAssessor->assessor->razao_social;
            $y1 = $this->pdf->textBox($this->margem, $z, $this->wPrint, 3, $texto, $aFont, 'T', 'R', false, '', false);
            $y += 2;
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco5H+$y+$linePlus, $this->wPrint, 0.1, 30);
        return $this->bloco5H + $y;
    }

    protected function pagType($type)
    {
        $lista = [
            1 => 'Dinheiro',
            2 => 'Cheque',
            3 => 'Cartão de Crédito',
            4 => 'Cartão de Débito',
            5 => 'Crédito Loja',
            10 => 'Vale Alimentação',
            11 => 'Vale Refeição',
            12 => 'Vale Presente',
            13 => 'Vale Combustível',
            15 => 'Boleto Bancário',
            16 => 'Depósito Bancário',
            17 => 'Pagamento Instantâneo (PIX)',
            18 => 'Transferência bancária, Carteira Digital',
            19 => 'Programa de fidelidade, Cashback, Crédito Virtual',
            90 => 'Sem pagamento',
            99 => 'Outros',
        ];
        return $lista[$type];
    }

    protected function calculateHeightPag($fatura = null)
    {

        $n = 1;
        if($fatura != null && sizeof($fatura) > 1){
            $n = sizeof($fatura);
        }
        $height = 4 + (2.4 * $n) + 3;
        return $height;
    }
}
