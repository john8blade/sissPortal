<?php

class FaturamentoCoordenacao extends FaturamentoAbstract {

    public function __construct() {
        
    }

    public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, array $colecaoContratoId = array(), array $colecaoEmpresaId = array(), array $colecaoUnidadeId = array()) {
        $resultado = array();
        try {
            $produtos = Application_Model_Parcelamento::obterColecaoParcelasCoordenacaoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $colecaoContratoId, $colecaoEmpresaId);
            if (count($produtos) > 0) {
                require_once("../application/business/FuncionarioBusiness.php");
                $NF = new FuncionarioBusiness();
                $ModeloProdutoContratado = new Application_Model_ProdutoContratado();
                foreach ($produtos as $rst) {
                    $Item = new ItemFaturamento();
                    $Item->id = (int) $rst['produto_id'];
                    $Item->nome = $rst['produto_nome'];
                    $regra = FuncionarioBusiness::REGRA_ATIVO_COORDENACAO_PCMSO;
                    $produtoContratadoId = (int) $rst['produto_contratado_id'];

                    $Cnx = Zend_Db_Table::getDefaultAdapter();
                    $Cmd = $Cnx->select()
                            ->from(array('pc' => 'produto_contratado'), array())
                            ->join(array('c' => 'contratante'), 'pc.fk_contrato_id = c.fk_contrato_id', array('contrato_id' => 'fk_contrato_id', 'empresa_id' => 'fk_empresa_id'))
                            ->where('c.contratante_empresa_principal = ?', 1)
                            ->where('pc.produto_contratado_id = ?', $produtoContratadoId);
                    $rst2 = $Cmd->query()->fetch();
                    $e = (int)$rst2['empresa_id'];
                    $c = (int)$rst2['contrato_id'];
                    
                    $r = $NF->obterColecaoFuncionariosAtivos(array($e), array($c), array(), 'pessoa.pessoa_id', $dataApuracaoInicio, $dataApuracaoTermino, $regra);
                    //$r = $NF->obterColecaoFuncionariosAtivos(array($rst['empresa_id']), array($rst['contrato_id']), array(), 'pessoa.pessoa_id', $dataApuracaoInicio, $dataApuracaoTermino, $regra);
                    $Item->quantidade = count($r);
                    $Item->empresaId = (int) $rst['empresa_id'];
                    $Item->empresaRazao = $rst['empresa_razao'];
                    $Item->valorUnitario = (float) $rst['produto_contratado_valor_venda'];
                    $Item->produtoContratadoId = (int) $rst['produto_contratado_id'];
                    // Regra de faturamento mínimo
                    $R = $ModeloProdutoContratado->fetchRow(array('produto_contratado_id = ?' => $Item->produtoContratadoId));
                    $fatMinino = ($R) ? $R->produto_contratado_faturamento_minimo : 0.00;
                    $v = (float) ($Item->valorUnitario * $Item->quantidade);
                    $vt = ($v < $fatMinino) ? $fatMinino : $v;
                    $Item->valorTotal = $vt;
                    $Item->codigoFixo = $rst['produto_codigo_fixo'];
                    $Item->osId = (int) $rst['os_id'];
                    $Item->cobrancaId = (int) $rst['os_id'];
                    $Item->parcelamentoId = (int) $rst['parcelamento_id'];
                    $Item->parcelamentoData = Util::dataBR($rst['parcelamento_data']);
                    $Item->numeroParcelaContexto = null;
                    $Item->quantidadeParcelas = null;
                    $Item->contratoId = (int) $rst['contrato_id'];
                    $Item->contratoNumero = $rst['contrato_numero'];
                    $Item->contratoSufixoNumero = $rst['contrato_sufixo_numero'];
                    $Item->localEntregaId = $rst['localentrega_id'];
                    $Item->localEntregaNome = $rst['localentrega_identificacao'];
                    $Item->grupoIdsIdentificacaoItemCobranca = $rst['grupo_parcela_id'];
                    $Item->tipoFaturamento = self::FATURAMENTO_COORDENACAO;
                    $Item->execucaoServicoId = null;
                    $Item->execucaoServicoDataCobranca = null;
                    $Item->checked = true;
                    $resultado[] = $Item;
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    public function obterColecaoProdutoFaturado($faturaId) {
        
    }

}
