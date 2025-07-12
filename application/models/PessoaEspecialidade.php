<?php

class Application_Model_PessoaEspecialidade extends Zend_Db_Table {

    protected $_name = 'pessoa_especialidade';
    protected $_primary = 'pessoa_especialidade_id';

    public function obter($id){
        return $this->getDefaultAdapter()->fetchRow("SELECT * FROM pessoa_especialidade JOIN pessoa ON pessoa.pessoa_id = pessoa_especialidade.fk_pessoa_id WHERE pessoa_especialidade.pessoa_especialidade_id = ? AND pessoa.pessoa_status = 0",array($id));
    }
    
    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'pessoa_especialidade.pessoa_especialidade_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM pessoa_especialidade
                                       JOIN pessoa ON pessoa.`pessoa_id` = pessoa_especialidade.`fk_pessoa_id`
                                       JOIN especialidade ON especialidade.especialidade_id = pessoa_especialidade.fk_especialidade_id
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
