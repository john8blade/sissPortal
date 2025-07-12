<?php

class Application_Model_DiasSemAtendimento extends Zend_Db_Table {

    protected $_name = 'dias_sem_atendimento';
    protected $_primary = 'dias_sem_atendimento_id';

    public function temAtendimentoNaData($data, $unidade_id) {
        //list($ano, $mes, $dia) = explode('-', $data);
        list($dia, $mes, $ano) = explode('/', $data);
        $sql = "SELECT * FROM dias_sem_atendimento WHERE dias_sem_atendimento_ano = '$ano' AND dias_sem_atendimento_mes = '$mes' AND dias_sem_atendimento_dia = '$dia' AND fk_unidade_id = '$unidade_id'";
        $fet = $this->getDefaultAdapter()->fetchRow($sql);
        if (!$fet) {
            $sql = "SELECT * FROM dias_sem_atendimento WHERE dias_sem_atendimento_mes = '$mes' AND dias_sem_atendimento_dia = '$dia' AND dias_sem_atendimento_motivo = 'NACIONAL' AND fk_unidade_id = '$unidade_id'";
            $fet = $this->getDefaultAdapter()->fetchRow($sql);
            if (!$fet) {
                return true;
            }
        }
        return false;
    }

    /* public function temAtendimentoNaData($data, $unidadeId) {
      $retorno = true;
      $dia = $mes = $ano = '';
      list($dia, $mes, $ano) = explode('/', $data);
      // 1º -  Verifica se exisite algum feriado nacional
      $filtro = array(
      'dias_sem_atendimento_motivo = ?' => 'NACIONAL',
      'dias_sem_atendimento_status = ?' => 0,
      'dias_sem_atendimento_dia = ?' => $dia,
      'dias_sem_atendimento_mes = ?' => $mes
      );
      $resultadoConsulta = $this->fetchAll($filtro)->toArray();
      if (count($resultadoConsulta) > 0) {
      $retorno = false;
      } else {
      // 2º - seleciona os dias que em não a antedimento em uma unidade.
      $filtro = array(
      'dias_sem_atendimento_motivo != ?' => 'NACIONAL',
      'dias_sem_atendimento_data = ?' => "{$ano}-{$mes}-{$dia}",
      'dias_sem_atendimento_status = ?' => 0,
      'fk_unidade_id = ?' => (int) $unidadeId
      );
      $resultadoConsulta = $this->fetchAll($filtro)->toArray();
      if (count($resultadoConsulta) > 0) {
      $retorno = false;
      }
      }
      return $retorno;
      } */

    public function verificarDiaAtendimento() {

        $sql = " Select * from capacidade_atendimento"
                . "where ";

        return $dias;
    }

    public function diasDeAtendimeno($data) {

        $sql = "SELECT * FROM `dias_sem_atendimento` d
                WHERE d.`dias_sem_atendimento_status` = 0
                AND d.`dias_sem_atendimento_data` = STR_TO_DATE('{$data}', '%d/%m/%Y')";

        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function vagasJaCadastradas($data) {

        $sql = " SELECT COUNT(*) FROM agenda a
                    WHERE a.`agenda_data_exame` = STR_TO_DATE('{$data}', '%d/%m/%Y') 
                    AND a.`agenda_status` = 0";

        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function diasDeAtendimenoUnidade($data, $unidade) {

        $sql = "SELECT 
                    * 
                  FROM
                    `dias_sem_atendimento` d, `unidade` u
                   WHERE d.`fk_unidade_id` = u.`unidade_id`
                   AND d.`dias_sem_atendimento_data` = STR_TO_DATE('{$data}', '%d/%m/%Y')
                   AND u.`unidade_id` = {$unidade}
                   AND d.`dias_sem_atendimento_status` = 0";

        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function controleVagas($unidade, $data) {

        $sql = "SELECT * FROM `controleagendamento` c
                WHERE c.`fk_unidade_id` = {$unidade}
                AND c.`controleagendamento_tipo` = 'ESPECIFICO'
                AND c.`controleagendamento_data` = STR_TO_DATE('{$data}', '%d/%m/%Y')";

        return $this->getDefaultAdapter()->fetchRow($sql);
    }

    public function controleVagasPadrao($unidade) {

        $sql = "SELECT * FROM `controleagendamento` c
                WHERE c.`fk_unidade_id` = {$unidade}
                AND c.`controleagendamento_tipo` = 'PADRAO'";

        return $this->getDefaultAdapter()->fetchRow($sql);
    }

}
