<?php

class Application_Model_EncaminhamentoEspecialidade extends Zend_Db_Table {

    protected $_name = 'encaminhamento_especialidade';
    protected $_primary = array('fk_especialidade_id', 'fk_fichamedica_id');

    public function buscarListaEspecialidadeFichaMédica($result) {
        $comando = "SELECT * 
                        FROM encaminhamento_especialidade ee
                            INNER JOIN especialidade e
                                ON e.`especialidade_id` = ee.fk_especialidade_id
                            INNER JOIN fichamedica f
                                ON f.`fichamedica_id` = ee.fk_fichamedica_id
                    WHERE ee.fk_fichamedica_id = {$result}
                        AND f.`fichamedica_status` = 0";

        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'fichamedica.fichamedica_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM encaminhamento_especialidade
                                       JOIN especialidade ON especialidade.especialidade_id = encaminhamento_especialidade.fk_especialidade_id
                                       JOIN fichamedica ON fichamedica.fichamedica_id = encaminhamento_especialidade.fk_fichamedica_id                                       
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
