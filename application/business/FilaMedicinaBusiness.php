<?php

require_once('FilaBusiness.php');

class FilaMedicinaBusiness extends FilaBusiness {

    public static function registrar($idReferenciaAgendamento = null, $registradoVia = null, $registradoViaDetalhes = null, $senhaCodPrefixo = null, $senhaCodSufixo = null, $transacaoSegura = true) {
        $id = null;
        self::$dhCriado = date('Y-m-d H:i:s');
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select()
                    ->from(array('a' => 'agenda'), array('agenda_data_exame', 'agenda_id', 'fk_contrato_id', 'fk_empresa_id', 'fk_tipoexame_id'))
                    ->join(array('al' => 'alocacao'), 'al.alocacao_id = a.fk_alocacao_id', array('fk_funcao_id', 'fk_cargo_id', 'fk_setor_id'))
                    ->join(array('te' => 'tipoexame'), 'te.tipoexame_id = a.fk_tipoexame_id', array())
                    ->join(array('ia' => 'intervalo_atendimento'), 'ia.intervalo_atendimento_id =  te.fk_intervalo_atendimento_id', array('intervalo_atendimento_senha_inicial', 'intervalo_atendimento_sufixo_senha', 'intervalo_atendimento_quantidade_atendimento', 'intervalo_atendimento_nome', 'intervalo_atendimento_id'))
                    ->join(array('c' => 'contrato'), 'c.contrato_id = a.fk_contrato_id', array())
                    ->join(array('u' => 'unidade'), 'u.unidade_id = c.fk_unidade_id', array('unidade_sigla', 'unidade_id'))
                    ->where('a.agenda_id = ?', (int) $idReferenciaAgendamento);
            $rst = $Cmd->query()->fetch();
            if (!$rst or count($rst) == 0)
                throw new Exception('Agendamento não localizado');

            $tipoExameId = (int) $rst['fk_tipoexame_id'];
            $setorId = (int) $rst['fk_setor_id'];
            $funcaoId = (int) $rst['fk_funcao_id'];
            $cargoId = (int) $rst['fk_cargo_id'];
            $contratoId = (int) $rst['fk_contrato_id'];
            $empresaId = (int) $rst['fk_empresa_id'];

            /*
             * Invoca um procedimento de banco de dados (Stored Procidure)
             * Consulte a documentação do procedimento no diretorio: docs/stored-procedure/doc-sp-obter-exame-pcmso-recente.docx
             * SpObterColecaoExameParaFuncaoDoPcmsoRecente(IN paramContratoId INT , IN paramEmpresaId INT, IN paramCargoId INT, IN paramFuncaoId INT, paramSetorId INT, IN paramTopoExameId INT)
             */
            $comando = "CALL SpObterColecaoExameParaFuncaoDoPcmsoRecente({$contratoId}, {$empresaId}, {$cargoId}, {$funcaoId}, {$setorId}, {$tipoExameId})";
            //$Comando = $Cnx->query($comando);
            //$rstColecaoProcedimentos = $Comando->fetchAll();
            $stmt = $Cnx->prepare($comando);
            $stmt->execute();
            $rstColecaoProcedimentos = $stmt->fetchAll();
            $stmt->closeCursor();

            self::$codControle = 'agenda.agenda_id=' . $rst['agenda_id'];
            /*
            // Verifica se já foi gerado uma senha para a agenda
            $ModeloFila = new Application_Model_Fila();
            $Rst = $ModeloFila->fetchRow(array('fila_cod_controle = ?' => self::$codControle, 'fila_status = ?' => 0));
            if ($Rst) {
                throw new Exception('Já existe registro de senha para este agendamento!');
            }
             */
            self::$deptoAtendimento = 'MED';
            self::$codSufixo = $rst['intervalo_atendimento_sufixo_senha'];
            self::$dtAtendimento = $rst['agenda_data_exame'];
            $i = $rst['intervalo_atendimento_nome'];
            $h = null;
            if (strlen($i) > 0) {
                $p = explode('-', $i);
                if (isset($p[0]) && is_numeric($p[0])) {
                    $h = $p[0] . ':00:00';
                }
            }
            self::$hrAtendimento = $h;
            self::$codSufixo = $rst['intervalo_atendimento_sufixo_senha'];
            self::$intervaloAtendimentoId = $rst['intervalo_atendimento_id'];
            self::$status = 0;
            self::$registradoVia = $registradoVia;
            self::$registradoViaDetalhes = $registradoViaDetalhes;
            self::$unidadeId = $rst['unidade_id'];
            self::$unidadeAtendimento = $rst['unidade_sigla'] . '-MED';
            self::$senha = self::_criarProximaSenha(self::$dtAtendimento, self::$intervaloAtendimentoId);
            self::_inicializarAtributos();
            $v = self::_eValido();
            if (!$v) {
                throw new Exception('Os atributos obrigatórios não informados!');
            }
            $c = self::_mapearObjetoFilaParaArrayAssociativo();
            $ModeloFila = new Application_Model_Fila();
            $c['fila_id'] = null;

            if ($transacaoSegura) {
                $Cnx->beginTransaction();
            }
            $id = null;
            $inserir = true;
            try {
                // Verifica se existe um registro de fila para o agendamento em contexto.
                $cod = 'agenda.agenda_id=' . $idReferenciaAgendamento;
                $RstFila = $ModeloFila->fetchRow(array('fila_cod_controle = ?' => $cod, 'fila_status = ?' => 0), array('fila_id DESC'));
                if ($RstFila) {
                    $ModeloTipoExame = new Application_Model_TipoExame();
                    $RstTipoExame = $ModeloTipoExame->fetchRow(array('tipoexame_id = ?' => $tipoExameId));
                    if ((int) $RstTipoExame->fk_intervalo_atendimento_id != (int) $RstFila->fk_intervalo_atendimento) {
                        $cln = array('fila_dh_alterado' => date('Y-m-d H:m:s'), 'fila_status' => 2);
                        $ModeloFila->update($cln, array('fila_id = ?' => $RstFila->fila_id));
                        $ModeloProcedimentoFila = new Application_Model_FilaAgendaProcedimento();
                        $ModeloProcedimentoFila->update(array('fila_agenda_procedimento_status' => 2), array('fila_id = ?' => $RstFila->fila_id));
                        $inserir = true;
                    } else {
                        $inserir = false;
                    }
                }
                if ($inserir) {
                    $id = $ModeloFila->insert($c);
                    if (is_array($rstColecaoProcedimentos) && count($rstColecaoProcedimentos) > 0) {
                        $ModeloFilaAgendaProc = new Application_Model_FilaAgendaProcedimento();
                        foreach ($rstColecaoProcedimentos as $item) {
                            $c = array(
                                'fila_agenda_procedimento_status' => 0,
                                'fila_id' => $id,
                                'fk_agenda_id' => $rst['agenda_id'],
                                'fk_produto_id' => $item['produto_id']
                            );
                            $ModeloFilaAgendaProc->insert($c);
                        }
                    }
                    $ModeloAgenda = new Application_Model_Agenda();
                    $c = array('fk_fila_id' => $id);
                    $ModeloAgenda->update($c, array('agenda_id = ?' => $rst['agenda_id']));
                }

                if ($transacaoSegura) {
                    $Cnx->commit();
                }
            } catch (Exception $exc1) {
                if ($transacaoSegura) {
                    $Cnx->rollBack();
                }
                throw $exc1;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $id;
    }

    private static function _criarProximaSenha($data, $intervaloAtendimentoId) {
        $nova = null;
        try {
            $ModeloIntervaloAtendimento = new Application_Model_IntervaloAtendimento();
            $Intervalo = $ModeloIntervaloAtendimento->fetchRow(array('intervalo_atendimento_id = ?' => $intervaloAtendimentoId));
            if (!$Intervalo) {
                throw new Exception('Intervalo de atendimento não localizado, isto torna impossível gerar o controle de senha');
            }
            $ModeloFila = new Application_Model_Fila();
            $f = array('fk_intervalo_atendimento = ?' => $intervaloAtendimentoId, 'fila_dt_atendimento = ?' => $data, 'fila_status = ?' => 0, 'fila_depto_atendimento = ?' => 'MED');
            $Rst = $ModeloFila->fetchRow($f, array('fila_senha DESC'));
            $prox = 1;
            if ($Rst) {
                $prox = (int) $Rst->fila_senha + 1;
            } else {
                $prox = (int) $Intervalo->intervalo_atendimento_senha_inicial;
            }
            $Rst = $ModeloFila->fetchAll($f);
            $q = $Rst->count();
            if ($q > $Intervalo->intervalo_atendimento_quantidade_atendimento) {
                throw new Exception('Limite de atendimento esgotado para o intervalo de atendimento de ' . $Intervalo->intervalo_atendimento_nome);
            }
            $nova = $prox;
        } catch (Exception $ex) {
            throw $ex;
            //throw new Exception('Fudeu é aqui');
        }
        return $nova;
    }

}
