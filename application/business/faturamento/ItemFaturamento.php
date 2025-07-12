<?php

class ItemFaturamento {

    public $id;
    public $tipoFaturamento;
    public $nome;
    public $sigla;
    public $descricao;
    public $codigoFixo;
    public $quantidade;
    public $valorUnitario;
    public $valorTotal;
    public $quantidadeParcelas;
    public $numeroParcelaContexto;
    public $osNumero;
    public $osId;
    public $cobrancaId;
    public $produtoContratadoId;
    public $parcelamentoData;
    public $parcelamentoId;
    public $execucaoServicoId;    
    public $execucaoServicoDataCobranca;    
    public $contratoId;
    public $contratoNumero;
    public $empresaId;
    public $empresaRazao;
    public $contratoSufixoNumero;
    public $localEntregaNome;
    public $localEntregaId;
    public $grupoIdsIdentificacaoItemCobranca;
    

    public function __construct() {
        
    }

}
