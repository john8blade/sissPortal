<?php

class Application_Model_ContextoCadastroEmpresa extends Zend_Db_Table {

    protected $_name = 'contexto_cadastro_empresa';
    protected $_primary = 'contexto_cadastro_empresa_id';

    public function buscarUsandoFiltro($clausulaComando = '1 = 1', $ordenarPor = 'contexto_cadastro_empresa.contexto_cadastro_empresa_id', $limit = '0,999999999', $imprimirComando = false) {
        $comando = "SELECT *
                    FROM {$this->_name}
                    WHERE {$clausulaComando}
                    ORDER BY $ordenarPor
                    LIMIT {$limit}";
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
