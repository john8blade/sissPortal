<?php

class Application_Model_LiberacaoAtividadeCritica extends Zend_Db_Table {

    protected $_name = 'liberacaoatividadecritica';
    protected $_primary = 'liberacaoatividadecritica_id';

    public function buscarCompletoUsandoClausula($clausula = '1 = 1', $ordenarPor = 'liberacaoatividadecritica.liberacaoatividadecritica_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM {$this->_name}
                                       JOIN atividadecritica ON(atividadecritica.atividadecritica_id = {$this->_name}.fk_atividadecritica_id)
                                       JOIN fichamedica ON(fichamedica.fichamedica_id = {$this->_name}.fk_fichamedica_id)
                             WHERE {$clausula}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscarRacliberacaoAtividadeCritica($result) {
        $comando = "SELECT * 
                        FROM liberacaoatividadecritica lbc
                            INNER JOIN fichamedica fm
                                ON (fm.`fichamedica_id` = lbc.`fk_fichamedica_id`)
                    WHERE lbc.`fk_fichamedica_id` = {$result}
                        AND lbc.`liberacaoatividadecritica_status` = 0";

        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
