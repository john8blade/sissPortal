<?php

class Application_Model_Contratante extends Zend_Db_Table {

    protected $_name = 'contratante';
    protected $_primary = array('fk_contrato_id', 'fk_empresa_id');

    public function buscarUsandoClausula($clausula = '1 = 1', $ordenarPor = 'contratante.fk_contrato_id', $limite = '0,99999999999', $imprimirComando = false) {
        $comando = "SELECT * 
                    FROM {$this->_name}
                         JOIN contrato ON contrato.contrato_id = contratante.fk_contrato_id
                         JOIN empresa ON empresa.empresa_id = contratante.fk_empresa_id
                    WHERE  {$clausula}
                    ORDER BY {$ordenarPor}
                    LIMIT {$limite} ";
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        } catch (Exception $exc) {
            echo ($imprimirComando) ? $comando : null;
            throw $exc;
        }
        echo ($imprimirComando) ? $comando : null;
        return $resultado;
    }

}
