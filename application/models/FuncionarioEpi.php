<?php

class Application_Model_FuncionarioEpi extends Zend_Db_Table {

    protected $_name = 'funcionario_epi';
    protected $_primary = 'funcionario_epi_id';

    public function obterPeloFuncionario($funcionario_id) {
        $name = $this->_name;
        $sql = "SELECT *
            , DATE_FORMAT(funcionario_epi_data_entrega, '%d/%m/%Y') AS funcionario_epi_data_entrega
            , DATE_FORMAT(funcionario_epi_data_vencimento, '%d/%m/%Y') AS funcionario_epi_data_vencimento
            , DATE_FORMAT(funcionario_epi_data_devolucao, '%d/%m/%Y') AS funcionario_epi_data_devolucao
            FROM {$name}
            LEFT JOIN ppra_item_risco_epi ON ppra_item_risco_epi_id = fk_ppra_item_risco_epi_id
            LEFT JOIN epi ON epi_id = fk_epi_id
            JOIN funcionario ON funcionario_id = fk_funcionario_id
            WHERE {$name}_status = 0 AND fk_funcionario_id = ? ORDER BY epi_nome ASC";
        return $this->getDefaultAdapter()->fetchAll($sql, array($funcionario_id));
    }

    public function obterPeloId($funcionario_epi_id) {
        $name = $this->_name;
        $sql = "SELECT *
            , DATE_FORMAT(funcionario_epi_data_entrega, '%d/%m/%Y') AS funcionario_epi_data_entrega
            , DATE_FORMAT(funcionario_epi_data_vencimento, '%d/%m/%Y') AS funcionario_epi_data_vencimento
            , DATE_FORMAT(funcionario_epi_data_devolucao, '%d/%m/%Y') AS funcionario_epi_data_devolucao
            FROM {$name}
            LEFT JOIN ppra_item_risco_epi ON ppra_item_risco_epi_id = fk_ppra_item_risco_epi_id
            LEFT JOIN epi ON epi_id = fk_epi_id
            JOIN funcionario ON funcionario_id = fk_funcionario_id
            WHERE {$name}_status = 0 AND funcionario_epi_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($funcionario_epi_id));
    }

}
