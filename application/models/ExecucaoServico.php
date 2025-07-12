<?php

class Application_Model_ExecucaoServico extends Zend_Db_Table {

    protected $_name = "execucao_servico";
    protected $_primary = "execucao_servico_id";

    public function buscarUsandoFiltro($clausulaComando = '1 = 1', $ordenarPor = 'execucao_servico.execucao_servico_id', $limit = '0,9999999999', $imprimirComando = false) {
        $comando = "SELECT *
                    FROM {$this->_name}
                         JOIN produto ON produto.produto_id = execucao_servico.fk_produto_id
                         JOIN contrato ON contrato.contrato_id = execucao_servico.fk_contrato_id
                         JOIN empresa ON empresa.empresa_id = execucao_servico.fk_empresa_id
                    WHERE {$clausulaComando}
                    ORDER BY $ordenarPor
                    LIMIT {$limit}";
        $resultado = array();
        try {
            $resultado = $this->getDefaultAdapter()->fetchAll($comando);
        } catch (Exception $exc) {
            echo ($imprimirComando) ? $comando : null;
            throw $exc;
        }
        echo ($imprimirComando) ? $comando : null;
        return $resultado;
    }

    public static function obterColecaoProdutoParaFaturamento($dataApuracaoInicio, $dataApuracaoTermino, array $colecaoContratoId, array $colecaoEmpresaId) {
        $localComando = APPLICATION_PATH . '/models/sql-query/';
        // Lendo comando SQL externo.
        $baseComando = file_get_contents($localComando . 'obter-colecao-execucao-servico-para-faturar.sql');
        if ($baseComando == false)
            throw new Exception('Não foi possível fazer a leitura do comando SQL externo');
        $parametros = array(
            $dataApuracaoInicio,
            $dataApuracaoTermino,
            implode(',', $colecaoEmpresaId),
            implode(',', $colecaoContratoId)
        );
        $resultados = array();
        try {
            $Cnx = self::getDefaultAdapter();
            // Altera o valor padrão de SET [GLOBAL | SESSION] group_concat_max_len = val;
            // http://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html#function_group-concat
            $Cnx->query('SET SESSION group_concat_max_len = 20480');
            $rst = $Cnx->fetchAll($baseComando, $parametros);
            $resultados = (count($rst) > 0) ? $rst : array();
            // Volta para valor padrão
            $Cnx->query('SET SESSION group_concat_max_len = 1024');
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultados;
    }

    public static function obterColecaoLancamentosDeProdutosFaturados($faturaId) {
        $comando = "SELECT  pf.*,
                            cnt.contrato_id,
                            cnt.contrato_numero,
                            cnt.contrato_sufixo_numero,
                            emp.empresa_id,
                            emp.empresa_razao,
                            pro.produto_id,
                            pro.produto_codigo_fixo,
                            pro.produto_descricao,
                            pro.produto_nome,
                            es.execucao_servico_data_cobranca
                   FROM     produto_fatura AS pf
                            JOIN fatura AS f ON f.fatura_id = pf.fk_fatura_id
                            JOIN execucao_servico AS es ON es.execucao_servico_id = pf.fk_execucao_servico_id
                            JOIN contrato AS cnt ON cnt.contrato_id = es.fk_contrato_id
                            JOIN empresa AS emp ON emp.empresa_id = es.fk_empresa_id
                            JOIN produto AS pro ON pro.produto_id = es.fk_produto_id
                   WHERE    pf.produto_fatura_status = 0
                            AND f.fatura_versao = '2.0'
                            AND f.fatura_id = ?
                    ORDER BY pro.produto_nome ASC";
        $resultado = array();
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $resultado = $Cnx->fetchAll($comando, array($faturaId));
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

}
