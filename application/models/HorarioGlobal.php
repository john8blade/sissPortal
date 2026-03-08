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

    /**
     * Retorna horários da unidade que valem para o dia da semana informado.
     * Considera horario_global_dia (vagas por dia) e fallback para legado (horario_global_vagas).
     *
     * @param int $unidadeID ID da unidade
     * @param int $diaSemana 1=Segunda … 5=Sexta
     * @return array
     */
    public function obterHorariosDaUnidadePorDiaSemana($unidadeID, $diaSemana)
    {
        $sql = "SELECT hg.horario_global_id AS id,
                       COALESCE(hgd.vagas, hg.horario_global_vagas) AS vagas,
                       hg.horario_global_de AS horario1,
                       hg.horario_global_ate AS horario2
                FROM horario_global hg
                LEFT JOIN horario_global_dia hgd ON hgd.fk_horario_global_id = hg.horario_global_id AND hgd.dia_semana = ?
                WHERE hg.fk_unidade_id = ?
                  AND (hg.horario_global_status = 0 OR hg.horario_global_status IS NULL)
                  AND (hgd.horario_global_dia_id IS NOT NULL
                       OR (SELECT COUNT(*) FROM horario_global_dia hgd2 WHERE hgd2.fk_horario_global_id = hg.horario_global_id) = 0)
                ORDER BY hg.horario_global_de";

        return $this->getDefaultAdapter()->fetchAll($sql, [(int) $diaSemana, (int) $unidadeID]);
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
