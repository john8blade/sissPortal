<?php

class Application_Model_Rascunho extends Zend_Db_Table {

    protected $_name = 'rascunho_ppra';
    protected $_primary = 'rascunho_ppra_id';

    public function buscarPorID($id) {
        $sql = "SELECT * FROM rascunho_ppra WHERE rascunho_ppra.fk_funcionario_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

}
