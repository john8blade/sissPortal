<?php

class Application_Model_Unidade extends Zend_Db_Table {

    protected $_name = 'unidade';
    protected $_primary = 'unidade_id';

    public function obterPeloFiltro($filtro = '1') {
        return $this->getDefaultAdapter()->fetchAll("SELECT * FROM unidade WHERE $filtro AND unidade.unidade_status = 0");
    }

    public function obterTodos() {
        return $this->fetchAll(array($this->_name . "_status = ?" => 0))->toArray();
    }

    public function obterUnidadeUsuario($id) {
        $comando = "SELECT * FROM unidade_usuario
                    WHERE fk_usuario_id = ?";

        $parametro = array($id);
        return $this->getDefaultAdapter()->fetchAll($comando, $parametro);
    }

    public function obterEnderecoUnidade($id) {
        $comando = "select * 
                        from unidade u
                           inner join endereco e
                              on (e.`endereco_id` = u.`fk_endereco_id`)
                        where u.unidade_id = ?";

        $parametro = array($id);
        return $this->getDefaultAdapter()->fetchAll($comando, $parametro);
    }

    public function obterEnderecoUnicoUnidade($id) {
        $comando = "select * 
                        from unidade u
                           inner join endereco e
                              on (e.`endereco_id` = u.`fk_endereco_id`)
                            left join empresa on empresa.fk_unidade_id = ?
                        where u.unidade_id = ?";

        $parametro = array($id, $id);
        $end = $this->getDefaultAdapter()->fetchRow($comando, $parametro);
        $end['tel'] = $this->getDefaultAdapter()->fetchAll("SELECT * FROM telefone WHERE telefone.fk_empresa_id = ?", array($end['empresa_id']));
        return $end;
    }

    public function unidadeUsuario($id) {
        $comando = "SELECT GROUP_CONCAT(fk_unidade_id) AS ids FROM unidade_usuario
        WHERE fk_usuario_id = {$id}";
        $fetch = $this->getDefaultAdapter()->fetchRow($comando);
        return isset($fetch['ids']) ? $fetch['ids'] : '';
    }

    public function obter($id) {
        $fet = $this->fetchRow(array("unidade_id = ?" => (int) $id, "unidade_status = ?" => 0));
        return is_object($fet) ? $fet->toArray() : null;
    }

    public function inserirLigacao(Array $dados) {
        $sql = "INSERT INTO unidade_usuario VALUES (?, ?)";
        $prep = $this->getDefaultAdapter()->prepare($sql);
        return $prep->execute(array($dados['fk_unidade_id'], $dados['fk_usuario_id']));
    }

    public function excluirTodosDoUsuario($usuario_id) {
        $sql = "DELETE from unidade_usuario where fk_usuario_id = ?";
        $prep = $this->getDefaultAdapter()->prepare($sql);
        return $prep->execute(array($usuario_id));
    }

}
