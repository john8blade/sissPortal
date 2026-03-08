<?php

class Application_Model_HorarioDiario extends Zend_Db_Table
{

    protected $_name = 'horario_diario';
    protected $_primary = 'horario_diario_id';

    public function atualizar($horarios, $data)
    {

        // para cada horario global
        foreach ($horarios as $i => $horario) {

            // verifica se existe um registro para a data no horario
            $existe = $this->fetchRow(["{$this->_name}_data = ?" => $data, "fk_horario_global_id = ?" => $horario['id']]);

            // se existe um registro para a data, atualiza as vagas na lista global
            if ($existe) $horarios[$i]['vagas'] = $existe->toArray()["{$this->_name}_quantidade"];

            // adiciona um indicador que o horario global foi substituido
            if ($existe) $horarios[$i]['editado'] = 1;

        }

        return $horarios;

    }

    public function vagasDaUnidadeNaData($unidadeID, $data)
    {

        $sql = "SELECT SUM(hd.horario_diario_quantidade) AS total FROM horario_diario hd JOIN horario_global hg ON hg.horario_global_id = hd.fk_horario_global_id WHERE hg.fk_unidade_id = ? AND hd.horario_diario_data = ?";
        $res = $this->getDefaultAdapter()->fetchRow($sql, [(int) $unidadeID, $data]);

        if (is_null($res['total'])) {

            $HorarioGlobal = new Application_Model_HorarioGlobal();
            $num = $HorarioGlobal->vagasDaUnidade($unidadeID);

        } else { $num = $res['total']; }

        return $num;

    }

    /**
     * Vagas do horário na data: horario_diario (override) > horario_global_dia (dia da semana) > horario_global (legado).
     */
    public function vagasDaUnidadeNaDataNoHorario($unidadeID, $data, $horarioID)
    {
        $sql = "SELECT SUM(hd.horario_diario_quantidade) AS total FROM horario_diario hd
                JOIN horario_global hg ON hg.horario_global_id = hd.fk_horario_global_id
                WHERE hg.fk_unidade_id = ? AND hd.horario_diario_data = ? AND hd.fk_horario_global_id = ?";
        $res = $this->getDefaultAdapter()->fetchRow($sql, [(int) $unidadeID, $data, $horarioID]);

        if ($res && !is_null($res['total'])) {
            return (int) $res['total'];
        }

        $diaSemana = (int) date('N', strtotime($data));
        if ($diaSemana > 5) {
            return 0;
        }

        $sqlHgd = "SELECT vagas FROM horario_global_dia WHERE fk_horario_global_id = ? AND dia_semana = ?";
        $resHgd = $this->getDefaultAdapter()->fetchRow($sqlHgd, [(int) $horarioID, $diaSemana]);
        if ($resHgd && !is_null($resHgd['vagas'])) {
            return $resHgd['vagas'];
        }

        $sqlLegacy = "SELECT COUNT(*) AS cnt FROM horario_global_dia WHERE fk_horario_global_id = ?";
        $legacy = $this->getDefaultAdapter()->fetchRow($sqlLegacy, [(int) $horarioID]);
        if ($legacy['cnt'] == 0) {
            $hg = (new Application_Model_HorarioGlobal())->obter($horarioID);
            return $hg ? (int) $hg['horario_global_vagas'] : 0;
        }

        return 0;
    }

}
