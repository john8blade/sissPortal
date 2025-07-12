<?php

class Application_Model_ESocialTipoEvento extends Zend_Db_Table {

    protected $_name = 'esocial_tipoevento';
    protected $_primary ='esocial_tipoevento_id';

    public function obter($cols = [], $order, $cond = ['status = 0']) {
        $parsed = $cols;

        if (count($cols) > 0 && @$cols[0] != '*') {
            $parsed = join(',', array_map(function($val) {
                return "{$this->_name}_{$val} AS {$val}";
            }, $cols));
        } else {
            $parsed = '*';
        }

        $cond = join('', array_map(function($val) {
            return " AND {$this->_name}_{$val} ";
        }, $cond));

        $sql = "SELECT {$parsed} FROM {$this->_name} WHERE 1 = 1 {$cond} {$order}";

        $resultado = $this->getAdapter()->fetchAll($sql);

        return $resultado;
    }
}