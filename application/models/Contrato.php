<?php

class Application_Model_Contrato extends Zend_Db_Table {

    protected $_name = 'contrato';
    protected $_primary = 'contrato_id';

    public function obterTodos() {
        return $this->getDefaultAdapter()->fetchAll("SELECT * FROM contrato WHERE contrato_status = 0");
    }

    public function obter($id) {
        return $this->getDefaultAdapter()->fetchRow("SELECT * FROM contrato WHERE contrato_status = 0 AND contrato_id = ?", array($id));
    }

    public function obterContratoCompletoComEmpresa($contrato_id, $empresa_id) {
        $sql = "SELECT 
            * 
        FROM
            contrato
            JOIN empresa ON empresa.empresa_id = ? AND empresa.empresa_status = 0
            JOIN vigencia ON vigencia.fk_contrato_id = contrato.contrato_id 
            LEFT JOIN pcmso ON pcmso.fk_contrato_id = contrato.contrato_id AND pcmso.fk_empresa_id = empresa.empresa_id AND pcmso.pcmso_status = 0 
            LEFT JOIN coordenacao ON coordenacao.coordenacao_id = pcmso.fk_coordenacao_id AND coordenacao.coordenacao_status = 0 
        WHERE 1 
            AND contrato.contrato_id = ?
            AND contrato.contrato_status = 0  
            AND vigencia.vigencia_status = 0";
        return $this->getDefaultAdapter()->fetchRow($sql, array((int) $empresa_id, (int) $contrato_id));
    }

    public function obterPelaEmpresa($empresa_id) {
        $sql = "SELECT * FROM contratante 
                JOIN contrato ON contrato.contrato_id = contratante.fk_contrato_id AND contrato.contrato_status = 0
                WHERE contratante.fk_empresa_id = ? 
                AND contrato.fk_unidade_id = {$_SESSION['usuario']['unidadeativa']['unidade_id']}";
        return $this->getDefaultAdapter()->fetchAll($sql, array($empresa_id));
    }

    public function obterProdutoContratadoNaSuaCategoria($contrato_id) {
        $sql = "SELECT * 
                    FROM produto_contratado pc
                        INNER JOIN produto p
                            ON p.`produto_id` = pc.`fk_produto_id`
                        INNER JOIN os 
                            ON os.`os_id` = pc.`fk_os_id`
                        INNER JOIN contrato c
                            ON c.`contrato_id` = os.`fk_contrato_id`
                        INNER JOIN categoriadoproduto cp
                            ON cp.`categoriadoproduto_id` = p.`fk_categoriadoproduto_id`
                WHERE c.`contrato_id` = {$contrato_id}
                    AND pc.`produto_contratado_status` = 0
                    AND p.`produto_status` = 0
                    AND os.`os_status` = 0
                    AND c.`contrato_status` = 0
                GROUP BY cp.`categoriadoproduto_id`
                ORDER BY cp.`categoriadoproduto_id`";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function obterPeloProduto($produto_id) {
        $unidade = $_SESSION['usuario']['unidadeativa'];

        $sql = "SELECT *
                FROM precificacao p, produto pd, empresa e, tabela t
                WHERE p.`fk_produto_id` = pd.`produto_id`
                AND p.`fk_tabela_id` = t.`tabela_id`
                AND t.`fk_empresa_id` = e.`empresa_id`
                AND pd.`produto_id` =  {$produto_id}
                AND p.`precificacao_status` = 0
                AND pd.`produto_status` = 0
                AND e.`empresa_status` = 0
                AND t.`tabela_status` = 0
                AND e.`fk_unidade_id`= {$unidade['unidade_id']}";
        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'contrato.contrato_numero', $limit = '0,999999999') {

        $comando = "SELECT *,contratante.fk_contrato_id
                             FROM contrato
                                    LEFT JOIN os ON(os.`fk_contrato_id` = contrato.`contrato_id`)
                                    LEFT JOIN contratante ON(contratante.fk_contrato_id = contrato.contrato_id)
                                    LEFT JOIN empresa ON(contratante.fk_empresa_id = empresa.empresa_id)
                                    LEFT JOIN unidade ON(unidade.unidade_id = contrato.fk_unidade_id)
                                    LEFT JOIN endereco ON(endereco.endereco_id = empresa.fk_endereco_id)
                             WHERE {$clausulaComando}  
                                    AND contrato.fk_unidade_id = {$_SESSION['usuario']['unidadeativa']['unidade_id']}
                                    AND (empresa.empresa_status = 0 
                                    OR empresa.`empresa_status` IS NULL)
                                    AND unidade.unidade_status = 0
                                    -- and contratante.`contratante_empresa_principal` = 1
                                    AND (os.os_status = 0 
                                    OR os.`os_status` is null)
                                    GROUP BY `contrato`.`contrato_id`
                             ORDER BY $ordenarPor
                             LIMIT {$limit}";
        //die($comando);
        //echo '<pre style="text-transform:none">', $comando, '</pre>';
        //exit(0);
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscaCompletaUsandoClausulaParaContratante($clausulaComando = '1 = 1', $ordenarPor = 'contrato.contrato_numero', $limit = '0,999999999') {
        $comando = "SELECT *
                             FROM contrato
                                    LEFT JOIN os ON(os.`fk_contrato_id` = contrato.`contrato_id`)
                                    LEFT JOIN contratante ON(contratante.fk_contrato_id = contrato.contrato_id)
                                    LEFT JOIN empresa ON(contratante.fk_empresa_id = empresa.empresa_id)
                                    LEFT JOIN unidade ON(unidade.unidade_id = contrato.fk_unidade_id)
                                    LEFT JOIN endereco ON(endereco.endereco_id = empresa.fk_endereco_id)
                             WHERE {$clausulaComando}  
                                    AND contrato.fk_unidade_id = {$_SESSION['usuario']['unidadeativa']['unidade_id']}
                                    AND (empresa.empresa_status = 0 
                                    OR empresa.`empresa_status` IS NULL)
                                    AND unidade.unidade_status = 0
                                    AND (os.os_status = 0 
                                    OR os.`os_status` is null)
                             ORDER BY $ordenarPor
                             LIMIT {$limit}";
        //die($comando);
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function buscaCompletaUsandoClausulaParaContratanteContrato($clausulaComando = '1 = 1', $ordenarPor = 'contrato.contrato_numero', $limit = '0,999999999') {

        $comando = "SELECT *
                             FROM contrato
                                    LEFT JOIN contratante ON(contratante.fk_contrato_id = contrato.contrato_id)
                                    LEFT JOIN empresa ON(contratante.fk_empresa_id = empresa.empresa_id)
                                    LEFT JOIN unidade ON(unidade.unidade_id = contrato.fk_unidade_id)
                                    LEFT JOIN endereco ON(endereco.endereco_id = empresa.fk_endereco_id)
                             WHERE {$clausulaComando}  
                                    AND contrato.fk_unidade_id = {$_SESSION['usuario']['unidadeativa']['unidade_id']}
                                    AND (empresa.empresa_status = 0 
                                    OR empresa.`empresa_status` IS NULL)
                                    AND unidade.unidade_status = 0
                             ORDER BY $ordenarPor
                             LIMIT {$limit}";
        //echo '<pre style="text-transform:none">', $comando, '</pre>';
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    public function obterPelaId($id) {
        $sql = "SELECT * FROM `contrato` c
                WHERE c.`contrato_id` = {$id}";
        return $this->getDefaultAdapter()->fetchAll($sql, array($id));
    }

    /**
     * Obtem colecão de resultado do contrato previsto para faturamento. Importante: Este método retorna dados dos contratos
     * previstos para faturamento, isto não quer dizer que realmente seja o valor do faturmento. No faturamento há valores de impostos, acréscimos, descontos e
     * possibilidade faturar ou não faturar.
     * @param int $unidadeId Id da unidade.
     * @param string $dataInicio Data de início em formato americano. Exemplo: 1989-06-06
     * @param string $dataTermino Data de termino em formato americano. Exemplo: 1989-07-05
     * @return array
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public static function obterDadosContratoPrevistoParaFaturamento($unidadeId, $dataInicio, $dataTermino) {
        $resultado = array();
        $localComando = APPLICATION_PATH . '/models/sql-query/';
        // Lendo comando SQL externo.
        $baseComando = file_get_contents($localComando . 'obter-contrato-empresa-prevista-para-faturamento.sql');
        if ($baseComando == false)
            throw new Exception('Não foi possível fazer a leitura do comando SQL externo');
        $parametros = array(
            $dataInicio,
            $dataTermino,
            $unidadeId
        );
        $resultados = array();
        try {
            $Cnx = self::getDefaultAdapter();
            $c = "
            SET @paramPeriodoInicio := '{$dataInicio}';
            SET @paramPeriodoTermino := '{$dataTermino}';
            SET @paramUnidadeId := {$unidadeId}; ";
            $Cnx->query($c);
            $rst = $Cnx->fetchAll($baseComando, $parametros);
            $resultados = (count($rst) > 0) ? $rst : array();
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultados;
    }

    public function obterMedicoContrato($contrato_id) {       

       $sql = "SELECT
                   co.coordenacao_id,
                   co.coordenacao_medico,
                   co.coordenacao_cpf,
                   co.coordenacao_crm
               FROM
                   contrato c
                   JOIN vigencia v ON v.fk_contrato_id = c.contrato_id
                    AND v.vigencia_status = 0
                   LEFT JOIN pcmso p ON p.fk_contrato_id = c.contrato_id 
                    AND p.pcmso_status = 0
                   LEFT JOIN coordenacao co ON co.coordenacao_id = p.fk_coordenacao_id 
                    AND co.coordenacao_status = 0
               WHERE c.contrato_status = 0
                   AND c.contrato_id = {$contrato_id}";
       $dados = $this->getDefaultAdapter()->fetchRow($sql);

       return $dados;
    }

}
