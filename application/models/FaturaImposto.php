<?php

class Application_Model_FaturaImposto extends Zend_Db_Table {

    protected $_name = 'fatura_imposto';
    protected $_primary = 'fatura_imposto_id';

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'fatura_imposto.fatura_imposto_id', $limite = '0,99999999999') {
        $resultado = array();
        $comando = "SELECT *
                    FROM imposto
                         JOIN fatura_imposto ON fatura_imposto.fk_imposto_id = imposto.imposto_id
                    WHERE {$clausulaComando}
                    ORDER BY {$ordenarPor}
                    LIMIT {$limite}";
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        return $resultado;
    }

}
