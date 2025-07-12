<?php

class FuncionarioBusiness {

    const REGRA_ATIVO_COORDENACAO_PCMSO = 2;
    const REGRA_ATIVO_GERAL = 1;

    public function __construct() {
        
    }

    public function obterColecaoFuncionariosInativos(array $colecaoEmpresasIds = array(), array $colecaoContratosIds = array(), array $colecaoCpfs = array(), $ordenarPor = 'pessoa.pessoa_nome') {
        $resultado = array();

        if (!is_array($colecaoEmpresasIds) or ! is_array($colecaoContratosIds) or ! is_array($colecaoCpfs)) {
            throw new Exception('Os parametros: $colecaoEmpresasIds, $colecaoContratosIds e $colecaoCpfs devem ser ser do tipo array');
        }

        $prefixoFiltro = ' funcionario.funcionario_status = 0 ';

        $filtroContrato = $filtroEmpresa = $filtroFuncionario = ' AND 1 = 1';

        // Filtra as empresas
        if (count($colecaoEmpresasIds) > 0) {
            $strIds = implode(',', $colecaoEmpresasIds);
            $filtroEmpresa = " AND funcionario.fk_empresa_id IN({$strIds})";
        }

        // Filtra os contratos
        if (count($colecaoContratosIds) > 0) {
            $strIds = implode(',', $colecaoContratosIds);
            $filtroContrato = " AND funcionario.fk_contrato_id IN({$strIds})";
        }

        // Filtra os funcionários pelo parametro CPF
        if (count($colecaoCpfs) > 0) {
            $strIds = implode(',', $colecaoCpfs);
            $filtroFuncionario = " AND pessoa.pessoa_cpf IN({$strIds})";
        }

        $filtro = $prefixoFiltro . $filtroEmpresa . $filtroContrato . $filtroFuncionario;
        try {
            $ModeloAlocacao = new Application_Model_Alocacao();
            /*
             * Até aqui temos todos os funcionários que atendem aos parametros
             * usados na consulta
             */
            $colecaoPrimeiroLoteResultado = $ModeloAlocacao->buscaCompletaUsandoClausula($filtro, $ordenarPor);

            if (is_array($colecaoPrimeiroLoteResultado) && count($colecaoPrimeiroLoteResultado) > 0) {
                $colecaoResultadosValidos = array();
                $colecaoParaConsulta = array();

                /*
                 * Foi criado um procedimento para criar uma lista
                 * filtrando o CPF, empresa e contrato. Nesta lista os registros
                 * são agrupados por CPF, Empresa e Contrato, o que facilita para verificar
                 * a situação médica do funcionario
                 *  
                 */
                foreach ($colecaoPrimeiroLoteResultado as $item) {
                    $queroEncontrar = $item['pessoa_cpf'] . $item['fk_empresa_id'] . $item['fk_contrato_id'];
                    if (!in_array($queroEncontrar, $colecaoParaConsulta)) {
                        $colecaoResultadosValidos[] = $item;
                        $colecaoParaConsulta[] = $queroEncontrar;
                    }
                }

                /*
                 * Será feito procedimento de verificação da situação do CPF
                 * na medicina para tomada de decisão se o funcionário está ou não ativo.
                 */
                foreach ($colecaoResultadosValidos as $itemResultado) {
                    $Comando = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
                    $Comando->from(array('a' => 'agenda'))
                            ->join(array('te' => 'tipoexame'), 'te.tipoexame_id = a.fk_tipoexame_id')
                            ->join(array('fm' => 'fichamedica'), 'fm.fk_agenda_id = a.agenda_id AND fm.fichamedica_status = 0')
                            ->join(array('p' => 'pessoa'), 'p.pessoa_id = a.fk_pessoa_id', array())
                            ->where('a.agenda_status = ?', 0)
                            ->where('a.fk_empresa_id = ?', $itemResultado['fk_empresa_id'])
                            ->where('a.fk_contrato_id = ?', $itemResultado['fk_contrato_id'])
                            ->where('p.pessoa_cpf = ?', $itemResultado['pessoa_cpf'])
                            ->where('fm.fichamedica_resultado_aptidao = ?', 1)
                            ->order('a.agenda_data_clinico DESC');
                    $resultadoComando = $Comando->query()->fetch();
                    //var_dump($resultadoComando);
                    //exit(0);
                    if (is_array($resultadoComando) && count($resultadoComando) > 0) {
                        $eUmDemissional = (strtoupper($resultadoComando['tipoexame_sigla']) == 'DEM' or strtoupper(trim($resultadoComando['tipoexame_nome']) == 'DEMISSIONAL')) ? true : false;
                        if ($eUmDemissional == true) {
                            $resultado[] = $itemResultado;
                        }
                    }
                    unset($Comando);
                }
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

    /**
     * Retorna uma coleção de funcionários ativos
     * @author Silas Stoffel <silas.stoffel@hiest.com>
     * @access public
     * @copyright (c) 2015, Hiest Assessoria LTDA
     * @param array $colecaoEmpresasIds - Coleção com ids de empresas em formato inteiro positivo maior que zero.
     * @param array $colecaoContratosIds - Coleção com ids dos contratos em formato inteiro positivo maior que zero.
     * @param array $colecaoFuncionariosIds - Coleção com ids de funcionarios em formato inteiro positivo maior que zero.
     * @param date $dataDe - Data em que será iniciado a contagem.
     * @param date $dataAte - Data em que será finalizado a contagem.
     * @param int $usarRegra [Opcional] - Define como será criado a listagem de funcionários ativos.
     * @return array - Uma coleção de funcionários ativos.
     * @throws Exception
     */
    public function obterColecaoFuncionariosAtivos(array $colecaoEmpresasIds = array(), array $colecaoContratosIds = array(), array $colecaoFuncionariosIds = array(), $ordenarPor = 'pessoa.pessoa_nome', $dataDe = null, $dataAte = null, $usarRegra = self::REGRA_ATIVO_GERAL) {
        $resultado = array();
        $dataInicialFixa = '2010-01-01';
        if (!is_array($colecaoEmpresasIds) or ! is_array($colecaoContratosIds) or ! is_array($colecaoFuncionariosIds)) {
            throw new Exception('Os parametros: $colecaoEmpresasIds, $colecaoContratosIds e $colecaoFuncionariosIds devem ser ser do tipo array');
        }

        $prefixoFiltro = ' funcionario.funcionario_status = 0';
        $filtroContrato = $filtroEmpresa = $filtroFuncionario = ' AND 1 = 1';
        
        // Habilitado 29/09/2015 Márcio pediu     
        $filtroFuncionario .= " AND (funcionario.funcionario_motivo_inativacao = '' OR  funcionario.funcionario_motivo_inativacao IS NULL ) ";
        
        /*
          Cancelando em 29/09/2015                   
          if ($usarRegra == self::REGRA_ATIVO_GERAL) {
          $filtroFuncionario .= " AND (funcionario.funcionario_motivo_inativacao = '' OR  funcionario.funcionario_motivo_inativacao IS NULL ) ";
          }
         */

        // Filtra as empresas
        if (count($colecaoEmpresasIds) > 0) {
            $strIds = implode(',', $colecaoEmpresasIds);
            $filtroEmpresa = " AND funcionario.fk_empresa_id IN({$strIds})";
        }

        // Filtra os contratos
        if (count($colecaoContratosIds) > 0) {
            $strIds = implode(',', $colecaoContratosIds);
            $filtroContrato = " AND funcionario.fk_contrato_id IN({$strIds})";
        }

        // Filtra os funcionários
        if (count($colecaoFuncionariosIds) > 0) {
            $strIds = implode(',', $colecaoFuncionariosIds);
            $filtroFuncionario .= " AND funcionario.funcionario_id IN({$strIds})";
        }

        if ($usarRegra == self::REGRA_ATIVO_COORDENACAO_PCMSO) {
            $filtroFuncionario .= " AND funcionario.funcionario_data_admissao <= '{$dataAte}' ";
        }

        $filtro = $prefixoFiltro . $filtroEmpresa . $filtroContrato . $filtroFuncionario;
        try {
            $ModeloAlocacao = new Application_Model_Alocacao();
            $colecaoPrimeiroLoteResultado = $ModeloAlocacao->buscaCompletaUsandoClausula($filtro, $ordenarPor);



            if (is_array($colecaoPrimeiroLoteResultado) && count($colecaoPrimeiroLoteResultado) > 0) {
                /*
                 * 
                 * P1 - Para cada funcionario resultado será verificado se 
                 * o ultimo exame é um demissional APTO. Sendo um demissional este funcionário
                 * não poderá estar na lista.
                 * 
                 * P2 - Independentemente se o funcionário fez ou não fez um exames do tipo 
                 * admissional ele já é um efetivo desde que, basta
                 * apenas estar cadastrado e alocado.
                 */

                foreach ($colecaoPrimeiroLoteResultado as $itemResultado) {
                    $Comando = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
                    $Comando->from(array('a' => 'agenda'))
                            ->join(array('te' => 'tipoexame'), 'te.tipoexame_id = a.fk_tipoexame_id')
                            ->join(array('fm' => 'fichamedica'), 'fm.fk_agenda_id = a.agenda_id AND fm.fichamedica_status = 0')
                            ->where('a.agenda_status = ?', 0)
                            ->where('a.fk_empresa_id = ?', $itemResultado['fk_empresa_id'])
                            ->where('a.fk_contrato_id = ?', $itemResultado['fk_contrato_id'])
                            ->where('a.fk_alocacao_id = ?', $itemResultado['alocacao_id'])
                            ->where('fm.fichamedica_resultado_aptidao = ?', 1);

                    if (strlen($dataAte) >= 10) {
                        $personalizado = "a.agenda_data_clinico BETWEEN '$dataInicialFixa' AND '$dataAte' ";
                        /*
                          if ($usarRegra == self::REGRA_ATIVO_COORDENACAO_PCMSO) {
                          $personalizado = "a.agenda_data_clinico <= '$dataAte' ";
                          } */
                        $Comando->where($personalizado);
                    }
                    $Comando->order('a.agenda_data_clinico DESC');

                    $resultadoComando = $Comando->query()->fetch();
                    if (is_array($resultadoComando) && count($resultadoComando) > 0) {
                        $eUmDemissional = (strtoupper($resultadoComando['tipoexame_sigla']) == 'DEM' or strtoupper(trim($resultadoComando['tipoexame_nome']) == 'DEMISSIONAL')) ? true : false;
                        if ($eUmDemissional == false) {
                            $resultado[] = $itemResultado;
                        } else if ($eUmDemissional == true && $usarRegra == self::REGRA_ATIVO_COORDENACAO_PCMSO) {
                            //echo 'Estou aqui <br/>';
                            /*
                             * Na regra de contagem de efetivo para coordenação de PCMSO é verificado se o demissional
                             * foi realizado ainda no mês(período) de cobrança, se sim pelo menos para cobrança ele é considerado
                             * efetivo mesmo já tendo sido realizado o demissional.
                             */
                            $Comando2 = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
                            $personalizado = "a.agenda_data_clinico BETWEEN '$dataDe' AND '$dataAte' ";
                            $Comando2->from(array('a' => 'agenda'))
                                    ->where('a.agenda_id = ?', $resultadoComando['agenda_id'])
                                    ->where($personalizado);
                            $resultadoConfirmado = $Comando2->query()->fetch();
                            if (is_array($resultadoConfirmado) && count($resultadoConfirmado) > 0) {
                                $resultado[] = $itemResultado;
                            }
                        }
                    } else {
                        /*
                         * Parece meio confuso cair neste bloco, porém existe uma explicação, sendo,
                         * Pode acontecer de um funcionário estar cadastrado (e ser efetivo) e ainda não ter um agendamento (o comando acima verifica a agenda).
                         * Quando a consulta acima retornar resultado deve-se verificar se o ultimo agendamento é de um exame do tipo
                         * demissional e seja apto, caso sim esse funcionário está inativo. 
                         * 
                         */
                        $resultado[] = $itemResultado;
                    }
                    unset($Comando);
                }
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

}
