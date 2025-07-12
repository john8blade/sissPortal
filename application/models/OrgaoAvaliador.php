<?php

class Application_Model_OrgaoAvaliador extends Zend_Db_Table {

    protected $_name = 'orgao_avaliador';
    protected $_primary = 'orgao_avaliador_id';

    public function buscarUsandoFiltro($clausulaComando = '1 = 1', $ordenarPor = 'orgao_avaliador_id', $limit = '0,999999999', $imprimirComando = false) {
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

    public function obterTodos() {
        $comando = "SELECT *
                    FROM orgao_avaliador";
        $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        return $resultado;
    }

}
