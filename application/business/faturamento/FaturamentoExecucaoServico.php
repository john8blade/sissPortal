<?php

class FaturamentoExecucaoServico extends FaturamentoAbstract {

    public function __construct() {
        
    }

    public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, array $colecaoContratoId, array $colecaoEmpresaId) {
        $resultado = array();
        try {
            $resultados = Application_Model_ExecucaoServico::obterColecaoProdutoParaFaturamento($dataApuracaoInicio, $dataApuracaoTermino, $colecaoContratoId, $colecaoEmpresaId);
            if (count($resultados) > 0) {
                foreach ($resultados as $rst) {
                    $Item = new ItemFaturamento();
                    $Item->id = (int) $rst['produto_id'];
                    $Item->nome = $rst['produto_nome'];
                    $Item->quantidade = (int) $rst['execucao_servico_quantidade'];
                    $Item->valorUnitario = (float) $rst['execucao_servico_valor_unitario'];
                    $Item->valorTotal = (float) $rst['execucao_servico_valor_cobranca'];
                    $Item->codigoFixo = $rst['produto_codigo_fixo'];
                    $Item->osId = null;
                    $Item->cobrancaId = null;
                    $Item->parcelamentoId = null;
                    $Item->parcelamentoData = Util::dataBR($rst['execucao_servico_data_cobranca']);
                    $Item->numeroParcelaContexto = null;
                    $Item->quantidadeParcelas = null;
                    $Item->contratoId = (int) $rst['contrato_id'];
                    $Item->contratoNumero = $rst['contrato_numero'];
                    $Item->contratoSufixoNumero = $rst['contrato_sufixo_numero'];
                    $Item->localEntregaId = null;
                    $Item->localEntregaNome = null;
                    $Item->grupoIdsIdentificacaoItemCobranca = null;
                    $Item->execucaoServicoId = (int) $rst['execucao_servico_id'];
                    $Item->execucaoServicoDataCobranca = Util::dataBR($rst['execucao_servico_data_cobranca']);
                    $Item->tipoFaturamento = self::FATURAMENTO_EXEC_SERVICO;
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
            $clcRst = Application_Model_ExecucaoServico::obterColecaoLancamentosDeProdutosFaturados($faturaId);
            if (count($clcRst)) {
                foreach ($clcRst as $rst) {
                    $Item = new ItemFaturamento();
                    $Item->id = (int) $rst['produto_id'];
                    $Item->nome = $rst['produto_nome'];
                    $Item->quantidade = (int) $rst['produto_fatura_quantidade'];
                    $Item->valorUnitario = (float) $rst['produto_fatura_valor'];
                    $Item->valorTotal = (float) $rst['produto_fatura_valor_total'];
                    $Item->codigoFixo = $rst['produto_codigo_fixo'];
                    $Item->osId = null;
                    $Item->cobrancaId = null;
                    $Item->parcelamentoId = null;
                    $Item->parcelamentoData = Util::dataBR($rst['execucao_servico_data_cobranca']);
                    $Item->numeroParcelaContexto = null;
                    $Item->quantidadeParcelas = null;
                    $Item->contratoId = (int) $rst['contrato_id'];
                    $Item->contratoNumero = $rst['contrato_numero'];
                    $Item->contratoSufixoNumero = $rst['contrato_sufixo_numero'];
                    $Item->localEntregaId = null;
                    $Item->localEntregaNome = null;
                    $Item->grupoIdsIdentificacaoItemCobranca = $rst['fk_execucao_servico_id'];
                    $Item->tipoFaturamento = self::FATURAMENTO_PARCELA;
                    $Item->execucaoServicoId = $rst['fk_execucao_servico_id'];
                    $Item->execucaoServicoDataCobranca = Util::dataBR($rst['execucao_servico_data_cobranca']);
                    $Item->checked = true;
                    $resultado[] = $Item;
                }
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $resultado;
    }

}
