<?php

class Application_Model_Prontuario extends Zend_Db_Table {

    protected $_name = 'prontuario';
    protected $_primary = 'prontuario_id';

    const FLAG_TIPO_PRONTUARIO_EXAMES = 'EXAME';
    const FLAG_TIPO_PRONTUARIO_TREINAMENTO = 'TREINAMENTO';
    const FLAG_TIPO_PRONTUARIO_OUTROS = 'OUTRO';

    /**
     * Resgata os registros de prontuário
     * @param bool $apenasAtivos [OPCIONAL] Define registros ativos e inativo. Padrão: TRUE
     * @param array $colecaoColunaOrdenacao [OPCIONAL] array a ordenação de colunas.
     * @param array $colecaoProntuarioId [OPCIONAL] array com ids de prontuário.
     * @param array $colecaoPessoaId [OPCIONAL] array com ids de pessoa.
     * @param array $colecaoAlocacaoId [OPCIONAL] array com ids de alocacao.
     * @param array $colecaoProntuarioTipo [OPCIONAL] array com os tipos de prontuário. Para saber os tipo resgata os tipo pelas constates: FLAG_TIPO_PRONTUARIO_EXAMES,FLAG_TIPO_PRONTUARIO_TREINAMENTO,FLAG_TIPO_PRONTUARIO_OUTROS
     * @param array $colecaoEmpresaId [OPCIONAL] array com ids de empresas
     * @param array $colecaoContratoId [OPCIONAL] array com ids de contratos
     * @return array 
     * @throws Exception
     */
    public static function obterColecaoProntuario($apenasAtivos = true, array $colecaoColunaOrdenacao = array(), array $colecaoProntuarioId = array(), array $colecaoPessoaId = array(), array $colecaoAlocacaoId = array(), array $colecaoProntuarioTipo = array(), array $colecaoEmpresaId = array(), array $colecaoContratoId = array()) {
        $filtro = '1 = 1';
        $ordenacao = 'prontuario.prontuario_id';
        $params = array();
        if (count($colecaoColunaOrdenacao) > 0) {
            $ordenacao = implode(',', $colecaoColunaOrdenacao);
        }
        if ($apenasAtivos) {
            $filtro .= " AND prontuario.prontuario_status = ?";
            $params[] = 0;
        }
        if (count($colecaoProntuarioId) > 0) {
            $x = implode(',', $colecaoProntuarioId);
            $filtro .= " AND prontuario.prontuario_id IN({$x})";
        }
        if (count($colecaoPessoaId) > 0) {
            $x = implode(',', $colecaoPessoaId);
            $filtro .= " AND prontuario.fk_pessoa_id IN({$x})";
        }
        if (count($colecaoAlocacaoId) > 0) {
            $x = implode(',', $colecaoAlocacaoId);
            $filtro .= " AND prontuario.fk_alocacao_id IN({$x})";
        }
        if (count($colecaoProntuarioTipo) > 0) {
            $c = array();
            foreach ($colecaoProntuarioTipo as $k => $item) {
                $c[] = 'prontuario.prontuario_tipo = ?';
                $params[] = $item;
            }
            $pc = implode('OR ', $c);
            $filtro .= " AND ({$pc})";
        }
        if (count($colecaoEmpresaId) > 0) {
            $c = array();
            foreach ($colecaoEmpresaId as $itemId) {
                $c[] = '?';
                $params[] = $itemId;
            }
            $filtro .= " AND prontuario.fk_empresa_id IN(" . implode(',', $c) . ")";
        }
        if (count($colecaoContratoId) > 0) {
            $c = array();
            foreach ($colecaoContratoId as $itemId) {
                $c[] = '?';
                $params[] = $itemId;
            }
            $filtro .= ' AND prontuario.fk_contrato_id IN(' . implode(',', $c) . ')';
        }
        $comando = "SELECT *
                    FROM prontuario
                         JOIN pessoa ON pessoa.pessoa_id = prontuario.fk_pessoa_id                          
                         LEFT JOIN alocacao ON alocacao.alocacao_id = prontuario.fk_alocacao_id                            
                    WHERE {$filtro} 
                    ORDER BY {$ordenacao}";
        $resultado = array();
        try {
            $Cnx = self::getDefaultAdapter();
            $resultado = $Cnx->fetchAll($comando, $params);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    /**
     *  Executa um comando SQL resgatando informações da do contrato e empresa do prontuário
     * @param bool $apenasRegistroAtivos [OPICIONAL] Define se os registros deverão ser ativos ou não. Padrão: true.
     * @param array $colecaoEmpresaIds [OPICIONAL] Coleção (array) com ids das empresas requeridas.
     * @param array $colecaoContratoIds [OPICIONAL] Coleção (array) com ids dos contratos requeridos.
     * @param array $colecaoPessoaIds [OPICIONAL] Coleção (array) com ids das pessoas requeridas.
     * @return array Um array vazio quando não tem resultado. Quando tiver resultado retorna um array associativo
     * os com seguinte índices/chaves: <br/>
     * contrato_id, contrato_numero, contrato_responsavel_nome, <br/>
     * contrato_responsavel_telefone, contrato_responsavel_email, empresa_id<br/>
     * empresa_razao, empresa_fantasia,empresa_cnpj<br/>
     * empresa_insc_estadual, empresa_insc_municipal, empresa_identificacao<br/>
     * Como resgatar: $retorno[0]['contrato_numero'],  $retorno[1]['contrato_numero'], ......
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public static function obterColecaoInformacaoEmpresasComProntuario($apenasRegistroAtivos = true, array $colecaoEmpresaIds = array(), array $colecaoContratoIds = array(), array $colecaoPessoaIds = array()) {
        $parametros = array(1);
        $filto = ' 1 = ?';
        if ($apenasRegistroAtivos) {
            $filto .= ' AND p.prontuario_status = ?';
            $parametros[] = 0;
        }
        if (count($colecaoEmpresaIds) > 0) {
            $pc = array();
            foreach ($colecaoEmpresaIds as $param) {
                $pc[] = '?';
                $parametros[] = $param;
            }
            $x = implode(',', $pc);
            $filto .= " AND e.empresa_id IN({$x})";
        }
        if (count($colecaoContratoIds) > 0) {
            $pc = array();
            foreach ($colecaoContratoIds as $param) {
                $pc[] = '?';
                $parametros[] = $param;
            }
            $x = implode(',', $pc);
            $filto .= " AND c.contrato_id IN({$x})";
        }
        if (count($colecaoPessoaIds) > 0) {
            $pc = array();
            foreach ($colecaoPessoaIds as $param) {
                $pc[] = '?';
                $parametros[] = $param;
            }
            $x = implode(',', $pc);
            $filto .= " AND p.fk_pessoa_id IN({$x})";
        }

        $comando = "SELECT
                            c.contrato_id,
                            c.contrato_numero,
                            c.contrato_responsavel_nome,
                            c.contrato_responsavel_telefone,
                            c.contrato_responsavel_email,
                            e.empresa_id,
                            e.empresa_razao,
                            e.empresa_fantasia,
                            e.empresa_cnpj,
                            e.empresa_insc_estadual,
                            e.empresa_insc_municipal,
                            e.empresa_identificacao            
                    FROM prontuario AS p
                         JOIN contrato AS c ON c.contrato_id = p.fk_contrato_id
                         JOIN empresa AS e ON e.empresa_id = p.fk_empresa_id
                    WHERE {$filto}
                    GROUP BY
                            c.contrato_id,
                            c.contrato_numero,
                            c.contrato_responsavel_nome,
                            c.contrato_responsavel_telefone,
                            c.contrato_responsavel_email,
                            e.empresa_id,
                            e.empresa_razao,
                            e.empresa_fantasia,
                            e.empresa_cnpj,
                            e.empresa_insc_estadual,
                            e.empresa_insc_municipal,
                            e.empresa_identificacao ";

        $resutado = array();
        try {
            $Cnx = self::getDefaultAdapter();
            $resutado = $Cnx->fetchAll($comando, $parametros);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resutado;
    }

}
