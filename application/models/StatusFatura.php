<?php

class Application_Model_StatusFatura extends Zend_Db_Table {

    protected $_name = 'statusfatura';
    protected $_primary = 'statusfatura_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'statusfatura.statusfatura_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM statusfatura                              
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
