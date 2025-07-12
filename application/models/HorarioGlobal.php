<?php

class Application_Model_HorarioGlobal extends Zend_Db_Table {

    protected $_name = 'horario_global';
    protected $_primary = 'horario_global_id';

    public function obterHorariosDaUnidade($unidade) {
        $sql = "SELECT

                {$this->_name}_id AS id,
                {$this->_name}_vagas AS vagas,
                {$this->_name}_de AS horario1,
                {$this->_name}_ate AS horario2

            FROM {$this->_name}

            WHERE fk_unidade_id = ?";

        return $this->getDefaultAdapter()->fetchAll($sql, [(int) $unidade]);
    }

    public function obter($id) {
        $sql = "SELECT * FROM horario_global WHERE horario_global_id = ?";
        return $this->getDefaultAdapter()->fetchRow($sql, [(int) $id]);
    }

    public function vagasDaUnidade($unidadeID)
    {

        $sql = "SELECT SUM(horario_global_vagas) AS total FROM horario_global WHERE fk_unidade_id = ?";
        $res = $this->getDefaultAdapter()->fetchRow($sql, [(int) $unidadeID]);
        return $res['total'];

    }

    public function obterComoObjeto($id) {
        $sql = "SELECT * FROM {$this->_name} WHERE {$this->_name}_id = ?";
        return (object) $this->getDefaultAdapter()->fetchRow($sql, array($id));
    }

}
