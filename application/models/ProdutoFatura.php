<?php

class Application_Model_ProdutoFatura extends Zend_Db_Table {

    protected $_name = 'produto_fatura';
    protected $_primary = 'produto_fatura_id';

    public function obterProdutosAgrupadosComQuantidade($faturaId) {
        /*
         * Desenvolvido por: Silas Stoffel
         * 
         * ###### Sequência de atividades ######
         * 1º - Resgatar todos os produtos contidos na tabela "produto_fatura"
         * agrupado pelos atributos  "fk_fatura_id, fk_produto_id". O filtro para
         * esta consuta são que atributo "fk_fatura_id" seja igual ao recebido via argumento, 
         * o atributo "produto_fatura_status" seja igual a zero (0).
         */
        $resultado = array();
        if (!is_numeric($faturaId))
            return $resultado;

        $comando = "SELECT *, COUNT(*) AS quantidadeProduto
                             FROM produto_fatura, fatura
                             WHERE fk_fatura_id = ?
                                         AND produto_fatura_status = 0
                                         AND fatura.fatura_id = produto_fatura.fk_fatura_id
                              GROUP BY fk_fatura_id, fk_produto_id";
        $resultadoProdutoFatura = $this->getDefaultAdapter()->fetchAll($comando, array($faturaId));
        // echo "<pre>"; var_dump($resultadoProdutoFatura);exit(00);
        if (is_array($resultadoProdutoFatura) && count($resultadoProdutoFatura) > 0) {
            $proximo = current($resultadoProdutoFatura);
            while ($proximo) {
                $itemProdFat = $proximo;
                $contratoId = (int) $itemProdFat["fk_contrato_id"];
                $empresaId = (int) $itemProdFat["fk_empresa_id"];
                $produtoId = (int) $itemProdFat["fk_produto_id"];

                /* ########## Atenção ##########
                 * A regra de faturamento muda de acordo com o produto que
                 * está sendo faturado.
                 * ## Requisitos
                 * R1 - Quando o produto é do tipo exame deve-se verificar se
                 * a coluna e/ou atributo "fk_produto_agenda_id" é do tipo
                 * inteiro positivo e maior que zero, se sim o preço do produto
                 * está definido na precificação definida/atribuida no momento de realização
                 * do exame ou no agendamento
                 * 
                 * R2 - Quando a coluna e/ou atributo "fk_produto_agenda_id" é 
                 * igual a zero, vazia ou nula o valor do produto deve ser resgado
                 * na tabela "produto_contratado". Se o produto existir mais de
                 * uma vez na tabela o preço será o maior preço definido.
                 */
                $atributoAgendaExameId = (int) $itemProdFat["fk_produto_agenda_id"];
                $resultadoPrecificacao = array();
                if ($atributoAgendaExameId > 0) {
                    $comando = "SELECT *
                                         FROM produto_agenda pa,
                                                   precificacao pre,
                                                   produto pro
                                         WHERE pa.produto_agenda_id = {$itemProdFat['fk_produto_agenda_id']}
                                                     AND pre.precificacao_id = pa.fk_precificacao_id 
                                                     AND pre.fk_produto_id = pa.fk_produto_id
                                                     AND pro.produto_id = pa.fk_produto_id
                                         LIMIT 0,1";
                    $resultadoPrecificacao = $this->getDefaultAdapter()->fetchRow($comando);
                } else {
                    /*
                     * 
                     */
                    $comando = "SELECT pc.*,
                                                      p.*,
                                                      pc.produto_contratado_valor_venda / (
                                                         SELECT COUNT(*) AS resultado
                                                         FROM cobrancaos c
                                                                   JOIN parcelamento p ON (p.fk_cobrancaos_id = c.cobrancaos_id)
                                                         WHERE c.cobrancaos_id = pc.fk_os_id
                                                      )  AS valor_parcela
                                                      
                                         FROM produto_contratado pc
                                                   JOIN produto  p ON p.produto_id = pc.fk_produto_id
                                                   JOIN os ON os.os_id = pc.fk_os_id
                                         WHERE pc.fk_produto_id = (
                                                        SELECT spc.fk_produto_id
                                                        FROM produto_contratado spc
                                                        WHERE spc.fk_produto_id = {$produtoId}
                                                        ORDER BY spc.produto_contratado_valor_venda DESC     
                                                        LIMIT 0,1  
                                                     )
                                                     AND os.fk_contrato_id = {$contratoId}";
                    $resultadoPrecificacao = $this->getDefaultAdapter()->fetchRow($comando);
                }
                $resultado[] = array_merge($itemProdFat, $resultadoPrecificacao);
                // Avança para proximo registro
                next($resultadoProdutoFatura);
                $proximo = current($resultadoProdutoFatura);
            } // fim while
        }
        return $resultado;
    }

}
