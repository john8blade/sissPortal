<?php

class Application_Model_Setor extends Zend_Db_Table {

    protected $_name = 'setor';
    protected $_primary = 'setor_id';

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

    public function obter($id) {
        $fet = $this->fetchRow(array($this->_primary . " = ?" => (int) $id, $this->_name . "_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

}