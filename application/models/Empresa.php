<?php

class Application_Model_Empresa extends Zend_Db_Table {

    protected $_name = "empresa";
    protected $_primary = "empresa_id";

    public function obterTodos() {
        $unidade = $_SESSION['usuario']['unidadeativa'];
        $sql = "SELECT * FROM empresa inner join unidade on (unidade.unidade_id =  empresa.`fk_unidade_id`) WHERE empresa.empresa_status = 0 and (unidade.unidade_id = {$unidade['unidade_id']}) ORDER BY empresa.empresa_razao ASC";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function empresaFornecedora() {
        $comando = "SELECT *
                     FROM empresa
                        INNER JOIN tabela t
                            ON empresa.`empresa_id` = t.`fk_empresa_id`
                    where empresa.empresa_status = 0";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterEmpresaPaganteDoContrato($contrato) {
        $comando = "SELECT *
                    FROM contrato c
                        INNER JOIN empresaresponsabilidade er ON (c.`contrato_id` = er.`fk_contrato_id`)
                        INNER JOIN empresa e ON (e.`empresa_id` = er.`fk_empresa_faturamento`)
                        WHERE er.`fk_contrato_id` = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterDadosFaturamentoDoContratoEspecifico($contrato) {
        $comando = "SELECT *
                    FROM contrato c
                        INNER JOIN empresaresponsabilidade er
                            ON (c.`contrato_id` = er.`fk_contrato_id`)
                        WHERE er.`fk_contrato_id` = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterDadosEmpresaDoContratoEspecifico($contrato) {
        $comando = "SELECT *
                       FROM empresa e
                        INNER JOIN empresaresponsabilidade er
                            ON er.`fk_empresa_faturamento` = e.`empresa_id`
                        WHERE er.`fk_empresa_faturamento` = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterCobrancaDoContratoEspecifico($contrato) {
        $comando = "SELECT *
                       FROM empresa e
                        INNER JOIN empresaresponsabilidade er
                            ON er.`fk_empresa_cobranca` = e.`empresa_id`
                        WHERE er.`fk_empresa_cobranca` = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterEmpresaPeloContrato($contrato) {
        $comando = "SELECT e.empresa_id,e.empresa_fantasia FROM contratante ct
                        JOIN empresa e ON e.empresa_id = ct.fk_empresa_id AND e.empresa_status = 0
                        JOIN contrato c ON c.contrato_id = ct.fk_contrato_id AND c.contrato_status = 0
                        WHERE ct.fk_contrato_id = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterEnderecoEmpresaDoContratoEspecifico($contrato) {
        $comando = "SELECT *
                       FROM endereco en
                    WHERE en.`endereco_id` = ?";
        return $this->getDefaultAdapter()->fetchAll($comando, array($contrato));
    }

    public function obterDadosEmpresaResponsavelContratoEspecifico($emp) {
        $comando = "SELECT *
                        FROM empresaresponsabilidade er
                            WHERE er.`fk_empresa_faturamento`=?;";
        return $this->getDefaultAdapter()->fetchAll($comando, array($emp));
    }

    public function obterDadosCobrancaEmpresaResponsavelContratoEspecifico($emp) {
        $comando = "SELECT *
                        FROM empresaresponsabilidade er
                            WHERE er.`fk_empresa_cobranca`=?;";
        return $this->getDefaultAdapter()->fetchAll($comando, array($emp));
    }

    public function obter($id) {
        $fet_emp = array();
        if ((int) $id > 0) {
            $sql_emp = "SELECT * FROM empresa WHERE empresa_id = ? AND empresa_status = 0";
            $fet_emp = $this->getDefaultAdapter()->fetchRow($sql_emp, array($id));
            $sql_tel = "SELECT * FROM telefone WHERE fk_empresa_id = ?";
            $fet_tel = $this->getDefaultAdapter()->fetchAll($sql_tel, array($fet_emp['empresa_id']));
            $fet_emp['telefones'] = $fet_tel;
            $sql_end = "SELECT * FROM endereco WHERE endereco_id = ?";
            $fet_end = $this->getDefaultAdapter()->fetchRow($sql_end, array($fet_emp['fk_endereco_id']));
            $fet_emp['endereco'] = $fet_end;
        }
        return $fet_emp;
    }

    public function obterEmpresasFornecedorasComClausula($clausula = "1 = 1", $ordenar = "empresa.empresa_razao ASC", $limite = "0,99999999") {
        $comando = "SELECT  *
                             FROM empresa
                                       JOIN tabela ON(tabela.fk_empresa_id = empresa.empresa_id)
                             WHERE {$clausula}
                             ORDER BY {$ordenar}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscarCompletoUsandoClausula($clausula = '1 = 1', $ordenarPor = 'empresa.empresa_razao', $limite = '0,999999999') {
        $comando = "SELECT *
                             FROM empresa
                                       JOIN endereco ON(endereco.endereco_id = empresa.fk_endereco_id)
                             WHERE {$clausula}
                                    AND empresa.empresa_status = 0
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterPeloFiltro($where = "1 = 1") {
        $sql = "SELECT * FROM empresa  WHERE {$where} AND empresa.empresa_status = 0 ORDER BY empresa.empresa_razao ASC";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function listarContatoHiest($contratoId) {
        $sql = "select * from `contrato` c, `usuario` u, `pessoa` p, `telefone` t
                    where c.`fk_representante_id` = u.`usuario_id`
                    and u.`fk_pessoa_id` = p.`pessoa_id`
                    and p.`pessoa_id` = t.`fk_pessoa_id`
                    AND c.`contrato_id` = {$contratoId} ";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function listarEmpresas() {
        $sql = "select * from empresa
                WHERE empresa_status = 0
                and empresa_tipo = 'CLIENTE'
                ORDER BY empresa.`empresa_fantasia`";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function buscarCompletaUsandoClausula($clausula = null, $ordenarPor = 'empresa.empresa_razao', $limit = '0,999999999') {
        $clausulaComando = '1 = 1';
        #$unidadeId = $_SESSION['usuario']['unidadeativa'];
        #$unidade = $unidadeId['unidade_id'];
        $unidade = $_SESSION['fk_unidade_id'];

        if ($clausula != null) {
            $clausulaComando = $clausula;
        }
        $comando = "SELECT *
                       FROM empresa
                          JOIN endereco
                             ON(endereco.endereco_id = empresa.fk_endereco_id)
                      WHERE {$clausulaComando}
                         AND empresa.empresa_status = 0
                         AND empresa.`fk_unidade_id` = {$unidade}
                         ORDER BY {$ordenarPor}
                         LIMIT {$limit}";
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterEmpresaInadimplente(array $colecaoUnidadeId, array $colecaoEmpresaCnpj = array(), $qtdDiasContabilizarInadimplencia = null) {
        /*
         * Informações Importantes:
         * Codigo de Status da Fatura
         * 1 - PAGO
         * 2 - ATRASADO
         * 3 - NEGOCIAÇÃO
         * 4 - INADIMPLENTE
         * 5 - AGUARDANDO PAGAMENTO
         *
         * Query(Externa)
         * /application/models/sql-query/obter-empresas-inadimplentes.sql
         */
        $localComando = APPLICATION_PATH . '/models/sql-query/';
        // Lendo comando SQL externo.
        $baseComando = file_get_contents($localComando . 'obter-empresas-inadimplentes.sql');
        if (!$baseComando) {
            throw new Exception('Este método usa uma consulta SQL (query) lida de um arquivo externo, porém este arquivo com comando não pode ser lido ou não existe');
        }
        $comando = str_replace('<sqlParamColecaoUnidade/>', implode(',', $colecaoUnidadeId), $baseComando);

        if ($qtdDiasContabilizarInadimplencia == null || !is_numeric($qtdDiasContabilizarInadimplencia)) {
            $ModeloParametro = new Application_Model_Parametro();
            $Rst = $ModeloParametro->fetchRow(array('parametro_nome = ?' => 'Fatura.QtdMaxDiasVencToleranciaPorInadimplencia', 'parametro_status = ?' => 0));
            $qtdDiasContabilizarInadimplencia = ($Rst) ? (int) $Rst->parametro_valor : 30;
        }

        // Filtra empresa
        if (count($colecaoEmpresaCnpj) == 0) {
            $ModeloEmpresa = new Application_Model_Empresa();
            $Rst = $ModeloEmpresa->fetchAll(array('empresa_status = ?' => 0, 'fk_unidade_id IN(?)' => $colecaoUnidadeId));
            if ($Rst->count() > 0) {
                $itens = $Rst->toArray();
                foreach ($itens as $item) {
                    $colecaoEmpresaCnpj[] = "{$item['empresa_cnpj']}";
                }
            }
        }
        $cc = array();
        foreach ($colecaoEmpresaCnpj as $c) {
            $cc[] = "'{$c}'";
        }
        $comando = str_replace('<sqlParamColecaoCnpj/>', implode(',', $cc), $comando);

        // Filtrando datas
        $hoje = date('Y-m-d');
        $dataCorteInadimplente = date('Y-m-d', strtotime("{$hoje} - {$qtdDiasContabilizarInadimplencia} days"));
        $comando = str_replace('<sqlParamDataVencimento/>', "'$dataCorteInadimplente'", $comando);
        $resultado = array();
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $rst = $Cnx->fetchAll($comando);
            $resultado = (is_array($rst) && count($rst) > 0) ? $rst : array();
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    public function obterEmpresa($cols = [], $cond = ['empresa_status = 0']) {
        $parsed = $cols;

        if (count($cols) > 0 && @$cols[0] != '*') {
            $parsed = join(',', array_map(function($val) {
                return "{$this->_name}_$val AS $val";
            }, $cols));
        } else {
            $parsed = '*';
        }

        $cond = join('', array_map(function($val) {
            return " AND {$val} ";
        }, $cond));

        $sql = "SELECT {$parsed} FROM {$this->_name} WHERE 1 = 1 {$cond}";

        $resultado = $this->getAdapter()->fetchAll($sql);

        if (count($resultado) == 1)
            return $resultado[0];
        return $resultado;
    }
}
