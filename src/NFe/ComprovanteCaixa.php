<?php

namespace NFePHP\DA\NFe;

/**
 * Classe para a impressão em PDF do Documento Auxiliar de NFe Consumidor
 * NOTA: Esta classe não é a indicada para quem faz uso de impressoras térmicas ESCPOS
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2016 NFePHP
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
use App\VendaCaixa;
use App\ConfigNota;
use \Carbon\Carbon;

class ComprovanteCaixa extends Common
{
    protected $papel;
    protected $sangria; 
    protected $margemInterna = 4;
    protected $hMaxLinha = 9;
    protected $hBoxLinha = 6;
    protected $hLinha = 3;
    protected $config = null;
    protected $larg = 80;
    protected $titulo = "";
    protected $nomeUsuario = "";
    /*
     * Retorna a sigla da UF
     * @var string
     */
    
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
        $titulo,
        $sangria,
        $sPathLogo = '',
        $config = null,
        $larg = 80,
        $nomeUsuario = ""
    ) {
        $this->titulo = $titulo;
        $this->larg = $larg;
        $this->sangria = $sangria;
        $this->config = $config;
        $this->nomeUsuario = $nomeUsuario;
        $this->logomarca = $sPathLogo;
        if (empty($fonteDANFE)) {
            $this->fontePadrao = 'Times';
        } else {
            $this->fontePadrao = $fonteDANFE;
        }
    }
    
    public function getPapel()
    {
        return $this->papel;
    }
    
    public function setPapel($aPap)
    {
        $this->papel = $aPap;
    }
    
    public function monta(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $this->montaDANFE($orientacao, $papel, $logoAlign, $classPdf, $depecNumReg);
    }
    
    public function montaDANFE(
        $orientacao = 'P',
        $papel = '',
        $logoAlign = 'C',
        $classPdf = false,
        $depecNumReg = ''
    ) {
        $qtdItens = 0;
        $qtdPgto = 1;
        $hMaxLinha = $this->hMaxLinha;
        $hBoxLinha = $this->hBoxLinha;
        $hLinha = $this->hLinha;
        $tamPapelVert = 80 + 16 + (($qtdItens - 1) * $hMaxLinha) + ($qtdPgto * $hLinha);

        // verifica se existe informações adicionais
        $this->textoAdic = '';
        
        if ($orientacao == '') {
            $orientacao = 'P';
        }
        $this->orientacao = $orientacao;
        $this->papel = array($this->larg,$tamPapelVert);
        $this->logoAlign = $logoAlign;
        //$this->situacao_externa = $situacaoExterna;
        $this->numero_registro_dpec = $depecNumReg;
        //instancia a classe pdf
        if ($classPdf) {
            $this->pdf = $classPdf;
        } else {
            $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        }
        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        $margSup = 2;
        $margEsq = 2;
        $margInf = 2;
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $margEsq;
        $yInic = $margSup;
        $maxW = $this->larg;
        $maxH = $tamPapelVert;
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $maxW-($margEsq*2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $maxH-$margSup-$margInf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins($margEsq, $margSup); // fixa as margens
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->pdf->open(); // inicia o documento
        $this->pdf->addPage($this->orientacao, $this->papel); // adiciona a primeira página
        $this->pdf->setLineWidth(0.1); // define a largura da linha
        $this->pdf->setTextColor(0, 0, 0);
        $this->pTextBox(0, 0, $maxW, $maxH); // POR QUE PRECISO DESA LINHA?
        $hcabecalho = 27;//para cabeçalho (dados emitente mais logomarca)  (FIXO)
        $hcabecalhoSecundario = 10;//para cabeçalho secundário (cabeçalho sefaz) (FIXO)
        $hprodutos = $hLinha + ($qtdItens*$hMaxLinha) ;//box poduto
        $hTotal = 12; //box total (FIXO)
        $hpagamentos = $hLinha + ($qtdPgto*$hLinha);//para pagamentos
        if (!empty($this->vTroco)) {
            $hpagamentos += $hLinha;
        }
        $hmsgfiscal = 21;// para imposto (FIXO)
        if (!isset($this->dest)) {
            $hcliente = 6;// para cliente (FIXO)
        } else {
            $hcliente = 12;
        }// para cliente (FIXO)};
        $hQRCode = 50;// para qrcode (FIXO)
        $hCabecItens = 4;//cabeçalho dos itens
        
        $hUsado = $hCabecItens;
        $w2 = round($this->wPrint*0.31, 0);
        $totPag = 1;
        $pag = 1;
        $x = $xInic;
        //COLOCA CABEÇALHO
        $y = $yInic;
        $y = $this->pCabecalhoDANFE($x, $y, $hcabecalho, $pag, $totPag);
        //COLOCA CABEÇALHO SECUNDÁRIO
        $y = $hcabecalho;
        $y = $this->pCabecalhoSecundarioDANFE($x, $y, $hcabecalhoSecundario);
        // //COLOCA PRODUTOS
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario;
        // $y = $this->pProdutosDANFE($x, $y, $hprodutos);
        // //COLOCA TOTAL
        $y = $xInic + $hcabecalho + $hcabecalhoSecundario + $hprodutos;
        $qtdItens = 0;
        
        $y = $this->pConsumidorDANFE($x, $y, null);
        

    }

    protected function pCabecalhoDANFE($x = 0, $y = 0, $h = 0, $pag = '1', $totPag = '1')
    {

        $emitRazao  = $this->config->razao_social;
        $emitCnpj   = $this->config->cnpj;
        $emitIE     = $this->config->ie;
        $emitIM     = '';
        $emitFone = ' ' . $this->config->fone;

        $emitLgr = $this->config->logradouro;
        $emitNro = $this->config->numero;
        $emitCpl = '';
        $emitBairro = $this->config->bairro;
        $emitCEP = $this->config->cep;
        $emitMun = $this->config->municipio;
        $emitUF = $this->config->UF;
        // CONFIGURAÇÃO DE POSIÇÃO
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $h = $h-($margemInterna);
        //COLOCA LOGOMARCA
        if (is_file($this->logomarca)) {
            $xImg = $margemInterna;
            $yImg = $margemInterna + 1;
            $this->pdf->Image($this->logomarca, $xImg, $yImg, 30, 22.5);
            $xRs = ($maxW*0.4) + $margemInterna;
            $wRs = ($maxW*0.6);
            $alignEmit = 'L';
        } else {
            $xRs = $margemInterna;
            $wRs = ($maxW*1);
            $alignEmit = 'L';
        }
        //COLOCA RAZÃO SOCIAL
        $texto = $emitRazao;
        $texto = $texto . "\nCNPJ:" . $emitCnpj;
        $texto = $texto . "\nIE:" . $emitIE;
        if (!empty($emitIM)) {
            $texto = $texto . " - IM:" . $emitIM;
        }
        $texto = $texto . "\n" . $emitLgr . "," . $emitNro . " " . $emitCpl . "," . $emitBairro
        . ". CEP:" . $emitCEP . ". " . $emitMun . "-" . $emitUF . $emitFone;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $this->pTextBox($xRs, $y, $wRs, $h, $texto, $aFont, 'C', $alignEmit, 0, '', false);
    }

    protected function pCabecalhoSecundarioDANFE($x = 0, $y = 0, $h = 0)
    {
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hBox1 = 7;
        $texto = $this->titulo;
        $aFont = array('font'=>$this->fontePadrao, 'size'=>12, 'style'=>'B');
        $this->pTextBox($x, $y, $w, $hBox1, $texto, $aFont, 'C', 'C', 0, '', false);
        $hBox2 = 4;
        $yBox2 = $y + $hBox1;
        $texto = "\n";
        $aFont = array('font'=>$this->fontePadrao, 'size'=>7, 'style'=>'');
        $this->pTextBox($x, $yBox2, $w, $hBox2, $texto, $aFont, 'C', 'C', 0, '', false);
    }

    protected function pConsumidorDANFE($x = 0, $y = 0, $h = 0)
    {
        $y += 6;
        $margemInterna = $this->margemInterna;
        $maxW = $this->wPrint;
        $w = ($maxW*1);
        $hLinha = $this->hLinha;
        $aFontTit = array('font'=>$this->fontePadrao, 'size'=>10, 'style'=>'B');
        $aFontTex = array('font'=>$this->fontePadrao, 'size'=>8, 'style'=>'');
        $texto = "VALOR: R$ " . number_format($this->sangria->valor, 2, ',', '.');
        $this->pTextBox($x, $y, $w, $hLinha, $texto, $aFontTit, 'C', 'C', 0, '', false);
        
        $yTex1 = $y + $hLinha;
        $texto = "Observação: ".$this->sangria->observacao;
        $this->pTextBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);

        $yTex1 = $y + $hLinha+3;
        $texto = \Carbon\Carbon::parse($this->sangria->created_at)->format('d/m/Y H:i:s');
        $this->pTextBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);


        $yTex1 = $y + $hLinha+14;
        $texto = "_____________________________________";
        $this->pTextBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);


        $yTex1 = $y + $hLinha+17;
        $texto = $this->nomeUsuario;
        $this->pTextBox($x, $yTex1, $w, $hLinha, $texto, $aFontTex, 'C', 'C', 0, '', false);

    }
    

    public function render()
    {
        return $this->pdf->getPdf();
    }

}
