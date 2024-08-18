<?php

namespace NFePHP\DA\NFe;

/**
 * Classe para a impressão em PDF do Documento Auxiliar de NFe Consumidor
 * NOTA: Esta classe não é a indicada para quem faz uso de impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2019 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto Spadim <roberto at spadim dot com dot br>
 */
use Exception;
use InvalidArgumentException;
use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Legacy\Common;
use Com\Tecnick\Barcode\Barcode;
use DateTime;

use App\Models\ConfigNota;

class ItensMulti extends Common
{
    protected $papel;
    protected $pedido;
    protected $sizePaper;
    protected $paperwidth = 80;
    protected $creditos;
    protected $xml; // string XML NFe
    protected $logomarca=''; // path para logomarca em jpg
    protected $formatoChave="#### #### #### #### #### #### #### #### #### #### ####";
    protected $debugMode=0; //ativa ou desativa o modo de debug
    protected $tpImp; //ambiente
    protected $fontePadrao='Times';
    protected $nfeProc;
    protected $nfe;
    protected $infNFe;
    protected $ide;
    protected $enderDest;
    protected $ICMSTot;
    protected $imposto;
    protected $emit;
    protected $enderEmit;
    protected $qrCode;
    protected $urlChave;
    protected $det;
    protected $infAdic;
    protected $textoAdic;
    protected $tpEmis;
    protected $pag;
    protected $vTroco;
    protected $dest;
    protected $imgQRCode;
    protected $urlQR = '';
    protected $pdf;
    protected $margemInterna = 2;
    protected $hMaxLinha = 9;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    protected $totalItens = 0;
    protected $printers = [];

    /**
     * __contruct
     *
     * @param string $docXML
     * @param string $sPathLogo
     * @param string $mododebug
     * @param string $idToken
     * @param string $Token
     */
    public function __construct(
        $itens = null,
        $pedido = null,
        $sizePaper = 1,
    ) {

        $this->printers = $itens;
        $this->sizePaper = $sizePaper;

        $this->itens = $itens[0]['produtos'];
        $this->pedido = $pedido;
        $this->logomarca = '';
        $this->fontePadrao = empty($fonteDANFE) ? 'Times' : $fonteDANFE;
        $this->aFontTit = array('font' => $this->fontePadrao, 'size' => 9, 'style' => 'B');
        $this->aFontTex = array('font' => $this->fontePadrao, 'size' => 8, 'style' => '');
        
    }
    
    /**
     * Ativa ou desativa o modo debug
     * @param bool $activate
     * @return bool
     */
    public function debugMode($activate = null)
    {
        if (isset($activate) && is_bool($activate)) {
            $this->debugmode = $activate;
        }
        if ($this->debugmode) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        return $this->debugmode;
    }
    
    
    /**
     * Dados brutos do PDF
     * @return string
     */
    public function render()
    {
        if (empty($this->pdf)) {
            $this->monta();
        }
        return $this->pdf->getPdf();
    }
    
    
    public function paperWidth($width = 80)
    {
        if (is_int($width) && $width > 60) {
            $this->paperwidth = $width;
        }
        return $this->paperwidth;
    }
    
    public function monta(
        $logo = null,
        $depecNumReg = '',
        $logoAlign = 'C'
    ) {
        // $this->logomarca = $logo;
        $qtdItens = count($this->itens);
        $qtdPgto = 0;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;
        $tamPapelVert = (30) + ($this->sizePaper*8) + 0 + 12 + (($qtdItens - 1) * $hMaxLinha) + ($qtdPgto * $hLinha);
        // verifica se existe informações adicionais
        $this->textoAdic = '';

        $this->orientacao = 'P';
        $this->papel = [$this->paperwidth, $tamPapelVert];
        $this->logoAlign = $logoAlign;
        //$this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);

        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        $margSup = 2;
        $margEsq = 2;
        $margInf = 2;
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = 80;
        $maxH = $tamPapelVert;
        //total inicial de paginas
        $totPag = 1;
        foreach($this->printers as $key => $item){
            $maxH+=50;
            if(sizeof($item['produtos']) > 0){
                $this->itens = $item['produtos'];

                $this->wPrint = $maxW-($margEsq*2);
                $this->hPrint = $maxH-$margSup-$margInf;

                $this->pdf->aliasNbPages();
                $this->pdf->setMargins($margEsq, $margSup); 
                $this->pdf->setDrawColor(0, 0, 0);
                $this->pdf->setFillColor(255, 255, 255);
                $this->pdf->open(); 
                $this->pdf->addPage($this->orientacao, $this->papel); 

                $this->pdf->setLineWidth(0.1); 
                $this->pdf->setTextColor(0, 0, 0);
                $this->pdf->textBox(0, 0, $maxW, $maxH); 
                $hcabecalho = 5;
                $hcabecalhoSecundario = 10 + 3;
                $hprodutos = $hLinha + ($qtdItens * $hMaxLinha) ;

                if($qtdItens == 1){
                    $hprodutos += 15;
                }
                $hTotal = 12; 
                $hpagamentos = $hLinha + ($qtdPgto * $hLinha) + 3;
                if (!empty($this->vTroco)) {
                    $hpagamentos += $hLinha;
                }

                $hmsgfiscal = 21 + 2;
                $hcliente = !isset($this->dest) ? 6 : 12;
                $hcontingencia = $this->tpEmis == 9 ? 6 : 0;

                $hCabecItens = 6;

                $hUsado = $hCabecItens;
                $w2 = round($this->wPrint * 0.31, 0);
                $totPag = 1;
                $pag = 1;
                $x = $xInic;
                $y = $yInic;
                $y = $hcabecalho;
                $y = $this->cabecalhoSecundarioDANFE($x, $y, $hcabecalhoSecundario, $item['tela']);
                $jj = $hcabecalho + $hcabecalhoSecundario;
                $y = $xInic + $hcabecalho + $hcabecalhoSecundario;
                $y = $this->produtosDANFE($x, $y, $hprodutos);

            }
        }

    }

    // protected function cabecalhoDANFE($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    // {
    //     $config = ConfigNota::first();
    //     $emitRazao  = $config->razao_social;
    //     $emitCnpj   = $config->cnpj;
    //     $emitIE     = $config->ie;
    //     $emitIM     = '';
    //     $emitFone = " $config->fone";

    //     $emitLgr = $config->logradouro;
    //     $emitNro = $config->numero;
    //     $emitCpl = '';
    //     $emitBairro = $config->bairro;
    //     $emitCEP = $config->cep;
    //     $emitMun = $config->municipio;
    //     $emitUF = $config->UF;
    //     // CONFIGURAÇÃO DE POSIÇÃO
    //     $margemInterna = $this->margemInterna;
    //     $maxW = $this->wPrint;
    //     $h = $h-($margemInterna);
    //     //COLOCA LOGOMARCA
    //     if (is_file($this->logomarca)) {
    //         $xImg = $margemInterna;
    //         $yImg = $margemInterna + 1;
    //         $this->pdf->Image($this->logomarca, $xImg, $yImg, 30, 22.5);
    //         $xRs = ($maxW*0.4) + $margemInterna;
    //         $wRs = ($maxW*0.6);
    //         $alignEmit = 'L';
    //     } else {
    //         $xRs = $margemInterna;
    //         $wRs = ($maxW*1);
    //         $alignEmit = 'L';
    //     }
    //     //COLOCA RAZÃO SOCIAL
    //     $texto = $emitRazao;
    //     $texto = $texto . "\nCNPJ:" . $emitCnpj;
    //     $texto = $texto . "\nIE:" . $emitIE;
    //     if (!empty($emitIM)) {
    //         $texto = $texto . " - IM:" . $emitIM;
    //     }
    //     $texto = $texto . "\n" . $emitLgr . "," . $emitNro . " " . $emitCpl . "," . $emitBairro
    //     . ". CEP:" . $emitCEP . ". " . $emitMun . "-" . $emitUF . $emitFone;
    //     $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
    //     $this->pdf->textBox($xRs, $y, $wRs, $h, $texto, $aFont, 'C', $alignEmit, 0, '', false);
    // }

    protected function cabecalhoSecundarioDANFE($x = 0, $y = 0, $h = 0, $tela = '')
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hBox1 = 7;

        $texto = "";

        if($this->pedido->comanda){

            if($this->pedido->comanda > 9999){
                $texto = $this->pedido->nome;
            }else{
                $texto = "COMANDA " . $this->pedido->comanda . " - " . $tela;
            }
        }else{
            $texto = $this->pedido->observacao;
        }

        $aFont = array('font'=>$this->fontePadrao, 'size'=>15, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);
        $hBox2 = 4;

        $hBox1 = 18;

        if(isset($this->pedido->app)){
            $texto = "";
        }else{
            $texto = "MESA: " . ($this->pedido->mesa_id != null ? $this->pedido->mesa->id : '--');
        }
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B');
        $this->pdf->textBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);
        $hBox2 = 5;



    }

    protected function produtosDANFE($x = 0, $y = 0, $h = 0)
    {

        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdItens = count($this->itens);
        $w = ($maxW*1);
        $hLinha = $this->hLinha+1;
        $aFontCabProdutos = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $wBoxCod = $w*0;
        $texto = "";
        $this->pdf->textBox($x, $y, $wBoxCod, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        $wBoxDescricao = $w*0.85;
        $xBoxDescricao = $wBoxCod + $x;
        $texto = "DESCRIÇÃO";
        $this->pdf->textBox(
            $xBoxDescricao,
            $y,
            $wBoxDescricao,
            $hLinha,
            $texto,
            $aFontCabProdutos,
            'T',
            'L',
            0,
            '',
            false
        );
        $wBoxQt = $w*0.08;
        $xBoxQt = $wBoxDescricao + $xBoxDescricao;
        $texto = "QT";
        $this->pdf->textBox($xBoxQt, $y, $wBoxQt, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);

        $hBoxLinha = $this->hBoxLinha;
        $hMaxLinha = $this->hMaxLinha;
        $cont = 0;

        // $wBoxObs = $w*0.20;
        // $xBoxObs = $wBoxTotal + $wBoxTotal;
        // $texto = "OBS";
        // $this->pdf->textBox($xBoxTotal, $y, $wBoxTotal, $hLinha, $texto, $aFontCabProdutos, 'T', 'L', 0, '', false);
        // $hBoxLinha = $this->hBoxLinha;
        // $hMaxLinha = $this->hMaxLinha;
        // $cont = 0;

        $aFontProdutos = array('font'=>$this->fontePadrao, 'size'=>9, 'style'=>'');
        if ($qtdItens > 0) {
            foreach ($this->itens as $p) {

                $this->totalItens += $p->quantidade;
                $thisItem   = '1';
                $prod       = '@';
                $nitem      = 1;
                $cProd      = $p->id;

                $nomeP = '';
                $nomeP = $p->nomeDoProduto();
                $xProd      = $nomeP;


                $qCom       = $p->quantidade;
                $uCom       = $p->produto->unidade_venda == 'UNID' ? 'UN' : 
                $p->produto->unidade_venda;
                $vUnCom     = number_format($p->valor, 2, ",", ".");
                $vProd      = number_format($p->valor * $p->quantidade, 2, ",", ".");
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hMaxLinha);
                //COLOCA PRODUTO CÓDIGO
                $wBoxCod = $w*0;
                $texto = '';
                $this->pdf->textBox(
                    $x,
                    $yBoxProd,
                    $wBoxCod,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO DESCRIÇÃO
                $wBoxDescricao = $w*0.85;
                $xBoxDescricao = $wBoxCod + $x;
                $texto = $xProd;
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDescricao,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'L',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO QUANTIDADE
                $wBoxQt = $w*0.08;
                $xBoxQt = $wBoxDescricao + $xBoxDescricao;
                $texto = $qCom;
                $this->pdf->textBox(
                    $xBoxQt,
                    $yBoxProd,
                    $wBoxQt,
                    $hMaxLinha,
                    $texto,
                    $aFontProdutos,
                    'C',
                    'C',
                    0,
                    '',
                    false
                );
                //COLOCA PRODUTO UNIDADE

                //COLOCA PRODUTO VL UNITÁRIO


                $cont++;
            }
        }
    }

    protected function totalDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $hLinha = 3;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $qtdItens = count($this->pedido->itens);
        $vProd = $this->getTagValue($this->ICMSTot, "vProd");
        $vNF = $this->getTagValue($this->ICMSTot, "vNF");
        $vDesc  = $this->getTagValue($this->ICMSTot, "vDesc");
        $vFrete = $this->getTagValue($this->ICMSTot, "vFrete");
        $vTotTrib = $this->getTagValue($this->ICMSTot, "vTotTrib");
        $texto = "Qtd. Total de Itens";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $y, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $qtdItens;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $y, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotal = $y + ($hLinha);
        $texto = "Total de Produtos";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . number_format($this->pedido->somaItems(), 2, ",", ".");
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yDesconto = $y + ($hLinha*2);
        $texto = "Descontos";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yDesconto, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . 0.00;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yDesconto, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yFrete= $y + ($hLinha*3);
        $texto = "Entrega";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yFrete, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = "R$ " . ($this->pedido->bairro_id != null ? $this->pedido->bairro->valor_entrega : 0.00);
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yFrete, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*4);
        $texto = "Total Geral";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $totalComFrete = $this->pedido->somaItems();
        if($this->pedido->bairro_id != null){
            $totalComFrete += $this->pedido->bairro->valor_entrega;
        }
        $texto = "R$ " . number_format($totalComFrete, 2);

        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*5);

        $texto = "Observação";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->pedido->observacao;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*6);

        $texto = "Rua";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->pedido->rua . ", ". $this->pedido->numero;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*7);

        $texto = "Bairro";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->pedido->bairro_id != null ? $this->pedido->bairro->nome : '';
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*8);

        $texto = "Referencia";
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);
        $texto = $this->pedido->referencia;
        $aFont = ['font'=>$this->fontePadrao, 'size'=>9, 'style'=>'B'];
        $this->pdf->textBox($xValor, $yTotalFinal, $wColDir, $hLinha, $texto, $aFont, 'T', 'R', 0, '', false);
        $yTotalFinal = $y + ($hLinha*9);
        // $texto = "Informação dos Tributos Totais Incidentes";
        // $aFont = ['font'=>$this->fontePadrao, 'size'=>7, 'style'=>''];
        // $this->pdf->textBox($x, $yTotalFinal, $wColEsq, $hLinha, $texto, $aFont, 'T', 'L', 0, '', false);

    }

    protected function pagamentosDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $qtdPgto = 0;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $wColEsq = ($maxW*0.7);
        $wColDir = ($maxW*0.3);
        $xValor = $x + $wColEsq;
        $aFontPgto = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'B');
        $wBoxEsq = $w*0.7;
        $texto = "FORMA DE PAGAMENTO";
        $this->pdf->textBox($x, $y, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
        $wBoxDir = $w*0.3;
        $xBoxDescricao = $x + $wBoxEsq;
        $texto = "VALOR PAGO";
        $this->pdf->textBox($xBoxDescricao, $y, $wBoxDir, $hLinha, $texto, $aFontPgto, 'T', 'R', 0, '', false);
        $cont = 0;
        if ($qtdPgto > 0) {
            foreach ($this->pag as $pagI) {
                $tPag = $this->getTagValue($pagI, "tPag");
                $tPagNome = $this->tipoPag($tPag);
                $tPnome = $tPagNome;
                $vPag = number_format($this->getTagValue($pagI, "vPag"), 2, ",", ".");
                $card = $pagI->getElementsByTagName("card")->item(0);
                $cardCNPJ = '';
                $tBand = '';
                $tBandNome = '';
                if (isset($card)) {
                    $cardCNPJ = $this->getTagValue($card, "CNPJ");
                    $tBand    = $this->getTagValue($card, "tBand");
                    $cAut = $this->getTagValue($card, "cAut");
                    $tBandNome = self::getCardName($tBand);
                }
                //COLOCA PRODUTO
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = $tPagNome;
                $this->pdf->textBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . $vPag;
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
                $cont++;
            }

            if (!empty($this->vTroco)) {
                $yBoxProd = $y + $hLinha + ($cont*$hLinha);
                //COLOCA PRODUTO CÓDIGO
                $texto = 'Troco';
                $this->pdf->textBox($x, $yBoxProd, $wBoxEsq, $hLinha, $texto, $aFontPgto, 'T', 'L', 0, '', false);
                //COLOCA PRODUTO DESCRIÇÃO
                $xBoxDescricao = $wBoxEsq + $x;
                $texto = "R$ " . number_format($this->vTroco, 2, ",", ".");
                $this->pdf->textBox(
                    $xBoxDescricao,
                    $yBoxProd,
                    $wBoxDir,
                    $hLinha,
                    $texto,
                    $aFontPgto,
                    'C',
                    'R',
                    0,
                    '',
                    false
                );
            }
        }
    }

    protected function fiscalDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
        $aFontTex = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
        $digVal = $this->getTagValue($this->nfe, "DigestValue");
        $chNFe = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $tpAmb = $this->getTagValue($this->ide, 'tpAmb');

        if ($this->checkCancelada()) {
            //101 Cancelamento
            $this->pdf->setTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pdf->textBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->setTextColor(0, 0, 0);
        }

        if ($this->checkDenegada()) {
            //uso denegado
            $this->pdf->setTextColor(255, 0, 0);
            $texto = "NFCe CANCELADA";
            $this->pdf->textBox($x, $y - 25, $w, $h, $texto, $aFontTit, 'C', 'C', 0, '');
            $this->pdf->SetTextColor(0, 0, 0);
        }

        $cUF = $this->getTagValue($this->ide, 'cUF');
        $nNF = $this->getTagValue($this->ide, 'nNF');
        $serieNF = str_pad($this->getTagValue($this->ide, "serie"), 3, "0", STR_PAD_LEFT);
        $dhEmi = $this->getTagValue($this->ide, "dhEmi");
        $dhEmilocal = new \DateTime($dhEmi);
        $dhEmiLocalFormat = $dhEmilocal->format('d/m/Y H:i:s');
        $texto = "ÁREA DE MENSAGEM FISCAL";
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex1 = $y + ($hLinha*1);
        $hTex1 = $hLinha*2;
        $texto = "Número " . $nNF . " Série " . $serieNF . " " .$dhEmiLocalFormat . " - Via Consumidor";
        $this->pdf->textBox($x, $yTex1, $w, $hTex1, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $yTex2 = $y + ($hLinha*3);
        $hTex2 = $hLinha*2;

        $texto = !empty($this->urlChave) ? "Consulte pela Chave de Acesso em " . $this->urlChave : '';
        $this->pdf->textBox($x, $yTex2, $w, $hTex2, $texto, $aFontTex, 'C', 'C', 0, '', false);
        $texto = "CHAVE DE ACESSO";
        $yTit2 = $y + ($hLinha*5);
        $this->pdf->textBox($x, $yTit2, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        $yTex3 = $y + ($hLinha*6);
        $texto = $chNFe;
        $this->pdf->textBox($x, $yTex3, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
    }

    protected function consumidorDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>'B'];
        $aFontTex = ['font'=>$this->fontePadrao, 'size'=>8, 'style'=>''];
        $texto = "CONSUMIDOR";
        $this->pdf->textBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        if (isset($this->dest)) {
            $considEstrangeiro = !empty($this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue)
            ? $this->dest->getElementsByTagName("idEstrangeiro")->item(0)->nodeValue
            : '';
            $consCPF = !empty($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue)
            ? $this->dest->getElementsByTagName("CPF")->item(0)->nodeValue
            : '';
            $consCNPJ = !empty($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue)
            ? $this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue
            : '';
            $cDest = $consCPF.$consCNPJ.$considEstrangeiro; //documentos do consumidor
            $enderDest = $this->dest->getElementsByTagName("enderDest")->item(0);
            $consNome = $this->getTagValue($this->dest, "xNome");
            $consLgr = $this->getTagValue($enderDest, "xLgr");
            $consNro = $this->getTagValue($enderDest, "nro");
            $consCpl = $this->getTagValue($enderDest, "xCpl", " - ");
            $consBairro = $this->getTagValue($enderDest, "xBairro");
            $consCEP = $this->formatField($this->getTagValue($enderDest, "CEP"));
            $consMun = $this->getTagValue($enderDest, "xMun");
            $consUF = $this->getTagValue($enderDest, "UF");
            $considEstrangeiro = $this->getTagValue($this->dest, "idEstrangeiro");
            $consCPF = $this->getTagValue($this->dest, "CPF");
            $consCNPJ = $this->getTagValue($this->dest, "CNPJ");
            $consDoc = "";
            if (!empty($consCNPJ)) {
                $consDoc = "CNPJ: $consCNPJ";
            } elseif (!empty($consCPF)) {
                $consDoc = "CPF: $consCPF";
            } elseif (!empty($considEstrangeiro)) {
                $consDoc = "id: $considEstrangeiro";
            }
            $consEnd = "";
            if (!empty($consLgr)) {
                $consEnd = $consLgr
                . ","
                . $consNro
                . " "
                . $consCpl
                . ","
                . $consBairro
                . ". CEP:"
                . $consCEP
                . ". "
                . $consMun
                . "-"
                . $consUF;
            }
            $yTex1 = $y + $hLinha;
            $texto = $consNome;
            if (!empty($consDoc)) {
                $texto .= " - ". $consDoc . "\n" . $consEnd;
                $this->pdf->textBox($x, $yTex1, $w, $hLinha*3, $texto, $aFontTex, 'C', 'C', 0, '', false);
            }
        } else {
            $yTex1 = $y + $hLinha;
            $texto = "Consumidor não identificado";
            $this->pdf->textBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);
        }
    }
    

    
    
    /**
     * anfavea
     * Função para transformar o campo cdata do padrão ANFAVEA para
     * texto imprimível
     *
     * @param  string $cdata campo CDATA
     * @return string conteúdo do campo CDATA como string
     */
    protected function anfaveaDANFE($cdata = '')
    {
        if ($cdata == '') {
            return '';
        }
        //remove qualquer texto antes ou depois da tag CDATA
        $cdata = str_replace('<![CDATA[', '<CDATA>', $cdata);
        $cdata = str_replace(']]>', '</CDATA>', $cdata);
        $cdata = preg_replace('/\s\s+/', ' ', $cdata);
        $cdata = str_replace("> <", "><", $cdata);
        $len = strlen($cdata);
        $startPos = strpos($cdata, '<');
        if ($startPos === false) {
            return $cdata;
        }
        for ($x=$len; $x>0; $x--) {
            if (substr($cdata, $x, 1) == '>') {
                $endPos = $x;
                break;
            }
        }
        if ($startPos > 0) {
            $parte1 = substr($cdata, 0, $startPos);
        } else {
            $parte1 = '';
        }
        $parte2 = substr($cdata, $startPos, $endPos-$startPos+1);
        if ($endPos < $len) {
            $parte3 = substr($cdata, $endPos + 1, $len - $endPos - 1);
        } else {
            $parte3 = '';
        }
        $texto = trim($parte1).' '.trim($parte3);
        if (strpos($parte2, '<CDATA>') === false) {
            $cdata = '<CDATA>'.$parte2.'</CDATA>';
        } else {
            $cdata = $parte2;
        }
        //carrega o xml CDATA em um objeto DOM
        $dom = new Dom();
        $dom->loadXML($cdata, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        //$xml = $dom->saveXML();
        //grupo CDATA infADprod
        $id = $dom->getElementsByTagName('id')->item(0);
        $div = $dom->getElementsByTagName('div')->item(0);
        $entg = $dom->getElementsByTagName('entg')->item(0);
        $dest = $dom->getElementsByTagName('dest')->item(0);
        $ctl = $dom->getElementsByTagName('ctl')->item(0);
        $ref = $dom->getElementsByTagName('ref')->item(0);
        if (isset($id)) {
            if ($id->hasAttributes()) {
                foreach ($id->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($div)) {
            if ($div->hasAttributes()) {
                foreach ($div->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($entg)) {
            if ($entg->hasAttributes()) {
                foreach ($entg->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($dest)) {
            if ($dest->hasAttributes()) {
                foreach ($dest->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ctl)) {
            if ($ctl->hasAttributes()) {
                foreach ($ctl->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($ref)) {
            if ($ref->hasAttributes()) {
                foreach ($ref->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        //grupo CADATA infCpl
        $t = $dom->getElementsByTagName('transmissor')->item(0);
        $r = $dom->getElementsByTagName('receptor')->item(0);
        $versao = ! empty($dom->getElementsByTagName('versao')->item(0)->nodeValue) ?
        'Versao:'.$dom->getElementsByTagName('versao')->item(0)->nodeValue.' ' : '';
        $especieNF = ! empty($dom->getElementsByTagName('especieNF')->item(0)->nodeValue) ?
        'Especie:'.$dom->getElementsByTagName('especieNF')->item(0)->nodeValue.' ' : '';
        $fabEntrega = ! empty($dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue) ?
        'Entrega:'.$dom->getElementsByTagName('fabEntrega')->item(0)->nodeValue.' ' : '';
        $dca = ! empty($dom->getElementsByTagName('dca')->item(0)->nodeValue) ?
        'dca:'.$dom->getElementsByTagName('dca')->item(0)->nodeValue.' ' : '';
        $texto .= "".$versao.$especieNF.$fabEntrega.$dca;
        if (isset($t)) {
            if ($t->hasAttributes()) {
                $texto .= " Transmissor ";
                foreach ($t->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        if (isset($r)) {
            if ($r->hasAttributes()) {
                $texto .= " Receptor ";
                foreach ($r->attributes as $attr) {
                    $name = $attr->nodeName;
                    $value = $attr->nodeValue;
                    $texto .= " $name : $value";
                }
            }
        }
        return $texto;
    }
    

}
