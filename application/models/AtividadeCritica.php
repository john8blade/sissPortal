<?php

class Application_Model_AtividadeCritica extends Zend_Db_Table {

    protected $_name = 'atividadecritica';
    protected $_primary = 'atividadecritica_id';

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

}
