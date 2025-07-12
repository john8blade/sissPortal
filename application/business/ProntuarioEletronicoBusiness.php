<?php

define('PRONTUARIO_MEDICO', Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_EXAMES);
define('PRONTUARIO_TREINAMENTO', Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_TREINAMENTO);

class ProntuarioEletronicoBusiness {

    /**
     * Prontuário do tipo: Médico 
     */
    const PRONTUARIO_MEDICO = PRONTUARIO_MEDICO;

    /**
     * Prontuário do tipo: Treinamento 
     */
    const PRONTUARIO_TREINAMENTO = PRONTUARIO_TREINAMENTO;

    private static $colecaoTipoProntuarioValidos = array(PRONTUARIO_MEDICO, PRONTUARIO_TREINAMENTO);

    /**
     * Define se será lançado exceção caso ao ser gerado um prontuário do tipo treinamento
     * @var bool 
     */
    public static $lancarExcecaoFuncionarioNaoLocalizadoProntuarioTreinamento = true;

    public function __construct() {
        
    }

    /**
     * Adicionar um tipo de prontuário válido
     * @param string $tipo descição do tipo de prontuário
     * @throws Exception
     */
    public static function adicionarTipoProntuarioValido($tipo) {
        if (strlen($tipo) == 0) {
            throw new Exception('O parametro tipo não pode vazio');
        }
        self::$colecaoTipoProntuarioValidos[] = $tipo;
    }

    /**
     * Cria um prontuário seguindo às regras de negócio definido pela empresa
     * @param string $tipo Tipo de prontário Valores: ProntuarioEletronicoBusiness::PRONTUARIO_MEDICO | ProntuarioEletronicoBusiness::PRONTUARIO_MEDICO 
     * @param int $idAgendaMedicaOuTreinamento Id da agenda médica se o tipo for ProntuarioEletronicoBusiness::PRONTUARIO_MEDICO ou Id da agenda de treinamento se o tipo for ProntuarioEletronicoBusiness::PRONTUARIO_MEDICO 
     * @return int id do prontuário salvo | 0 
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     * 
     */
    public static function criarProntuarioComProcedimentoBaseadoContextoDaAgenda($tipo, $idAgendaMedicaOuTreinamento) {
        $id = 0;
        // Validando às entradas
        if (!in_array($tipo, self::$colecaoTipoProntuarioValidos))
            throw new Exception('O tipo de prontuario informado no parâmetro da chamada ao método não é valido');

        if (!is_numeric($idAgendaMedicaOuTreinamento) or (int) $idAgendaMedicaOuTreinamento == 0)
            throw new Exception('O parametro ID da agenda ou de treinamento deve ser tipo inteiro positivo e maior que zero');

        $pessoaId = $alocacaoId = $empresaId = $contratoId = 0;
        $colecaoProcedimentos = array();
        $chaveInserida = 0;
        // Verificando a existencia das agendas
        if (strcmp($tipo, self::PRONTUARIO_MEDICO) == 0) {
            $id = self::_criarProntuarioMedico($idAgendaMedicaOuTreinamento);
        } elseif (strcmp($tipo, self::PRONTUARIO_TREINAMENTO) == 0) {
            $id = self::_criarProntuarioTreinamento($idAgendaMedicaOuTreinamento);
        } else {
            throw new Exception('Este tipo de prontuário não está configurado. Atualmente este método opera com informações dos prontuários do tipo de Medicina e treinamento');
        }
        return $id;
    }

    /**
     * Cria um prontuário de treinamento seguindo às regras de negócio definidas pela empresa.
     * @param int $paramTreinamentoAgendadoId ID do agendamento do treinamento.
     * @return int Id Prontuario | 0 em caso de não sucesso
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiestg.com.br>
     */
    private static function _criarProntuarioTreinamento($paramTreinamentoAgendadoId) {
        $id = 0;
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cnx->beginTransaction();

            $ModeloProntuario = new Application_Model_Prontuario();
            try {
                // Resgata informações do agendamento do treinamento                
                $Comando = $Cnx->select();
                $Comando->from(array('tado' => 'treinamento_agendado'))
                        ->join(array('tada' => 'treinamento_agenda'), 'tada.treinamento_agenda_id = tado.fk_treinamento_agenda_id', array('treinamento_agenda_data_inicio'))
                        ->join(array('p' => 'produto'), 'p.produto_id = tada.fk_produto_id', array('produto_nome', 'produto_id', 'cfg_pront_eletr_val_prod_em_dia'))
                        ->where('tado.treinamento_agendado_id = ?', $paramTreinamentoAgendadoId);
                $rst = $Comando->query()->fetch();

                if (is_array($rst) && count($rst) > 0) {
                    $Comando = $Cnx->select();
                    /*
                     * Como a tabela treinamento_agendado não possuí que é o funcionário
                     * será preciso fazer uma consulta customizada para resgatar o ID do funcionário em
                     * que foi agendadado o treinamento. 
                     */
                    $ModeloFuncionario = new Application_Model_Funcionario();
                    $filtro = array(
                        'fk_empresa_id = ?' => $rst['fk_empresa_id'],
                        'fk_contrato_id = ?' => $rst['fk_contrato_id'],
                        'fk_pessoa_id = ?' => $rst['fk_pessoa_id']
                    );
                    $Rst = $ModeloFuncionario->fetchRow($filtro, array('funcionario_id DESC'));
                    if (!$Rst && self::$lancarExcecaoFuncionarioNaoLocalizadoProntuarioTreinamento)
                        throw new Exception('Funcionário não localizado!');
                    if ($Rst) {
                        // Monta uma estrutura para inserção
                        $prontuario = array(
                            'prontuario_data' => $rst['treinamento_agenda_data_inicio'],
                            'prontuario_descricao' => strtoupper($rst['produto_nome']),
                            'prontuario_dh_criacao' => date('Y-m-d H:i:s'),
                            'prontuario_tipo' => self::PRONTUARIO_TREINAMENTO,
                            'prontuario_status' => 0,
                            'fk_pessoa_id' => $rst['fk_pessoa_id'],
                            'fk_alocacao_id' => null,
                            'fk_contrato_id' => $rst['fk_contrato_id'],
                            'fk_empresa_id' => $rst['fk_empresa_id'],
                            'fk_agenda_exame_id' => null,
                            'fk_agenda_treinamento_id' => $rst['treinamento_agendado_id'],
                            'fk_funcionario_id' => $Rst->funcionario_id
                        );
                        $prontuarioId = $ModeloProntuario->insert($prontuario);
                        $id = $prontuarioId;
                        /*
                         * Os prontuários de treinamentos seguem uma linha bem diferente se comparado
                         * aos de exames. Para o prontuário de treinamento temos apenas dois procedimentos, que são:
                         * - Certificado
                         * - Avaliação
                         * Seguindo essa premissa iremos inserir dois procedimento para prontuário
                         */

                        // Resgata a quantidade de dias padrão definida na tabela parametro
                        $ModeloParam = new Application_Model_Parametro();
                        $RstParam = $ModeloParam->fetchRow(array('parametro_nome = ?' => 'ProntuarioEletronico.ValidadePadraoExameProntuarioEmDias'));
                        $paramQuantidadeDias = ($RstParam) ? (int) $RstParam->parametro_valor : 0;
                        $produtoConfiguracaoQuantidadeDias = (int) $rst['cfg_pront_eletr_val_prod_em_dia'];
                        $adicionarQuantidade = ($produtoConfiguracaoQuantidadeDias > 0) ? $produtoConfiguracaoQuantidadeDias : $paramQuantidadeDias;

                        $validade = date('Y-m-d', strtotime("{$prontuario['prontuario_data']} + {$adicionarQuantidade} days"));
                        $procedimento = array(
                            'procedimento_data' => $prontuario['prontuario_data'],
                            'procedimento_nome' => 'CERTIFICADO',
                            'procedimento_data_validade' => $validade,
                            'procedimento_status' => 0,
                            'procedimento_visib_externa' => 1,
                            'procedimento_visib_externa_medico' => 0,
                            'fk_prontuario_id' => $prontuarioId
                        );
                        $ModeloProcedimento = new Application_Model_Procedimento();
                        $ModeloProcedimento->insert($procedimento);
                        $procedimento['procedimento_nome'] = 'AVALIAÇÃO';
                        $ModeloProcedimento->insert($procedimento);
                    }
                }

                $Cnx->commit();
            } catch (Exception $ex) {
                $Cnx->rollBack();
                throw $ex;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $id;
    }

    /**
     * Cria um prontuário médico seguindo às regras de negócio da empresa
     * @param int $paramAgendaId Id da agenda médica
     * @return int
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    private static function _criarProntuarioMedico($paramAgendaId) {
        $id = 0;
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select();
            $Cmd->from(array('pa' => 'produto_agenda'), array('produto_agenda_data_programada'))
                    ->join(array('a' => 'agenda'), 'a.agenda_id = pa.fk_agenda_id', array('agenda_id', 'fk_empresa_id', 'fk_contrato_id', 'fk_pessoa_id', 'fk_alocacao_id', 'agenda_data_clinico'))
                    ->join(array('p' => 'produto'), 'p.produto_id = pa.fk_produto_id', array('produto_nome', 'cfg_pront_eletr_val_prod_em_dia', 'cfg_pront_eletr_fonte_resultado'))
                    ->join(array('alo' => 'alocacao'), 'alo.alocacao_id = a.fk_alocacao_id', array('fk_funcionario_id'))
                    ->join(array('te' => 'tipoexame'), 'te.tipoexame_id = a.fk_tipoexame_id', array('tipoexame_nome'))
                    ->where('pa.fk_agenda_id = ?', $paramAgendaId)
                    ->where('pa.produto_agenda_status = ?', 0)
                    ->where('pa.produto_agenda_data_programada IS NOT NULL');
            $clcRst = $Cmd->query()->fetchAll();
            if (count($clcRst) == 0)
                throw new Exception('Não foi possível localizar informações do agendamento médico!');
            $pessoaId = $clcRst[0]['fk_pessoa_id'];
            $alocacaoId = $clcRst[0]['fk_alocacao_id'];
            $empresaId = $clcRst[0]['fk_empresa_id'];
            $contratoId = $clcRst[0]['fk_contrato_id'];
            $tipoExame = $clcRst[0]['tipoexame_nome'];
            $agendaId = $clcRst[0]['agenda_id'];
            $funcionarioId = $clcRst[0]['fk_funcionario_id'];
            // Cria estrutura para inserção em prontuario
            $prontuario = array(
                'prontuario_data' => $clcRst[0]['agenda_data_clinico'],
                'prontuario_descricao' => strtoupper($tipoExame),
                'prontuario_dh_criacao' => date('Y-m-d H:i:s'),
                'prontuario_tipo' => self::PRONTUARIO_MEDICO,
                'prontuario_status' => 0,
                'fk_pessoa_id' => $pessoaId,
                'fk_alocacao_id' => $alocacaoId,
                'fk_contrato_id' => $contratoId,
                'fk_empresa_id' => $empresaId,
                'fk_agenda_exame_id' => $agendaId,
                'fk_funcionario_id' => $funcionarioId
            );
            $Cnx->beginTransaction();
            try {
                $ModeloPront = new Application_Model_Prontuario();
                $chaveInserida = $ModeloPront->insert($prontuario);
                $id = $chaveInserida;
                $ModeloProc = new Application_Model_Procedimento();
                // Gravando todos os procedimentos de um prontuário.
                $procedimentosInseridos = 0;
                //ProntuarioEletronico.ValidadePadraoExameProntuarioEmDias
                $ModeloParam = new Application_Model_Parametro();
                $Rst = $ModeloParam->fetchRow(array('parametro_nome = ?' => 'ProntuarioEletronico.ValidadePadraoExameProntuarioEmDias'));
                $qdp = ($Rst) ? (int) $Rst->parametro_valor : 0;
                foreach ($clcRst as $rst) {
                    // Se não conter a quantidade de dias no produto (cadastrado) é utilizado a quantidade defina no parametro ProntuarioEletronico.ValidadePadraoExameProntuarioEmDias
                    $dias = ((int) $rst['cfg_pront_eletr_val_prod_em_dia'] > 0) ? (int) $rst['cfg_pront_eletr_val_prod_em_dia'] : $qdp;
                    $realizado = $rst['produto_agenda_data_programada'];
                    $validade = date('Y-m-d', strtotime("{$realizado} + {$dias} days"));
                    $procedimento = array(
                        'procedimento_data' => $realizado,
                        'procedimento_nome' => mb_strtoupper($rst['produto_nome']),
                        'procedimento_data_validade' => $validade,
                        'procedimento_status' => 0,
                        'procedimento_visib_externa' => 1,
                        'procedimento_visib_externa_medico' => 1,
                        'fk_prontuario_id' => $chaveInserida
                    );                    
                    $procedimentosInseridos += $ModeloProc->insert($procedimento);
                }
                if ($procedimentosInseridos == 0) {
                    $chaveInserida = 0;
                    $Cnx->rollBack();
                } else {
                    $Cnx->commit();
                }
            } catch (Exception $ex) {
                $Cnx->rollBack();
                throw $ex;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $id;
    }

}