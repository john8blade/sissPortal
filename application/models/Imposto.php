<?php

class Application_Model_Imposto extends Zend_Db_Table {

    protected $_name = 'imposto';
    protected $_primary = 'imposto_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'imposto.imposto_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                    FROM {$this->_name}                              
                    WHERE {$clausulaComando}
                    ORDER BY {$ordenarPor}
                    LIMIT {$limite}";
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

}
