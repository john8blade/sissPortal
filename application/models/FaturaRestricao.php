<?php

class Application_Model_FaturaRestricao extends Zend_Db_Table {

    protected $_name = 'fatura_restricao';
    protected $_primary = 'fatura_restricao_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'fatura_restricao.fatura_restricao_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM fatura_restricao                              
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}