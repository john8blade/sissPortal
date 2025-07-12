<?php

class Application_Model_FichaFispq extends Zend_Db_Table {

    protected $_name = 'ficha_fispq';
    protected $_primary = 'ficha_fispq_id';

    public function exibirPorId($idFuncionario) {
        $sql = "SELECT * FROM `ficha_fispq` f
                WHERE f.`fk_funcionario_id` ={$idFuncionario}";
        return $this->getDefaultAdapter()->fetchRow($sql);
    }

}
