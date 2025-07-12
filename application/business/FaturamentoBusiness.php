<?php

require_once(APPLICATION_PATH . '/business/faturamento/Empresa.php');
require_once(APPLICATION_PATH . '/business/faturamento/Endereco.php');
require_once(APPLICATION_PATH . '/business/faturamento/ItemFaturamento.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoAbstract.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoExame.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoParcela.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoCoordenacao.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoExecucaoServico.php');
require_once(APPLICATION_PATH . '/business/faturamento/FaturamentoImposto.php');

class FaturamentoBusiness {

    public $EmpresaFavorecida;
    public $EmpresaFaturamento;
    public $Imposto;
    public $Contrato;
    public $Cedente;
    public $faturaId;
    public $faturaNumero;
    public $faturaDataInicioApuracao;
    public $faturaDataTerminoApuracao;
    public $faturaDataHoraCriacao;
    public $faturaDataHoraAlteracao;
    public $faturaDataVencimento;
    public $faturaDataPagamento;
    public $faturaValorDesconto = 0;
    public $faturaValorMultas = 0;
    public $faturaValorJuros = 0;
    public $faturaValorTotal = 0;
    public $faturaValorCobranca = 0;
    public $faturaValorOutrasDeducoes = 0;
    public $faturaValorOutrosAcrescimos = 0;
    public $faturaJustificativaJurosMulta;
    public $faturaJustificativaDesconto;
    public $faturaNFEmitida;
    public $faturaReterImpostoISS;
    public $faturaDataEmissaoNF;
    public $faturaNumeroNotaFiscal;
    public $faturaStatusNome;
    public $faturaStatusId;
    public $faturaArquivoUploadId;
    public $faturaInfImportantes;
    public $ItensFaturamento = array();

    /**
     * Exclui uma fatura de forma segura.
     * @param int $faturaId - Id da fatura a ser excluída.
     * @return boolean
     * @throws Exception
     */
    public function excluir($faturaId) {
        $excluido = false;
        $comando = "SELECT p.*
                    FROM `produto_fatura` AS pf
                          JOIN parcelamento AS p ON p.parcelamento_id = pf.fk_parcelamento_id
                    WHERE produto_fatura_grupo_faturamento = 'COORDENACAO'
                          AND pf.produto_fatura_status = 0
                          AND pf.fk_fatura_id = ?
			  AND p.parcelamento_faturado = 1";
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cnx->beginTransaction();
            try {
                $clcRst = $Cnx->fetchAll($comando, array($faturaId));

                $parcelamentosCoordenacao = array();
                if (is_array($clcRst) && count($clcRst) > 0) {
                    $parcelamentosCoordenacao = $clcRst;
                }
                $ModeloParcelamento = new Application_Model_Parcelamento();

                foreach ($parcelamentosCoordenacao as $parc) {
                    $sequencia = (int) $parc['parcelamento_sequencia'];
                    $cobrancaId = (int) $parc['fk_cobrancaos_id'];

                    // Inativa o parcelamento ATUAL
                    $p = $parc;
                    $id = $p['parcelamento_id'];
                    $p['parcelamento_status'] = 2; // 2 = Excluído para Banco de dados
                    unset($p['parcelamento_id']);
                    $c = $ModeloParcelamento->update($p, array('parcelamento_id = ?' => $id));
                    // Cria um novo parcelamento como NAO FATURADO                    
                    $p = $parc;
                    unset($p['parcelamento_id']);
                    $p['parcelamento_faturado'] = 0;
                    $ModeloParcelamento->insert($p);

                    // Deleta às parcela futuras NAO FATURADA
                    $condicao = array(
                        'parcelamento_faturado = ?' => 0,
                        'parcelamento_sequencia > ?' => $sequencia,
                        'fk_cobrancaos_id = ?' => $cobrancaId
                    );
                    $ModeloParcelamento->update(array('parcelamento_status' => 2), $condicao);
                }

                $comando = "SET @paramFaturaId := ?;

                        UPDATE fatura SET fatura_status = 2 WHERE `fatura_id` = @paramFaturaId;
                        UPDATE produto_fatura SET `produto_fatura_status` = 2 WHERE `fk_fatura_id` = @paramFaturaId;

                        /*
                        * Marca os lancamentos de execucao_servico como não faturados no processo de cancelamento;
                        */
                        UPDATE execucao_servico
                        SET execucao_servico_faturado = 0
                        WHERE execucao_servico_id IN (
                                SELECT pf.fk_execucao_servico_id  
                                FROM produto_fatura AS pf
                                WHERE pf.produto_fatura_grupo_faturamento = 'EXEC_SERVICO'
                                                AND pf.fk_fatura_id = @paramFaturaId
                        );
                        
                        /*
                        * Marca os parcelamentos das OS como nao faturado 
                        */
                        UPDATE parcelamento
                        SET parcelamento_faturado = 0
                        WHERE parcelamento_id IN (
                                SELECT pf.fk_parcelamento_id   
                                FROM produto_fatura AS pf
                                WHERE pf.produto_fatura_grupo_faturamento = 'PARCELA'
                                                AND pf.fk_fatura_id = @paramFaturaId
                        );
                        SET @paramFaturaId := NULL;";
                $Cnx->query($comando, array($faturaId));
                $Cnx->commit();
                $excluido = true;
            } catch (Exception $exc) {
                $Cnx->rollBack();
                throw $exc;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $excluido;
    }

    public function obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratoId, $empresaId) {
        $resultado = null;
        $contratosId = array((int) $contratoId);
        $empresasId = array((int) $empresaId);
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select()
                    ->from(array('c' => 'contrato'), array('contrato_id'))
                    ->join(array('ce' => 'contratante'), 'ce.fk_contrato_id = c.contrato_id AND ce.contratante_empresa_principal = 1', array('empresa_id' => 'fk_empresa_id'))
                    ->where('c.fk_contrato_id = ?', 0)
                    ->where('c.contrato_status = ?', 0);
            $rst = $Cmd->query()->fetchAll();
            // Verificando se ter subcontratos
            if (isset($rst['contrato_id'])) {
                foreach ($rst as $r) {
                    $contratosId[] = (int) $r['contrato_id'];
                    $empresasId[] = (int) $r['empresa_id'];
                }
            }

            $Faturamento = new FaturamentoBusiness();
            // Faturamento de Parcelas
            $Parcela = new FaturamentoParcela();
            $r1 = $Parcela->obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratosId, $empresasId);

            // Exames
            $Exame = new FaturamentoExame();
            $r2 = $Exame->obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratosId, $empresasId);

            $ExecServico = new FaturamentoExecucaoServico();
            $r3 = $ExecServico->obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratosId, $empresasId);

            $Coordenacao = new FaturamentoCoordenacao();
            $r4 = $Coordenacao->obterColecaoProdutoParaFaturar($dataApuracaoInicio, $dataApuracaoTermino, $contratosId, $empresasId);

            $itens = array_merge($r1, $r2, $r3, $r4);

            foreach ($itens as $item) {
                $item->checked = true;
                $Faturamento->faturaValorTotal += (float) $item->valorTotal;
                $Faturamento->ItensFaturamento[] = $item;
            }

            if (count($Faturamento->ItensFaturamento) > 0) {
                $Faturamento->Imposto = new FaturamentoImposto();
                $Empresa = new Application_Model_Empresa();
                $Rst = $Empresa->fetchRow(array('empresa_id = ?' => $empresaId));
                if ($Rst && (int) $Rst->fk_fatura_cedente_id > 0) {
                    $Cmd = $Cnx->select()
                            ->from(array('e' => 'empresa'))
                            ->join(array('en' => 'endereco'), 'en.endereco_id = e.fk_endereco_id')
                            ->where('e.empresa_id = ?', $empresaId);
                    $Faturamento->EmpresaFavorecida = $Cmd->query()->fetch();
                    $Cmd = null;
                    $ModeloContrato = new Application_Model_Contrato();
                    $Faturamento->Contrato = $ModeloContrato->fetchRow(array('contrato_id = ?' => $contratoId))->toArray();

                    $Cmd = $Cnx->select()
                            ->from(array('cf' => 'fatura_cedente'))
                            ->join(array('e' => 'endereco'), 'e.endereco_id = cf.fk_endereco_id')
                            ->where('cf.fatura_cedente_id = ?', $Rst->fk_fatura_cedente_id);
                    $Faturamento->Cedente = $Cmd->query()->fetch();

                    //Dados para envio da nota Fiscal
                    $Cmd = $Cnx->select()
                            ->from(array('er' => 'empresaresponsabilidade', array()))
                            ->join(array('e' => 'empresa'), 'e.empresa_id = er.fk_empresa_faturamento')
                            ->joinLeft(array('en' => 'endereco'), 'en.endereco_id = e.fk_endereco_id')
                            ->where('er.fk_contrato_id = ?', $contratoId)
                            ->where('er.empresaresponsabilidade_status = ?', 0)
                            ->order(array('er.empresaresponsabilidade_id DESC'));
                    $rst = $Cmd->query()->fetch();
                    if (strlen($rst['endereco_complemento']) == 0) {
                        $rst['endereco_complemento'] = $rst['empresa_complemento'];
                    }
                    if (strlen($rst['endereco_numero']) == 0) {
                        $rst['endereco_numero'] = $rst['empresa_numero'];
                    }
                    $Faturamento->EmpresaFaturamento = $rst;
                }
                $resultado = $Faturamento;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    /**
     * Obtem produtos faturados
     * @param int $faturaId Id da fatura
     * @return \FaturamentoBusiness
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public function obterColecaoProdutosFaturados($faturaId) {
        $Faturamento = null;
        try {
            $ModeloFatura = new Application_Model_Fatura();
            $Rst = $ModeloFatura->fetchRow(array('fatura_id = ?' => $faturaId));
            if (!$Rst) {
                throw new Exception('Fatura não localizada!');
            }
            $Cnx = Zend_Db_Table::getDefaultAdapter();

            $Faturamento = new FaturamentoBusiness();
            $Faturamento->faturaArquivoUploadId = (int) $Rst->fk_arquivo_upload_id > 0 ? (int) $Rst->fk_arquivo_upload_id : null;
            $Faturamento->faturaDataEmissaoNF = Util::dataBR($Rst->fatura_nf_dt_emissao);
            $Faturamento->faturaDataHoraAlteracao = Util::dataBR($Rst->fatura_data_hora_alteracao);
            $Faturamento->faturaDataHoraCriacao = Util::dataBR($Rst->fatura_data_hora_criacao);
            $Faturamento->faturaDataInicioApuracao = Util::dataBR($Rst->fatura_data_inicio_apuracao);
            $Faturamento->faturaDataVencimento = Util::dataBR($Rst->fatura_data_vencimento);
            $Faturamento->faturaDataPagamento = Util::dataBR($Rst->fatura_data_pagamento);
            $Faturamento->faturaDataTerminoApuracao = Util::dataBR($Rst->fatura_data_fim_apuracao);
            $Faturamento->faturaId = (int) $Rst->fatura_id > 0 ? (int) $Rst->fatura_id : null;
            $Faturamento->faturaInfImportantes = $Rst->fatura_info_imp;
            $Faturamento->faturaJustificativaDesconto = $Rst->fatura_justificativa_desconto;
            $Faturamento->faturaJustificativaJurosMulta = $Rst->fatura_justificativa_juros_multas;
            $Faturamento->faturaNFEmitida = $Rst->fatura_nf_emitida;
            $Faturamento->faturaNumero = $Rst->fatura_id;
            $Faturamento->faturaNumeroNotaFiscal = $Rst->fatura_numero_nota_fiscal;
            $Faturamento->faturaReterImpostoISS = $Rst->fatura_reter_imposto_iss;
            $Faturamento->faturaStatusId = $Rst->fk_statusfatura_id;
            if (is_numeric($Rst->fk_statusfatura_id)) {
                $ModeloStatus = new Application_Model_StatusFatura();
                $RstStatus = $ModeloStatus->fetchRow(array('statusfatura_id = ?' => $Rst->fk_statusfatura_id));
                $Faturamento->faturaStatusNome = $RstStatus->statusfatura_nome;
            }
            $Faturamento->faturaValorCobranca = (float) $Rst->fatura_valor_a_cobrar;
            $Faturamento->faturaValorDesconto = (float) $Rst->fatura_valor_desconto;
            $Faturamento->faturaValorJuros = (float) $Rst->fatura_valor_juros;
            $Faturamento->faturaValorMultas = (float) $Rst->fatura_valor_multa;
            $Faturamento->faturaValorOutrasDeducoes = (float) $Rst->fatura_valor_outras_deducoes;
            $Faturamento->faturaValorOutrosAcrescimos = (float) $Rst->fatura_valor_outros_acrescimos;
            $Faturamento->faturaValorTotal = (float) $Rst->fatura_valor_total;
            $Faturamento->faturaInformacoesImportante = $Rst->fatura_info_imp;
            // Atribuindo valor nas propriedades do tipo exame.
            $Cmd = $Cnx->select()
                    ->from(array('e' => 'empresa'))
                    ->join(array('en' => 'endereco'), 'en.endereco_id = e.fk_endereco_id')
                    ->where('e.empresa_id = ?', $Rst->fk_empresa_id);
            $Faturamento->EmpresaFavorecida = $Cmd->query()->fetch();

            $Cmd = null;
            $ModeloContrato = new Application_Model_Contrato();
            $Faturamento->Contrato = $ModeloContrato->fetchRow(array('contrato_id = ?' => $Rst->fk_contrato_id))->toArray();
            if (isset($Rst->fk_fatura_cedente_id) && (int) $Rst->fk_fatura_cedente_id > 0) {
                $Cmd = $Cnx->select()
                        ->from(array('cf' => 'fatura_cedente'))
                        ->join(array('e' => 'endereco'), 'e.endereco_id = cf.fk_endereco_id')
                        ->where('cf.fatura_cedente_id = ?', $Rst->fk_fatura_cedente_id);
                $Faturamento->Cedente = $Cmd->query()->fetch();
            }

            if (isset($Rst->fk_envio_fatura_id) && (int) $Rst->fk_envio_fatura_id > 0) {
                $Cmd = null;
                $Cmd = $Cnx->select()
                        ->from(array('ef' => 'envio_fatura'), array())
                        ->join(array('ea' => 'empresa'), 'ea.empresa_id = ef.fk_empresa_id')
                        ->join(array('eo' => 'endereco'), 'eo.endereco_id = ef.fk_endereco_id')
                        ->where('ef.envio_fatura_id = ?', $Rst->fk_envio_fatura_id);
                $Faturamento->EmpresaFaturamento = $Cmd->query()->fetch();
            }

            // Colecao de Impostos
            $tabela = array();
            $ModeloImpostos = new Application_Model_Imposto();
            $RstImp = $ModeloImpostos->fetchAll();
            if ($RstImp->count()) {
                $ci = $RstImp->toArray();
                foreach ($ci as $i) {
                    $tabela[trim(strtolower($i['imposto_nome']))] = 0;
                }
            }

            $Cmd = $Cnx->select()
                    ->from(array('fi' => 'fatura_imposto'))
                    ->join(array('i' => 'imposto'), 'i.imposto_id = fi.fk_imposto_id')
                    ->where('fi.fk_fatura_id = ?', $Rst->fatura_id)
                    ->where('fi.fatura_imposto_status = 0');

            $clcRstImp = $Cmd->query()->fetchAll();
            foreach ($clcRstImp as $item) {
                $n = trim(strtolower($item['imposto_nome']));
                $tabela[$n] = (float) $item['fatura_imposto_valor'];
            }

            $Faturamento->Imposto = (object) $tabela;

            // Resgata os produtos faturados
            $Parcela = new FaturamentoParcela();
            // Lembrando que além do próprio grupo PARCELAMENTO, o GRUPO "COORDENACAO também é registrado 
            // no faturamento como uma parcela.
            $r1 = $Parcela->obterColecaoProdutoFaturado($Rst->fatura_id);
            $Exame = new FaturamentoExame();
            $r2 = $Exame->obterColecaoProdutoFaturado($Rst->fatura_id);
            $ExecucaoServico = new FaturamentoExecucaoServico();
            $r3 = $ExecucaoServico->obterColecaoProdutoFaturado($Rst->fatura_id);

            $Faturamento->ItensFaturamento = array_merge($r1, $r2, $r3);
        } catch (Exception $ex) {
            throw $ex;
        }

        return $Faturamento;
    }

}
