<?php

class Application_Model_Cargo extends Zend_Db_Table {

    protected $_name = 'cargo';
    protected $_primary = 'cargo_id';

    public function obterParte($parte = 0, $quantidade = 100) {
        $inicio = ($parte == 1) ? 100 : ($parte * $quantidade);
        $sql = "SELECT * FROM cargo WHERE cargo.cargo_status = 0 ORDER BY cargo.cargo_nome ASC LIMIT $inicio, $quantidade";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }
    
     public function buscarCompletaUsandoClausula($clausula = null, $ordenarPor = 'cargo_id', $limit = '0,999999999') {
        $clausulaComando = '1 = 1';
        if ($clausula != null) {
            $clausulaComando = $clausula;
        }
        $comando = "SELECT *
                             FROM cargo c
                             WHERE {$clausulaComando}
                             GROUP BY c.cargo_id
                             ORDER BY {$ordenarPor}
                             LIMIT {$limit}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }
    
    public function excluirCargo($cargo_id) {
        $sql = "DELETE from cargo where cargo_id = ?";
        $prep = $this->getDefaultAdapter()->prepare($sql);
        return $prep->execute(array($cargo_id));
    }

    public function obter($id) {
        $fet = $this->fetchRow(array($this->_primary . " = ?" => (int) $id, $this->_name . "_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

}




