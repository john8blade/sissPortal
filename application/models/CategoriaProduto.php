<?php

class Application_Model_CategoriaProduto extends Zend_Db_Table {

    protected $_name = 'categoriadoproduto';
    protected $_primary = 'categoriadoproduto_id';

    public static function obterColecaoDeCategoriaDeProdutosContratados(array $colecaoIdContratos, array $colecaoIdsOrdemServicos = array(), $contabilizarApenasOrdensAprovadas = true) {
        $colecaoResultados = array();
        $filtro = " ";
        $f = implode(',', $colecaoIdContratos);
        $filtro .= " AND contrato.contrato_id IN ({$f}) ";
        if (count($colecaoIdsOrdemServicos) > 0) {
            $f = implode(',', $colecaoIdsOrdemServicos);
            $filtro .= " AND os.os_id IN ({$f}) ";
        }

        if ($contabilizarApenasOrdensAprovadas) {
            $filtro .= " AND os.os_aprovada = 1";
        }

        $comando = "SELECT 
				categoriadoproduto.categoriadoproduto_id,
				categoriadoproduto.categoriadoproduto_nome,
				categoriadoproduto.categoriadoproduto_descricao,
				categoriadoproduto.categoriadoproduto_valor_min_faturamento,
				categoriadoproduto.categoriadoproduto_status,
				categoriadoproduto.categoriadoproduto_codigo_fixo,
				categoriadoproduto.fk_categoriadoproduto_id
                    FROM os
                         JOIN produto_contratado ON (produto_contratado.fk_os_id = os.os_id AND produto_contratado_status = 0)
                         JOIN produto ON (produto.produto_id = produto_contratado.fk_produto_id)
                         JOIN categoriadoproduto ON (categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id)
                         JOIN contrato ON contrato.contrato_id = os.fk_contrato_id
                         
                    WHERE os.os_status = 0
                          {$filtro}
                    GROUP BY
                            categoriadoproduto.categoriadoproduto_id,
                            categoriadoproduto.categoriadoproduto_nome,
                            categoriadoproduto.categoriadoproduto_descricao,
                            categoriadoproduto.categoriadoproduto_valor_min_faturamento,
                            categoriadoproduto.categoriadoproduto_status,
                            categoriadoproduto.categoriadoproduto_codigo_fixo,
                            categoriadoproduto.fk_categoriadoproduto_id
                    ORDER BY categoriadoproduto.categoriadoproduto_nome ASC";

        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $colecaoResultados = $Cnx->fetchAll($comando);
        } catch (Exception $ex) {
            throw $ex;
        }

        return $colecaoResultados;
    }

}
