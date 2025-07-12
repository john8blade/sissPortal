<?php

class Application_Model_AnexoProcedimento extends Zend_Db_Table {

    protected $_name = 'anx_proc';
    protected $_primary = 'anx_proc_id';

    const FLAG_TIPO_PROCEDIMENTO_EXAME = 'EXAME';
    const FLAG_TIPO_PROCEDIMENTO_LAUDO = 'LAUDO_EXAME';
    const FLAG_TIPO_PROCEDIMENTO_CERTIFICADO = 'CERTIFICADO';
    const FLAG_TIPO_PROCEDIMENTO_AVALIACAO = 'AVALIACAO';

}
