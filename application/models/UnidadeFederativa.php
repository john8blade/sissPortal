<?php 
class Application_Model_UnidadeFederativa extends Zend_Db_Table {

    protected $_name = 'unidade_federativa';
    protected $_primary = 'unidade_federativa_id';

    /**
     * 
    **/
    public function obterUnidadeFederativa($cols = ['id', 'sigla'], $where = ['1 = 1']) {
        $cols = join(',', array_map(function($value) { return "{$this->_name}_{$value}"; }, $cols));
        $where = implode(' AND ', $where);

        $sql = "SELECT {$cols} FROM {$this->_name} WHERE 1 = 1 AND {$where}";

        $resultado = $this->getDefaultAdapter()->fetchAll($sql);

        return $resultado;
    }
}