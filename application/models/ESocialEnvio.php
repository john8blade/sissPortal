<?php

class Application_Model_eSocialEnvio extends Zend_Db_Table {

    protected $_name    = 'esocial_envio';
    protected $_primary = 'esocial_envio_id';

    public function obter($cols = [], $cond = ['esocial_envio_status = 0']) {
        $parsed = $cols;

        if (count($cols) > 0 && @$cols[0] != '*') {
            $parsed = join(',', array_map(function($val) {
                return "{$this->_name}_$val AS $val";
            }, $cols));
        } else {
            $parsed = '*';
        }

        $cond = join('', array_map(function($val) {
            return " AND {$val} ";
        }, $cond));

        $sql = "SELECT {$parsed} FROM {$this->_name} WHERE 1 = 1 {$cond}";

        $resultado = $this->getAdapter()->fetchAll($sql);

        if (count($resultado) == 1)
            return $resultado[0];
        return $resultado;
    }
}