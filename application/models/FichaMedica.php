<?php

class Application_Model_FichaMedica extends Zend_Db_Table {

    protected $_name = 'fichamedica';
    protected $_primary = 'fichamedica_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'fichamedica.fichamedica_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM fichamedica
                                       JOIN agenda ON agenda.`agenda_id` = fichamedica.`fk_agenda_id`
                                       LEFT JOIN biometria ON  biometria.biometria_id = fichamedica.`fk_biometria_id`
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    /**
     * Faz uma busca base de dados procurando pelos atendimentos com pendenceia
     * @param string $dataInicio
     * @param string $dataTermino
     * @param array $colecaoIdEmpresas Coleção de IDS da empresas para conter no relatório
     * @param array $colecaoIdContrato Coleção de IDS de contrato para conter no relatório
     * @param array $colecaoIdUnidade Coleção de IDS de unidade para conter no relatório
     * @throws Exception
     * @return array Uma coleção associativa com dados da empresa, contrato, cargo, funcao, funcionario
     */
    public function obterColecaoAtendimentoComPendencia($dataInicio = null, $dataTermino = null, array $colecaoIdEmpresas = array(), array $colecaoIdContrato = array(), array $colecaoIdUnidade = array()) {
        $resultado = array();
        $filtroPeriodo = ' AND 1 = 1 ';
        $filtroEmpresa = ' AND 1 = 1 ';
        $filtroContrato = ' AND 1 = 1 ';
        $filtroUnidade = ' AND 1 = 1 ';
        if (strlen($dataInicio) > 0 && strlen($dataTermino) > 0) {
            $filtroPeriodo = " AND a.agenda_data_clinico BETWEEN '{$dataInicio}' AND '{$dataTermino}' ";
        }

        if (count($colecaoIdEmpresas) > 0) {
            $x = implode(',', $colecaoIdEmpresas);
            $filtroEmpresa = " AND e.empresa_id IN($x) ";
        }

        if (count($colecaoIdContrato) > 0) {
            $x = implode(',', $colecaoIdContrato);
            $filtroContrato = " AND c.contrato_id  IN($x) ";
        }
        
        if (count($colecaoIdUnidade) > 0) {
            $x = implode(',', $colecaoIdUnidade);
            $filtroUnidade = " AND c.fk_unidade_id  IN($x) ";
        }

        $comando = "SELECT
                          e.*,
                          ca.cargo_nome,
                          p.*,
                          te.*,
                          a.agenda_data_clinico,
                          f.funcao_nome,
                          fm.fichamedica_detalhe_pedencia,
                          fm.fichamedica_id,
                          fm.fichamedica_resultado_aptidao,
                          e.empresa_razao,
                          e.empresa_fantasia,
                          e.empresa_cnpj,
                          c.contrato_numero
                    FROM fichamedica fm
                         JOIN agenda a ON a.agenda_id = fm.fk_agenda_id
                         JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                         JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                         JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                         JOIN tipoexame te ON te.tipoexame_id = a.fk_tipoexame_id
                         JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                         JOIN funcao f ON f.funcao_id = al.fk_funcao_id
                         JOIN cargo ca ON ca.cargo_id = al.fk_cargo_id                                                    
                    WHERE fm.fichamedica_status = 0
                          AND a.agenda_status = 0
                          AND (
                                fm.fichamedica_resultado_aptidao = 2
                                OR (fm.fichamedica_detalhe_pedencia <> '' AND fm.fichamedica_detalhe_pedencia IS NOT NULL)                                
                          )
                          {$filtroPeriodo}
                          {$filtroContrato}                              
                          {$filtroEmpresa}
                          {$filtroUnidade}
                    ORDER BY p.pessoa_nome ASC, e.empresa_razao ASC, f.funcao_nome ASC ";
                          //echo $comando; exit(0);
        try {
            $Cnx = $this->getDefaultAdapter();
            $resultado = $Cnx->fetchAll($comando);
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

}
