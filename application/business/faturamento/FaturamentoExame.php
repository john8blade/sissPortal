<?php

class FaturamentoExame extends FaturamentoAbstract {

    public function __construct() {
        
    }

    public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, array $colecaoContratoId, array $colecaoEmpresaId) {
        $resultado = array();
        try {
            $resultados = Application_Model_Agenda::obterColecaoProdutoDeAgendasParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $colecaoContratoId, $colecaoEmpresaId);
            if (count($resultados) > 0) {
                foreach ($resultados as $rst) {
                    $Item = new ItemFaturamento();
                    $Item->id = (int) $rst['produto_id'];
                    $Item->nome = $rst['produto_nome'];
                    $Item->quantidade = (int) $rst['quantidade'];
                    $Item->valorUnitario = (float) $rst['valor_venda'];
                    $Item->valorTotal = $Item->quantidade * $Item->valorUnitario;
                    $Item->codigoFixo = $rst['produto_codigo_fixo'];
                    $Item->osId = (int) $rst['os_id'];
                    $Item->cobrancaId = null;
                    $Item->parcelamentoId = null;
                    $Item->parcelamentoData = null;
                    $Item->numeroParcelaContexto = null;
                    $Item->quantidadeParcelas = null;
                    $Item->contratoId = (int) $rst['contrato_id'];
                    $Item->contratoNumero = $rst['contrato_numero'];
                    $Item->contratoSufixoNumero = $rst['contrato_sufixo_numero'];
                    $Item->localEntregaId = null;
                    $Item->localEntregaNome = null;
                    $Item->grupoIdsIdentificacaoItemCobranca = $rst['produto_agenda_ids'];
                    $Item->tipoFaturamento = self::FATURAMENTO_EXAME;
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
        try {           
            $produtos = Application_Model_Agenda::obterColecaoProdutoDeAgendasFaturadas($faturaId);
            foreach ($produtos as $produto) {
                $Item = new ItemFaturamento();
                $Item->cobrancaId = (isset($produto['fk_cobrancaos_id']) && (int) $produto['fk_cobrancaos_id'] > 0) ? (int) $produto['fk_cobrancaos_id'] : null;
                $Item->codigoFixo = $produto['produto_codigo_fixo'];
                $Item->contratoId = (int) $produto['contrato_id'];
                $Item->contratoNumero = $produto['contrato_numero'];
                $Item->contratoSufixoNumero = $produto['produto_codigo_fixo'];
                $Item->descricao = $produto['produto_descricao'];
                $Item->grupoIdsIdentificacaoItemCobranca = $produto['produto_fatura_grupo_item_ids'];
                $Item->id = (int) $produto['produto_id'];
                $Item->localEntregaId = null;
                $Item->localEntregaNome = null;
                $Item->nome = $produto['produto_nome'];
                $Item->numeroParcelaContexto = null;
                $Item->osId = (isset($produto['fk_os_id']) && (int) $produto['fk_os_id'] > 0) ? (int) $produto['fk_os_id'] : null;
                $Item->osNumero = (isset($produto['fk_os_id']) && (int) $produto['fk_os_id'] > 0) ? (int) $produto['fk_os_id'] : null;
                $Item->parcelamentoData = null;
                $Item->parcelamentoId = null;
                $Item->produtoContratadoId = null;
                $Item->quantidade = (int) $produto['produto_fatura_quantidade'];
                $Item->quantidadeParcelas = (int) $produto['produto_fatura_quantidade_parcela'];
                $Item->sigla = $produto['produto_sigla'];
                $Item->tipoFaturamento = $produto['produto_fatura_grupo_faturamento'];
                $Item->valorTotal = (float) $produto['produto_fatura_valor_total'];
                $Item->valorUnitario = (float) $produto['produto_fatura_valor'];
                $Item->checked = true;
                $resultado[] = $Item;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

}
