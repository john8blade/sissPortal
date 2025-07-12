<?php

class Application_Model_Pessoa extends Zend_Db_Table {

    protected $_name = "pessoa";
    protected $_primary = "pessoa_id";

    public function obterPeloCpf($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        $sql = "SELECT *, DATE_FORMAT(pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento FROM pessoa WHERE pessoa_cpf = ? AND pessoa_status = 0";
        return $this->getDefaultAdapter()->fetchRow($sql, array($cpf));
    }

    public function ajaxObterPeloCpf($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        $sql = "SELECT *, DATE_FORMAT(pessoa_data_nascimento, '%d/%m/%Y') AS pessoa_data_nascimento FROM pessoa  LEFT JOIN usuario ON(usuario.`fk_pessoa_id` = pessoa.`pessoa_id`)"
                . "  INNER JOIN telefone ON(`telefone`.`fk_pessoa_id` = `pessoa`.`pessoa_id`) WHERE pessoa_cpf = ? AND pessoa_status = 0";
        return $this->getDefaultAdapter()->fetchRow($sql, array($cpf));
    }

    public function verificarAlocacaoEmEmpresaPeloCpf($cpf, $empresa_id) {
        $sql = "";
        return $this->getDefaultAdapter()->fetchAll($sql, array($cpf, $empresa_id));
    }

    public function listarMedicoEEspecialidade() {
        $comando = "SELECT * 
                     FROM pessoa p
                        INNER JOIN pessoa_especialidade pe
                            ON (p.`pessoa_id` = pe.`fk_pessoa_id`)
                        INNER JOIN especialidade e
                            ON (e.`especialidade_id`= pe.`fk_especialidade_id`)
                            WHERE e.`especialidade_status` = 0";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function listarPessoa($idPessoa) {
        $comando = "SELECT * 
                        FROM pessoa p  
                    WHERE p.`pessoa_id` = {$idPessoa}
                        AND p.`pessoa_status` = 0";


        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function listarMedico($idMedico) {
        $comando = "SELECT * 
                        FROM pessoa_especialidade pe
                            INNER JOIN especialidade e
                                ON pe.`fk_especialidade_id` = e.`especialidade_id`
                            INNER JOIN pessoa p
                                ON p.`pessoa_id` = pe.`fk_pessoa_id`
                    WHERE pe.`pessoa_especialidade_id` = {$idMedico}
                        AND e.`especialidade_status`=0;";


        return $this->getDefaultAdapter()->fetchAll($comando);
    }

}
