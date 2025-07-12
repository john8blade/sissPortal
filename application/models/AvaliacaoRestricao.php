<?php

class Application_Model_AvaliacaoRestricao extends Zend_Db_Table {

    protected $_name = 'avaliacao_restricao';
    protected $_primary = 'avaliacao_restricao_id';

    public function buscarUsandoFiltro($clausulaComando = '1 = 1', $ordenarPor = 'orgao_avaliador_id', $limit = '0,999999999', $imprimirComando = false) {
        $comando = "SELECT *
                    FROM {$this->_name}
                         JOIN orgao_avaliador ON orgao_avaliador.orgao_avaliador_id = avaliacao_restricao.fk_orgao_avaliador_id
                         JOIN empresa ON empresa.empresa_id = avaliacao_restricao.fk_empresa_id
                         JOIN usuario ON usuario.usuario_id = avaliacao_restricao.fk_usuario_id
                         JOIN pessoa ON pessoa.pessoa_id = usuario.fk_pessoa_id
                    WHERE {$clausulaComando}
                    ORDER BY $ordenarPor
                    LIMIT {$limit}";
        //echo $comando; exit(0) ;
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        } catch (Exception $exc) {
            echo ($imprimirComando) ? $comando : null;
            throw $exc;
        }
        echo ($imprimirComando) ? $comando : null;
        return $resultado;
    }

    /**
     * Lista o último resultado da avaliação de crédito. O resultado
     * é agrupado por orgão avaliadores.
     * 
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     * @param int $empresa_id - Id da empresa
     * @return array, onde cada posição do é indexada da seguinte maneira: <br/> array(
     *  'orgao_avaliador_id' = '',
     *  'orgao_avaliador_nome' => '',  
     *  'avaliacao_restricao_resultado' => '',
     *  'avaliacao_restricao_data_consulta' => '',
     *  'avaliacao_restricao_id' => '',
     * );
     * @throws Exception
     */
    public function obterUltimaAvalicaoCreditoAgrupadaPorOrgaoAvaliador($empresa_id) {
        $comando = "
                    SELECT 
                            OrgaoAvaliador.fk_orgao_avaliador_id as orgao_avaliador_id,
                            (SELECT oa1.orgao_avaliador_nome FROM orgao_avaliador oa1 WHERE oa1.orgao_avaliador_id = OrgaoAvaliador.fk_orgao_avaliador_id LIMIT 1) as orgao_avaliador_nome,
                            (SELECT ar1.avaliacao_restricao_data_consulta FROM avaliacao_restricao ar1 WHERE ar1.fk_empresa_id = ? and ar1.avaliacao_restricao_status = 0 and ar1.fk_orgao_avaliador_id = OrgaoAvaliador.fk_orgao_avaliador_id order by ar1.avaliacao_restricao_data_consulta desc LIMIT 1) AS avaliacao_restricao_data_consulta,
                            (SELECT ar2.avaliacao_restricao_resultado FROM avaliacao_restricao ar2 WHERE ar2.fk_empresa_id = ? and ar2.avaliacao_restricao_status = 0 and ar2.fk_orgao_avaliador_id = OrgaoAvaliador.fk_orgao_avaliador_id order by ar2.avaliacao_restricao_data_consulta desc LIMIT 1) AS avaliacao_restricao_resultado,
                            (SELECT ar3.avaliacao_restricao_id FROM avaliacao_restricao ar3 WHERE ar3.fk_empresa_id = ? and ar3.avaliacao_restricao_status = 0 and ar3.fk_orgao_avaliador_id = OrgaoAvaliador.fk_orgao_avaliador_id order by ar3.avaliacao_restricao_data_consulta desc LIMIT 1) AS avaliacao_restricao_id
                    FROM (
                            select distinct fk_orgao_avaliador_id
                            from avaliacao_restricao
                            where fk_empresa_id = ?
                                  and avaliacao_restricao_status = 0
                         )  OrgaoAvaliador
                    ";
        $parametros = array(
            $empresa_id,
            $empresa_id,
            $empresa_id,
            $empresa_id
        );
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando, $parametros);
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

}
