<?php

class Application_Model_Atestado extends Zend_Db_Table {

    protected $_name = 'atestado';
    protected $_primary = 'atestado_id';

    public function obterPeloId($atestado_id) {
        $sql = "SELECT *,
            DATE_FORMAT(atestado_data_inicial, '%d/%m/%Y') AS atestado_data_inicial,
            DATE_FORMAT(atestado_data_termino, '%d/%m/%Y') AS atestado_data_termino
            FROM {$this->_name}
            JOIN funcionario ON funcionario_id = fk_funcionario_id
            WHERE {$this->_name}_status = 0 AND {$this->_primary} = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($atestado_id));
    }

    public function obterPeloFuncionario($funcionario_id) {
        $sql = "SELECT *,
            DATE_FORMAT(atestado_data_inicial, '%d/%m/%Y') AS atestado_data_inicial,
            DATE_FORMAT(atestado_data_termino, '%d/%m/%Y') AS atestado_data_termino
            FROM {$this->_name}
            JOIN funcionario ON funcionario_id = fk_funcionario_id
            WHERE {$this->_name}_status = 0 AND fk_funcionario_id = ? ORDER BY atestado_data_inicial ASC";
        return $this->getDefaultAdapter()->fetchAll($sql, array($funcionario_id));
    }

}
