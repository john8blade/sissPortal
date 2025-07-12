<?php

class FaturamentoParcela extends FaturamentoAbstract {

    public function __construct() {
        $this->identificadorFaturamento = self::FATURAMENTO_PARCELA;
    }

    public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, array $contratoId, array $empresaId) {
        $resultado = array();
        try {
            $resultados = Application_Model_Parcelamento::obterColecaoParcelasParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratoId, $empresaId);
            if (count($resultados) > 0) {
                foreach ($resultados as $rst) {
                    $Item = new ItemFaturamento();
                    $Item->id = (int) $rst['produto_id'];
                    $Item->nome = $rst['produto_nome'];
                    $Item->quantidade = (int) $rst['produto_contratado_quantidade'];
                    $Item->valorUnitario = (float) $rst['produto_contratado_valor_venda'];
                    $Item->valorTotal = (float) $rst['valor_cobranca'];
                    $Item->codigoFixo = $rst['produto_codigo_fixo'];
                    $Item->osId = (int) $rst['os_id'];
                    $Item->cobrancaId = (int) $rst['cobrancaos_id'];
                    $Item->parcelamentoId = $rst['parcelamento_id'];
                    $Item->parcelamentoData = $rst['parcelamento_data'];
                    $Item->numeroParcelaContexto = $rst['parcelamento_sequencia'];
                    $Item->quantidadeParcelas = (int) $rst['quantidade_parcela'];
                    $Item->contratoId = (int) $rst['contrato_id'];
                    $Item->contratoNumero = $rst['contrato_numero'];
                    $Item->contratoSufixoNumero = $rst['contrato_sufixo_numero'];
                    $Item->localEntregaId = $rst['localentrega_id'];
                    $Item->localEntregaNome = $rst['localentrega_identificacao'];
                    $Item->grupoIdsIdentificacaoItemCobranca = $rst['grupo_parcela_id'];
                    $Item->tipoFaturamento = self::FATURAMENTO_PARCELA;
                    $resultado[] = $Item;
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    public function obterColecaoProdutoFaturado($faturaId) {
        $resultado = array();
        $Cmd = null;
        $clcProdutos = array();
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select()
                    ->from(array('pf' => 'produto_fatura'), array('produto_fatura_valor', 'produto_fatura_valor_total', 'produto_fatura_quantidade', 'produto_fatura_quantidade_parcela', 'produto_fatura_parcela_contexto', 'produto_fatura_grupo_item_ids', 'produto_fatura_grupo_faturamento', 'fk_os_id'))
                    ->join(array('pr' => 'produto'), 'pr.produto_id = pf.fk_produto_id', array('produto_nome', 'produto_id', 'produto_sigla', 'produto_codigo_fixo', 'produto_descricao'))
                    ->join(array('f' => 'fatura'), 'f.fatura_id = pf.fk_fatura_id', array())
                    ->join(array('c' => 'contrato'), 'c.contrato_id = f.fk_contrato_id', array('contrato_id', 'contrato_numero', 'contrato_sufixo_numero'))
                    ->joinLeft(array('pa' => 'parcelamento'), 'pa.parcelamento_id = pf.fk_parcelamento_id', array('cobranca_parcelamento_data' => 'parcelamento_data', 'fk_cobrancaos_id', 'cobranca_parcelamento_id' => 'parcelamento_id'))
                    ->joinLeft(array('le' => 'localentrega'), 'le.localentrega_id = pf.fk_localentrega_id', array('localentrega_identificacao', 'localentrega_id'))
                    ->where('pf.produto_fatura_status = 0')
                    ->where('f.fatura_versao =  ?', '2.0')
                    ->where('pf.fk_parcelamento_id IS NOT NULL')
                    ->where('pf.fk_fatura_id = ?', $faturaId);
            $clcProdutos = $Cmd->query()->fetchAll();
        } catch (Exception $Exc) {
            throw $Exc;
        }
        
        if (is_array($clcProdutos) && count($clcProdutos) > 0) {
            foreach ($clcProdutos as $produto) {
                $Item = new ItemFaturamento();
                $Item->cobrancaId = (isset($produto['fk_cobrancaos_id']) && (int) $produto['fk_cobrancaos_id'] > 0) ? (int) $produto['fk_cobrancaos_id'] : null;
                $Item->codigoFixo = $produto['produto_codigo_fixo'];
                $Item->contratoId = (int) $produto['contrato_id'];
                $Item->contratoNumero = $produto['contrato_numero'];
                $Item->contratoSufixoNumero = $produto['produto_codigo_fixo'];
                $Item->descricao = $produto['produto_descricao'];
                $Item->grupoIdsIdentificacaoItemCobranca = $produto['produto_fatura_grupo_item_ids'];
                $Item->id = (int) $produto['produto_id'];
                $Item->localEntregaId = (isset($produto['localentrega_id']) && (int) $produto['localentrega_id'] > 0) ? (int) $produto['localentrega_id'] : null;
                $Item->localEntregaNome = $produto['localentrega_identificacao'];
                $Item->nome = $produto['produto_nome'];
                $Item->numeroParcelaContexto = $produto['produto_fatura_parcela_contexto'];
                $Item->osId = (isset($produto['fk_os_id']) && (int) $produto['fk_os_id'] > 0) ? (int) $produto['fk_os_id'] : null;
                $Item->osNumero = (isset($produto['fk_os_id']) && (int) $produto['fk_os_id'] > 0) ? (int) $produto['fk_os_id'] : null;
                $Item->parcelamentoData = Util::dataBR($produto['cobranca_parcelamento_data']);
                $Item->parcelamentoId = $produto['cobranca_parcelamento_id'];
                $Item->produtContratadoId = null;
                $Item->quantidade = (int) $produto['produto_fatura_quantidade'];
                $Item->quantidadeParcelas = (int) $produto['produto_fatura_quantidade_parcela'];
                $Item->sigla = $produto['produto_sigla'];
                $Item->tipoFaturamento = $produto['produto_fatura_grupo_faturamento'];
                $Item->valorTotal = (float) $produto['produto_fatura_valor_total'];
                $Item->valorUnitario = (float) $produto['produto_fatura_valor'];
                $Item->checked = true;
                $resultado[] = $Item;
            }
        }
        return $resultado;
    }

}
