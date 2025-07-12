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

    public function vagasDaUnidadeNaDataNoHorario($unidadeID, $data, $horarioID)
    {

        $sql = "SELECT SUM(hd.horario_diario_quantidade) AS total FROM horario_diario hd JOIN horario_global hg ON hg.horario_global_id = hd.fk_horario_global_id WHERE hg.fk_unidade_id = ? AND hd.horario_diario_data = ? AND hd.fk_horario_global_id = ?";
        $res = $this->getDefaultAdapter()->fetchRow($sql, [(int) $unidadeID, $data, $horarioID]);

        if (is_null($res['total'])) {

            $HorarioGlobal = new Application_Model_HorarioGlobal();
            $num = $HorarioGlobal->obter($horarioID)['horario_global_vagas'];

        } else { $num = $res['total']; }

        return $num;

    }

}
