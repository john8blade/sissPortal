<?php

class Application_Model_ProdutoAgenda extends Zend_Db_Table {

    protected $_name = 'produto_agenda';
    protected $_primary = 'produto_agenda_id';

    public function obterProdutosDoCliente($id) {
        $sql = "SELECT *
              FROM `produto_agenda` pa
              INNER JOIN `produto` p ON p.`produto_id` = pa.`fk_produto_id`
              WHERE pa.`produto_agenda_status` = 0
              AND  pa.`fk_agenda_id` =  {$id}";

        return $this->getDefaultAdapter()->fetchAll($sql);
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'agenda.agenda_id', $limite = '0,99999999999') {
        $comando = "SELECT *
                             FROM produto_agenda
                                       JOIN produto ON produto_id = produto_agenda.fk_produto_id
                                       JOIN agenda ON agenda.agenda_id = produto_agenda.fk_agenda_id
                                       LEFT JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id
                             WHERE {$clausulaComando}
                             ORDER BY {$ordenarPor}
                             LIMIT {$limite}";
        //echo($comando);
        return $this->getDefaultAdapter()->fetchAll($comando);
    }

    /**
     * Seleciona os exames programados para uma agenda com a precificação
     * @param int $paramAgendaId - Id da agenda
     * @param array $colecaoIdsExames [Opcional] - Coleção com ids dos exames. Caso seja informado todos os exames programados são contabilizados.
     * @return array
     * @throws Exception
     */
    public function selecionarColecaoProdutosExamePrecificadosPelaAgenda($paramAgendaId, array $colecaoIdsExames = array()) {
        $resultado = array();
        try {
            if (!is_numeric($paramAgendaId) or (int) $paramAgendaId == 0 or ! is_array($colecaoIdsExames)) {
                throw new Exception('O argumento $paramAgendaId não é está em um formato válido!');
            }
            $ModeloAgenda = new Application_Model_Agenda();
            $ResultadoComando = $ModeloAgenda->fetchRow(array('agenda_id = ?' => $paramAgendaId));
            if (!$ResultadoComando) {
                throw new Exception('Não foi encontrado agenda com o argumento informado!');
            }
            $contratoId = (int) $ResultadoComando->fk_contrato_id;

            $filtroExameId = " AND produto_agenda.fk_produto_id > 0";
            if (count($colecaoIdsExames) > 0) {
                $strIds = implode(',', $colecaoIdsExames);
                $filtroExameId = " AND produto_agenda.fk_produto_id IN({$strIds}) ";
            }

            $comando = "SELECT
                          produto.produto_id,
                          produto.produto_nome,                          
                          (
                            IF(
                                /*Condição     */ (SELECT COUNT(sub_pc.produto_contratado_id) AS total FROM produto_contratado AS sub_pc, os AS sub_os WHERE sub_pc.fk_produto_id = produto.produto_id AND sub_pc.fk_os_id = sub_os.os_id AND sub_pc.produto_contratado_status = 0 AND sub_os.os_id = agenda.fk_os_id ORDER BY sub_os.os_id DESC LIMIT 0,1) > 0,
                                /*Se Verdadeiro*/ (SELECT sub_pc.produto_contratado_valor_venda FROM produto_contratado AS sub_pc, os AS sub_os WHERE sub_pc.fk_produto_id = produto.produto_id AND sub_pc.fk_os_id = sub_os.os_id AND sub_pc.produto_contratado_status = 0 AND sub_os.os_id = agenda.fk_os_id  ORDER BY sub_os.os_id DESC LIMIT 0,1),                                    
                                /*Se Falso     */ precificacao.precificacao_valor_venda
                            )
                           ) AS produto_valor_venda
                           
                        FROM
                            produto_agenda
                            JOIN agenda ON agenda.agenda_id = produto_agenda.fk_agenda_id AND agenda.agenda_status = 0
                            JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id AND produto.produto_status = 0
                            JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id AND categoriadoproduto.categoriadoproduto_status = 0
                            JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id AND precificacao.precificacao_status = 0
                            LEFT JOIN os ON (os.os_status = 0 AND os.os_aprovada = 1 AND os.fk_contrato_id = {$contratoId})
                            LEFT JOIN produto_contratado ON (produto_contratado.fk_produto_id = produto_agenda.fk_produto_id AND produto_contratado.fk_os_id = os.os_id AND produto_contratado.produto_contratado_status = 0)

                        WHERE 1

                            AND produto_agenda.produto_agenda_status = 0
                            AND produto_agenda.produto_agenda_executado = 1
                            AND produto_agenda.fk_agenda_id = {$paramAgendaId}
                            {$filtroExameId}

                        GROUP BY produto.produto_id, produto.produto_nome";

            $Cnx = $this->getDefaultAdapter();
            $colecaoResultados = $Cnx->fetchAll($comando);
            if (is_array($colecaoResultados) && count($colecaoResultados) > 0) {
                $resultado = $colecaoResultados;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultado;
    }

    public function obterListAnos($anovigente) { 

       $sql = "SELECT DISTINCT
                DATE_FORMAT(pa.produto_agenda_data_programada,'%Y') AS 'ano'  
               FROM
                produto_agenda pa
               JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                AND pro.produto_status = 0      
               JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                AND a.agenda_status = 0
                AND a.agenda_presente_clinico = 1
               WHERE pa.produto_agenda_status = 0
                AND pa.produto_agenda_data_programada > '2010'
                AND pa.produto_agenda_data_programada < '{$anovigente}'
               ORDER BY pa.produto_agenda_data_programada DESC;";
         
       $dados = $this->getDefaultAdapter()->fetchAll($sql);

       return $dados;
    }

    public function obterExamesClinicos($ano, $contrato_id) {       

       $sql = "SELECT
                  pro.produto_nome,
                  tp.tipoexame_nome,
                  COUNT(pro.produto_nome) AS qtd_exames,
                  (SELECT COUNT(*) FROM fichamedica f
                      JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                      WHERE f.fk_agenda_id = a.agenda_id
                      AND f.fichamedica_status = 0
                      AND paf.fk_produto_id = pro.produto_id) AS alterado
                FROM
                  produto_agenda pa
                  JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                    AND pro.produto_status = 0      
                  JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                    AND a.agenda_status = 0
                    AND a.agenda_presente_clinico = 1
                  JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                    AND tp.tipoexame_status = 0
                  JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                    AND e.empresa_status = 0
                  JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                    AND c.contrato_status = 0
                  JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                    AND p.pessoa_status = 0
                  JOIN funcionario f ON f.fk_pessoa_id = p.pessoa_id
                  JOIN alocacao al ON al.fk_funcionario_id = f.funcionario_id
                  JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                  LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
                WHERE pa.produto_agenda_status = 0
                AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
                AND pa.produto_agenda_executado = 1
                AND pa.fk_produto_id = 163
                AND a.fk_contrato_id = {$contrato_id}
                GROUP BY tp.tipoexame_nome, pro.produto_nome
                ORDER BY pro.produto_nome ASC ;";
       $dados = $this->getDefaultAdapter()->fetchRow($sql);

       return $dados;
    }

    public function obterTipoExamesComplementares($ano, $contrato_id) {       

       $sql = "SELECT
                  pro.produto_nome,
                  tp.tipoexame_nome,
                  COUNT(pro.produto_nome) AS qtd_exames,
                  (SELECT COUNT(*) FROM fichamedica f
                      JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                      WHERE f.fk_agenda_id = a.agenda_id
                      AND f.fichamedica_status = 0
                      AND paf.fk_produto_id = pro.produto_id) AS alterado
                FROM
                  produto_agenda pa
                  JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                    AND pro.produto_status = 0      
                  JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                    AND a.agenda_status = 0
                    AND a.agenda_presente_clinico = 1
                  JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                    AND tp.tipoexame_status = 0
                  JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                    AND e.empresa_status = 0
                  JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                    AND c.contrato_status = 0
                  JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                    AND p.pessoa_status = 0
                  JOIN funcionario f ON f.fk_pessoa_id = p.pessoa_id
                  JOIN alocacao al ON al.fk_funcionario_id = f.funcionario_id
                  JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                  LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
                WHERE pa.produto_agenda_status = 0
                AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
                AND pa.produto_agenda_executado = 1
                AND pa.fk_produto_id != 163
                AND a.fk_contrato_id = {$contrato_id}
                GROUP BY tp.tipoexame_nome, pro.produto_nome
                ORDER BY pro.produto_nome ASC;";
       $dados = $this->getDefaultAdapter()->fetchAll($sql);

       return $dados;
    }

    public function obterResultadosAnormaisComplementares($ano, $contrato_id) {       

       $sql = "SELECT
                  IF (pp.ppra_item_funcao IS NULL OR pp.ppra_item_funcao = '', cg.cargo_nome , pp.ppra_item_funcao) AS funcao,
                  pro.produto_nome,
                  tp.tipoexame_nome,
                  COUNT(pro.produto_nome) AS qtd_exames,
                  (SELECT COUNT(*) FROM fichamedica f
                      JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                      WHERE f.fk_agenda_id = a.agenda_id
                      AND f.fichamedica_status = 0
                      AND paf.fk_produto_id = pro.produto_id) AS alterado
                FROM
                  produto_agenda pa
                  JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                    AND pro.produto_status = 0      
                  JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                    AND a.agenda_status = 0
                    AND a.agenda_presente_clinico = 1
                  JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                    AND tp.tipoexame_status = 0
                  JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                    AND e.empresa_status = 0
                  JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                    AND c.contrato_status = 0
                  JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                    AND p.pessoa_status = 0
                  JOIN funcionario f ON f.fk_pessoa_id = p.pessoa_id
                  JOIN alocacao al ON al.fk_funcionario_id = f.funcionario_id
                  JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                  LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
                WHERE pa.produto_agenda_status = 0
                AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
                AND pa.produto_agenda_executado = 1
                AND pa.fk_produto_id != 163
                AND a.fk_contrato_id = {$contrato_id}
                GROUP BY funcao, pro.produto_nome, tp.tipoexame_nome
                ORDER BY funcao, pro.produto_nome, tp.tipoexame_nome ASC";
       $dados = $this->getDefaultAdapter()->fetchAll($sql);

       return $dados;
    }

    public function obterExamesClinicosV2($ano, $contrato_id, $empresa_id) {       

      $sql = "SELECT
                 pro.produto_nome,
                 tp.tipoexame_nome,
                 COUNT(pro.produto_nome) AS qtd_exames,
                 (SELECT COUNT(*) FROM fichamedica f
                     JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                     WHERE f.fk_agenda_id = a.agenda_id
                     AND f.fichamedica_status = 0
                     AND paf.fk_produto_id = pro.produto_id) AS alterado
               FROM
                 produto_agenda pa
                 JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                   AND pro.produto_status = 0      
                 JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                   AND a.agenda_status = 0
                   AND a.agenda_presente_clinico = 1
                 JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                   AND tp.tipoexame_status = 0
                 JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                   AND e.empresa_status = 0
                 JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                   AND c.contrato_status = 0
                 JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                   AND p.pessoa_status = 0
                   JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                   JOIN funcionario f ON f.funcionario_id = al.fk_funcionario_id
                 JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                 LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
               WHERE pa.produto_agenda_status = 0
               AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
               AND pa.produto_agenda_executado = 1
               AND pa.fk_produto_id = 163
               AND a.fk_contrato_id = {$contrato_id}
               AND a.fk_empresa_id = {$empresa_id}
               GROUP BY tp.tipoexame_nome, pro.produto_nome
               ORDER BY pro.produto_nome ASC ;";
      $dados = $this->getDefaultAdapter()->fetchAll($sql);
  
      return $dados;
   }

  public function obterTipoExamesComplementaresV2($ano, $contrato_id, $empresa_id) {       

    $sql = "SELECT
               pro.produto_nome,
               tp.tipoexame_nome,
               COUNT(pro.produto_nome) AS qtd_exames,
               (SELECT COUNT(*) FROM fichamedica f
                   JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                   WHERE f.fk_agenda_id = a.agenda_id
                   AND f.fichamedica_status = 0
                   AND paf.fk_produto_id = pro.produto_id) AS alterado
             FROM
               produto_agenda pa
               JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                 AND pro.produto_status = 0      
               JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                 AND a.agenda_status = 0
                 AND a.agenda_presente_clinico = 1
               JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                 AND tp.tipoexame_status = 0
               JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                 AND e.empresa_status = 0
               JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                 AND c.contrato_status = 0
               JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                 AND p.pessoa_status = 0
                 JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                 JOIN funcionario f ON f.funcionario_id = al.fk_funcionario_id
               JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
               LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
             WHERE pa.produto_agenda_status = 0
             AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
             AND pa.produto_agenda_executado = 1
             AND pa.fk_produto_id != 163
             AND a.fk_contrato_id = {$contrato_id}
             AND a.fk_empresa_id = {$empresa_id}
             GROUP BY tp.tipoexame_nome, pro.produto_nome
             ORDER BY pro.produto_nome ASC;";
    $dados = $this->getDefaultAdapter()->fetchAll($sql);

    return $dados;
  }

  public function obterResultadosAnormaisComplementaresV2($ano, $contrato_id, $empresa_id) {       

    $sql = "SELECT
               IF (pp.ppra_item_funcao IS NULL OR pp.ppra_item_funcao = '', cg.cargo_nome , pp.ppra_item_funcao) AS funcao,
               pro.produto_nome,
               tp.tipoexame_nome,
               COUNT(pro.produto_nome) AS qtd_exames,
               (SELECT COUNT(*) FROM fichamedica f
                   JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                   WHERE f.fk_agenda_id = a.agenda_id
                   AND f.fichamedica_status = 0
                   AND paf.fk_produto_id = pro.produto_id) AS alterado
             FROM
               produto_agenda pa
               JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                 AND pro.produto_status = 0      
               JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                 AND a.agenda_status = 0
                 AND a.agenda_presente_clinico = 1
               JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                 AND tp.tipoexame_status = 0
               JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                 AND e.empresa_status = 0
               JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                 AND c.contrato_status = 0
               JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                 AND p.pessoa_status = 0
                 JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                 JOIN funcionario f ON f.funcionario_id = al.fk_funcionario_id
               JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
               LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
             WHERE pa.produto_agenda_status = 0
             AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
             AND pa.produto_agenda_executado = 1
             AND pa.fk_produto_id != 163
             AND a.fk_contrato_id = {$contrato_id}
             AND a.fk_empresa_id = {$empresa_id}
             GROUP BY funcao, pro.produto_nome, tp.tipoexame_nome
             ORDER BY funcao, pro.produto_nome, tp.tipoexame_nome ASC";
    $dados = $this->getDefaultAdapter()->fetchAll($sql);

    return $dados;
  }

  public function obterExames($ano, $contrato_id, $empresa_id) {       
    $sql = "SELECT
                pro.produto_nome,
                tp.tipoexame_nome,
                COUNT(pro.produto_nome) AS qtd_exames,
                (SELECT COUNT(*) FROM fichamedica f
                    JOIN produto_alterado_fichamedica paf ON paf.fk_fichamedica_id = f.fichamedica_id
                    WHERE f.fk_agenda_id = a.agenda_id
                    AND f.fichamedica_status = 0
                    AND paf.fk_produto_id = pro.produto_id) AS alterado
              FROM
                produto_agenda pa
                JOIN produto pro ON pro.produto_id = pa.fk_produto_id
                  AND pro.produto_status = 0      
                JOIN agenda a ON a.agenda_id = pa.fk_agenda_id
                  AND a.agenda_status = 0
                  AND a.agenda_presente_clinico = 1
                JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id
                  AND tp.tipoexame_status = 0
                JOIN empresa e ON e.empresa_id = a.fk_empresa_id
                  AND e.empresa_status = 0
                JOIN contrato c ON c.contrato_id = a.fk_contrato_id
                  AND c.contrato_status = 0
                JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
                  AND p.pessoa_status = 0
                  JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                  JOIN funcionario f ON f.funcionario_id = al.fk_funcionario_id
                JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id  
              WHERE pa.produto_agenda_status = 0
              AND pa.produto_agenda_data_programada LIKE '%{$ano}%'
              AND pa.produto_agenda_executado = 1
              AND pa.fk_produto_id = 163
              AND a.fk_contrato_id = {$contrato_id}
              AND a.fk_empresa_id = {$empresa_id}
              GROUP BY tp.tipoexame_nome, pro.produto_nome
              ORDER BY pro.produto_nome ASC ;";
      $dados = $this->getDefaultAdapter()->fetchAll($sql);
  
      return $dados;
  }

}