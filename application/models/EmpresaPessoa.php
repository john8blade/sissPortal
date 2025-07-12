<?php

class Application_Model_EmpresaPessoa extends Zend_Db_Table {

    protected $_name = 'empresa_pessoa';
    protected $_primary = 'empresa_pessoa_id';

    public function obter($emp) {

        $comando = "SELECT * FROM `empresa_pessoa` e
                    WHERE e.`fk_empresa_id` = ?";

        return $this->getDefaultAdapter()->fetchAll($comando, array($emp));
    }
    
    public function obterPorContrato($contrato) {

        $comando = "SELECT * FROM `empresa_pessoa` e
                    WHERE e.`fk_contrato_id` = ?";

        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

}
