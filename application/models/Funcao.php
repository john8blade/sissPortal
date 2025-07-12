<?php

class Application_Model_Funcao extends Zend_Db_Table {

    protected $_name = 'funcao';
    protected $_primary = 'funcao_id';

    public function obterParte($parte = 0, $quantidade = 100) {
        $inicio = ($parte == 1) ? 100 : ($parte * $quantidade);
        $sql = "SELECT * FROM funcao WHERE funcao.funcao_status = 0 ORDER BY funcao.funcao_nome ASC LIMIT $inicio, $quantidade";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

    public function excluirFuncao($cargo_id) {
        $sql = "DELETE from funcao where funcao_id = ?";
        $prep = $this->getDefaultAdapter()->prepare($sql);
        return $prep->execute(array($cargo_id));
    }

    public function obter($id) {
        $fet = $this->fetchRow(array($this->_primary . " = ?" => (int) $id, $this->_name . "_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

}