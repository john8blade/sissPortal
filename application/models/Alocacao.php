<?php

class Application_Model_Alocacao extends Zend_Db_Table {

    protected $_name = "alocacao";
    protected $_primary = "alocacao_id";

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

    public function obter($id) {
        $fet = $this->fetchRow(array($this->_primary . " = ?" => (int) $id, $this->_name . "_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'alocacao.alocacao_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM alocacao
                                       JOIN funcionario ON funcionario.`funcionario_id` = alocacao.`fk_funcionario_id`
                                       JOIN funcao ON funcao.`funcao_id` = alocacao.`fk_funcao_id`
                                       JOIN cargo ON cargo.`cargo_id` = alocacao.`fk_cargo_id`
                                       JOIN setor ON setor.`setor_id` = alocacao.`fk_setor_id`
                                       LEFT JOIN ghe  ON ghe.ghe_id = alocacao.`fk_ghe_id`
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function dadosAlocacao($contrato_id) {
        $comando = "SELECT a.fk_ppra_item_id FROM funcionario f
                    JOIN alocacao a ON a.fk_funcionario_id = f.funcionario_id
                    WHERE f.funcionario_status = 0
                    AND (a.fk_ppra_item_id = 0 
                    OR a.fk_ppra_item_id IS NULL) 
                    AND (f.funcionario_motivo_inativacao = ''
                    OR f.funcionario_motivo_inativacao is NULL)
                    AND f.fk_contrato_id = {$contrato_id}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
