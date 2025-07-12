<?php

abstract class FaturamentoAbstract {

    public $identificadorFaturamento;

    const FATURAMENTO_PARCELA = 'PARCELA';
    const FATURAMENTO_EXAME = 'EXAME';
    const FATURAMENTO_COORDENACAO = 'COORDENACAO';
    const FATURAMENTO_EXEC_SERVICO = 'EXEC_SERVICO';

    public function __construct() {
        
    }

    abstract public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, array $colecaoContratoId, array $colecaoEmpresaId);
    
    abstract public function obterColecaoProdutoFaturado($faturaId);
}
