<?php

class Application_Model_TipoExame extends Zend_Db_Table {

    protected $_name = 'tipoexame';
    protected $_primary = 'tipoexame_id';

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

    public function obter($id) {
        $fet = $this->fetchRow(array($this->_primary . " = ?" => (int) $id, $this->_name . "_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

    public function obterComoObjeto($id) {
        $sql = "SELECT * FROM tipoexame WHERE tipoexame.tipoexame_id = ?";
        return (object) $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

}
