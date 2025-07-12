<?php

class Application_Model_Fatura extends Zend_Db_Table {

    protected $_name = 'fatura';
    protected $_primary = 'fatura_id';

    public function obterProdutosParaFaturar($contrato, $empresa, $data1, $data2) {

        $this->getDefaultAdapter()->query("SET GLOBAL group_concat_max_len = 2048");

        $sql = "SELECT

        COUNT(produto.produto_id) AS quantidade,
        'exame' AS categoria,
        produto.produto_id AS produto_id,
        produto.produto_nome AS produto,
        GROUP_CONCAT(produto_agenda.produto_agenda_id) AS ids,
        ROUND(precificacao.precificacao_valor_venda, 2) AS valor,
        NULL AS efetivo,
        ROUND(SUM(precificacao.precificacao_valor_venda), 2) AS total,
        NULL AS minimo,
        NULL AS valor_da_parcela,
        NULL AS parcela,
        NULL AS parcelas,
        DATE_FORMAT(produto_agenda.produto_agenda_data_programada, '%d/%m/%Y') AS data

        FROM

        produto_agenda

        JOIN agenda ON agenda.agenda_id = produto_agenda.fk_agenda_id AND agenda.agenda_status = 0
        JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id AND produto.produto_status = 0
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id AND categoriadoproduto.categoriadoproduto_status = 0
        JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id AND precificacao.precificacao_status = 0

        WHERE 1

        AND agenda.fk_contrato_id = $contrato
        AND agenda.fk_empresa_id = $empresa
        AND produto_agenda.produto_agenda_data_programada BETWEEN '$data1' AND '$data2'
        AND produto_agenda.produto_agenda_status = 0
        AND NOT EXISTS (SELECT * FROM produto_fatura WHERE produto_fatura.fk_produto_agenda_id = produto_agenda.produto_agenda_id)

        GROUP BY produto.produto_id

        UNION

        SELECT

        1 AS quantidade,
        'parcelamento' AS categoria,
        GROUP_CONCAT(produto.produto_id) AS produto_id,
        GROUP_CONCAT(produto.produto_nome) AS produto,
        parcelamento.parcelamento_id AS ids,
        ROUND(SUM(produto_contratado.produto_contratado_valor_venda), 2) AS valor,
        produto_contratado.produto_contratado_efetivo AS efetivo,
        ROUND(IF(categoriadoproduto.categoriadoproduto_codigo_fixo = '0004', IF(produto_contratado.produto_contratado_efetivo > 0, produto_contratado.produto_contratado_efetivo * SUM(produto_contratado.produto_contratado_valor_venda), SUM(produto_contratado.produto_contratado_valor_venda)), SUM(produto_contratado.produto_contratado_valor_venda)) / (IF(categoriadoproduto.categoriadoproduto_codigo_fixo != '0004', (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id), 1)), 2) AS total,
        ROUND(produto_contratado.produto_contratado_faturamento_minimo, 2) AS minimo,
        NULL AS valor_da_parcela,
        parcelamento.parcelamento_sequencia AS parcela,
        (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id) AS parcelas,
        DATE_FORMAT(parcelamento.parcelamento_data, '%d/%m/%Y') AS data

        FROM

        produto_contratado

        JOIN produto ON produto.produto_id = produto_contratado.fk_produto_id AND produto.produto_status = 0
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id AND categoriadoproduto.categoriadoproduto_status = 0
        JOIN cobrancaos ON cobrancaos.fk_categoriadoproduto_id = categoriadoproduto.categoriadoproduto_id AND cobrancaos.fk_os_id = produto_contratado.fk_os_id AND cobrancaos.cobrancaos_status = 0
        JOIN os ON os.os_id = cobrancaos.fk_os_id AND os.os_status = 0
        JOIN contrato ON contrato.contrato_id = os.fk_contrato_id AND contrato.contrato_status = 0
        JOIN contratante ON contratante.fk_contrato_id = contrato.contrato_id
        JOIN empresa ON empresa.empresa_id = contratante.fk_empresa_id
        JOIN parcelamento ON parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id AND parcelamento.parcelamento_status = 0

        WHERE 1

        AND contrato.contrato_id = $contrato
        AND empresa.empresa_id = $empresa
        AND parcelamento.parcelamento_data BETWEEN '$data1' AND '$data2'
        AND categoriadoproduto.categoriadoproduto_codigo_fixo != '0002'
        AND produto_contratado.produto_contratado_status = 0
        AND NOT EXISTS (SELECT * FROM produto_fatura WHERE produto_fatura.fk_parcelamento_id = parcelamento.parcelamento_id)

        GROUP BY categoriadoproduto.categoriadoproduto_id, parcelamento.parcelamento_id";
        //die($sql);

        $produtos = $this->getDefaultAdapter()->fetchAll($sql);
        return $produtos;
    }

    public function buscarProdutosFaturados($fatura) {

        $adapter = $this->getDefaultAdapter();

        $this->getDefaultAdapter()->query("SET GLOBAL group_concat_max_len = 2048");

        $sql = "SELECT 

        produto.produto_id, 
        COUNT(produto.produto_id) AS quantidade, 
        produto.produto_nome AS produto, 
        ROUND(precificacao.precificacao_valor_venda, 2) AS valor, 
        ROUND(SUM(precificacao.precificacao_valor_venda), 2) AS total, 
        NULL AS parcela, 
        NULL AS parcelas, 
        NULL AS valor_da_parcela 
      
        FROM
        
        fatura 
        JOIN produto_fatura ON produto_fatura.fk_fatura_id = fatura.fatura_id AND produto_fatura.produto_fatura_status = 0 
        JOIN produto_agenda ON produto_agenda.produto_agenda_id = produto_fatura.fk_produto_agenda_id AND produto_agenda.produto_agenda_status = 0 
        JOIN produto ON produto.produto_id = produto_fatura.fk_produto_id AND produto.produto_status = 0 
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id 
        JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id AND precificacao.precificacao_status = 0 
        
        WHERE 1
        
        AND fatura.fatura_id = $fatura
        AND fatura.fatura_status = 0 
        
        GROUP BY produto.produto_id 

        UNION

        SELECT 
        
        produto.produto_id, 
        IF(categoriadoproduto.categoriadoproduto_codigo_fixo = '0004', produto_contratado.produto_contratado_efetivo, 1) AS quantidade, 
        GROUP_CONCAT(produto.produto_nome) AS produto, 
        ROUND(SUM(produto_contratado.produto_contratado_valor_venda), 2) AS valor, 
        ROUND(IF(categoriadoproduto.categoriadoproduto_codigo_fixo = '0004', IF(produto_contratado.produto_contratado_efetivo > 0, produto_contratado.produto_contratado_efetivo * SUM(produto_contratado.produto_contratado_valor_venda), SUM(produto_contratado.produto_contratado_valor_venda)), SUM(produto_contratado.produto_contratado_valor_venda)) / (IF(categoriadoproduto.categoriadoproduto_codigo_fixo != '0004', (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id), 1)), 2) AS total,
        parcelamento.parcelamento_sequencia AS parcela,
        (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id) AS parcelas, 
        ROUND(IF(produto_contratado.produto_contratado_efetivo > 0, (produto_contratado.produto_contratado_valor_venda * produto_contratado.produto_contratado_efetivo), produto_contratado.produto_contratado_valor_venda) / (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id), 2) AS valor_da_parcela 
        
        FROM
        
        fatura 
        
        JOIN produto_fatura ON produto_fatura.fk_fatura_id = fatura.fatura_id AND produto_fatura.produto_fatura_status = 0 
        JOIN produto ON produto.produto_id = produto_fatura.fk_produto_id AND produto.produto_status = 0 
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id 
        JOIN parcelamento ON parcelamento.parcelamento_id = produto_fatura.fk_parcelamento_id AND parcelamento.parcelamento_status = 0 
        JOIN cobrancaos ON cobrancaos.cobrancaos_id = parcelamento.fk_cobrancaos_id AND cobrancaos.cobrancaos_status = 0 
        JOIN produto_contratado ON produto_contratado.fk_os_id = cobrancaos.fk_os_id AND produto_contratado.fk_produto_id = produto_fatura.fk_produto_id AND produto_contratado.produto_contratado_status = 0 
        
        WHERE fatura.fatura_id = $fatura
        AND fatura.fatura_status = 0 
        
        GROUP BY categoriadoproduto.categoriadoproduto_id, parcelamento.parcelamento_id ";
        //die($sql);
        $produtos = $adapter->fetchAll($sql);

        # impostos
        $sql = "SELECT *
        FROM fatura_imposto
        JOIN imposto ON imposto.imposto_id = fatura_imposto.fk_imposto_id
        WHERE fatura_imposto.fk_fatura_id = {$fatura}
        AND fatura_imposto.fatura_imposto_status = 0
        AND imposto.imposto_status = 0";
        $fatura_impostos = $adapter->fetchAll($sql);

        # indexar impostos por id
        $impostos = array();
        foreach ($fatura_impostos as $p) {
            $impostos[$p['imposto_id']] = $p;
        }

        # fatura
        $sql = "SELECT *
        FROM fatura
        WHERE fatura.fatura_id = {$fatura}
        AND fatura.fatura_status = 0";
        $fatura = $adapter->fetchRow($sql);

        # empresa
        $sql = "SELECT *,
        CONCAT('(', telefone.telefone_ddd, ') ', telefone.telefone_numero, ' ', telefone.telefone_ramal) AS telefone
        FROM empresa
        LEFT JOIN telefone ON telefone.fk_empresa_id = empresa.empresa_id
        LEFT JOIN endereco ON endereco.endereco_id = empresa.fk_endereco_id
        WHERE empresa.empresa_id = {$fatura['fk_empresa_id']}
        AND empresa.empresa_status = 0";
        $empresa = $adapter->fetchRow($sql);

        # contrato
        $sql = "SELECT *
        FROM contrato
        WHERE contrato.contrato_id = {$fatura['fk_contrato_id']}
        AND contrato.contrato_status = 0";
        $contrato = $adapter->fetchRow($sql);

        return array('produtos' => $produtos, 'fatura' => $fatura, 'impostos' => $impostos, 'empresa' => $empresa, 'contrato' => $contrato);
    }

    public function buscarProdutosParaFaturarPeloFiltro($where = '1=1') {

        # Obter produtos agendados
        $sql = "SELECT 
        '1/1' AS parcela,
        produto.produto_id,
        produto_agenda.produto_agenda_id, 
        categoriadoproduto.categoriadoproduto_codigo_fixo AS categoria,
        COUNT(produto.produto_id) AS quantidade, produto.produto_nome, 
        ROUND(precificacao.precificacao_valor_venda, 2) AS valor, 
        ROUND(COUNT(produto.produto_id) * precificacao.precificacao_valor_venda, 2) AS total, 
        GROUP_CONCAT(produto_agenda.produto_agenda_id) AS ids,
        DATE_FORMAT(agenda.agenda_data_exame, '%d/%m/%Y') AS data
        FROM agenda
        JOIN empresa ON empresa.empresa_id = agenda.fk_empresa_id
        JOIN contrato ON contrato.contrato_id = agenda.fk_contrato_id
        JOIN produto_agenda ON produto_agenda.fk_agenda_id = agenda.agenda_id
        JOIN produto ON produto.produto_id = produto_agenda.fk_produto_id
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
        JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id
        WHERE {$where}
        AND produto_agenda.produto_agenda_executado = 1
        AND produto_agenda.produto_agenda_status = 0
        AND NOT EXISTS (SELECT * FROM produto_fatura WHERE produto_fatura.fk_produto_agenda_id = produto_agenda.produto_agenda_id)
        GROUP BY produto.produto_id";
        //die($sql);
        $produtos = $this->getDefaultAdapter()->fetchAll($sql);

        return $produtos;
    }

    public function buscaCompletaUsandoClausula($clausulaComando = '1 = 1', $ordenarPor = 'fatura.fatura_id', $limite = '0,99999999999') {
        $resultado = array();
        $comando = "SELECT *
      FROM fatura
      JOIN statusfatura ON statusfatura.statusfatura_id = fatura.fk_statusfatura_id
      JOIN contrato ON contrato.contrato_id = fatura.fk_contrato_id
      JOIN empresa ON empresa.empresa_id = fatura.fk_empresa_id
      WHERE {$clausulaComando}
      ORDER BY {$ordenarPor}
      LIMIT {$limite}";
        $resultadoConsulta = $this->getDefaultAdapter()->fetchAll($comando);
        $resutadoTemporario = array();
        foreach ($resultadoConsulta as $itemConsulta) {
            // Resgata a empresa que fará o pagamento da fatura
            $comando = "SELECT * FROM empresa WHERE empresa_id = ?";
            $empresaQuePaga = $itemConsulta['fk_empresa_pagante_id'];
            $resultadoConsultaEmpresa = $this->getDefaultAdapter()->fetchAll($comando, array($empresaQuePaga));
            $itemConsulta["fatura_pagante"] = (count($resultadoConsultaEmpresa) > 0 && isset($resultadoConsultaEmpresa[0])) ? $resultadoConsultaEmpresa[0] : array();

            $faturaId = $itemConsulta['fatura_id'];

            // Resgata os impostos da fatura
            $itemConsulta["fatura_imposto"] = array();
            $comando = "SELECT *
      FROM fatura_imposto,
      imposto
      WHERE imposto_id = fk_imposto_id
      AND fatura_imposto_status = 0
      AND fk_fatura_id = '{$faturaId}'";
            $resultadoConsultaImposto = $this->getDefaultAdapter()->fetchAll($comando);
            $itemConsulta["fatura_imposto"] = (count($resultadoConsultaImposto) > 0) ? $resultadoConsultaImposto : array();

            // Resgata os produtos da fatura
            $itemConsulta["fatura_produto"] = array();
            $comando = "SELECT *
      FROM produto_fatura
      JOIN produto ON  produto.produto_id = produto_fatura.fk_produto_id
      LEFT JOIN produto_agenda ON  produto_agenda.produto_agenda_id = produto_fatura.fk_produto_agenda_id
      WHERE produto_fatura.fk_fatura_id = '{$faturaId}'
      AND produto_fatura.produto_fatura_status = 0
      ORDER BY produto_fatura.fk_produto_id";
            $resultadoConsultaProduto = $this->getDefaultAdapter()->fetchAll($comando);
            $itemConsulta["fatura_produto"] = (count($resultadoConsultaProduto) > 0) ? $resultadoConsultaProduto : array();
            $resultado[] = $itemConsulta;
        }
        return $resultado;
    }

    public function obterDetalhesDeProdutosFaturados($fatura_id) {

        # FATURA
        $sql = "SELECT *,
        DATE_FORMAT(fatura_data_hora_criacao, '%d/%m/%Y H:i:s') AS fatura_data_hora_criacao,
        DATE_FORMAT(fatura_data_vencimento, '%d/%m/%Y') AS fatura_data_vencimento,
        DATE_FORMAT(fatura_data_inicio_apuracao, '%d/%m/%Y') AS fatura_data_inicio_apuracao,
        DATE_FORMAT(fatura_data_fim_apuracao, '%d/%m/%Y') AS fatura_data_fim_apuracao
        FROM fatura 
        WHERE fatura.fatura_id = ? AND fatura.fatura_status = 0";
        $fatura = $this->getDefaultAdapter()->fetchRow($sql, array($fatura_id));

        # CLIENTE
        $sql = "SELECT *
        FROM empresa
        JOIN endereco ON endereco.endereco_id = empresa.fk_endereco_id
        WHERE empresa.empresa_id = ? AND empresa.empresa_status = 0";
        $cliente = $this->getDefaultAdapter()->fetchRow($sql, array($fatura['fk_empresa_id']));

        # CONTRATO
        $sql = "SELECT *
        FROM contrato
        WHERE contrato.contrato_id = ? AND contrato.contrato_status = 0";
        $contrato = $this->getDefaultAdapter()->fetchRow($sql, array($fatura['fk_contrato_id']));

        # PRODUTOS
        $sql = "SELECT 

        produto.produto_id AS codigo,
        produto.produto_nome AS produto, 
        produto.produto_validade AS validade,
        COUNT(produto.produto_id) AS quantidade,
        ROUND((precificacao.precificacao_valor_venda), 2) AS valor, 
        ROUND((precificacao.precificacao_valor_venda) * COUNT(produto.produto_id), 2) AS total, 
        NULL AS parcelas,
        NULL AS parcela

        FROM

        fatura 

        JOIN produto_fatura ON produto_fatura.fk_fatura_id = fatura.fatura_id AND produto_fatura.produto_fatura_status = 0 
        JOIN produto_agenda ON produto_agenda.produto_agenda_id = produto_fatura.fk_produto_agenda_id AND produto_agenda.produto_agenda_status = 0 
        JOIN agenda ON agenda.agenda_id = produto_agenda.fk_agenda_id
        JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
        JOIN produto ON produto.produto_id = produto_fatura.fk_produto_id AND produto.produto_status = 0
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
        JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id AND precificacao.precificacao_status = 0
        JOIN tabela ON tabela.tabela_id = precificacao.fk_tabela_id
        JOIN empresa ON empresa.empresa_id = tabela.fk_empresa_id

        WHERE 1

        AND fatura.fatura_id = ?
        AND fatura.fatura_status = 0
        
        GROUP BY produto.produto_id

        UNION

        SELECT

        NULL AS codigo,
        GROUP_CONCAT(produto.produto_nome) AS produto,
        NULL AS validade,
        1 AS quantidade,
        ROUND(SUM(produto_contratado.produto_contratado_valor_venda), 2) AS valor,
        ROUND(IF(categoriadoproduto.categoriadoproduto_codigo_fixo = '0004', IF(produto_contratado.produto_contratado_efetivo > 0, produto_contratado.produto_contratado_efetivo * SUM(produto_contratado.produto_contratado_valor_venda), SUM(produto_contratado.produto_contratado_valor_venda)), SUM(produto_contratado.produto_contratado_valor_venda)) / (IF(categoriadoproduto.categoriadoproduto_codigo_fixo != '0004', (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id), 1)), 2) AS total,
        (SELECT COUNT(*) FROM parcelamento WHERE parcelamento.fk_cobrancaos_id = cobrancaos.cobrancaos_id) AS parcelas,
        parcelamento.parcelamento_sequencia AS parcela

        FROM

        fatura

        JOIN produto_fatura ON produto_fatura.fk_fatura_id = fatura.fatura_id AND produto_fatura.produto_fatura_status = 0
        JOIN produto ON produto.produto_id = produto_fatura.fk_produto_id AND produto.produto_status = 0
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
        JOIN parcelamento ON parcelamento.parcelamento_id = produto_fatura.fk_parcelamento_id AND parcelamento.parcelamento_status = 0
        JOIN cobrancaos ON cobrancaos.cobrancaos_id = parcelamento.fk_cobrancaos_id AND cobrancaos.cobrancaos_status = 0
        JOIN produto_contratado ON produto_contratado.fk_os_id = cobrancaos.fk_os_id AND produto_contratado.fk_produto_id = produto_fatura.fk_produto_id AND produto_contratado.produto_contratado_status = 0

        WHERE fatura.fatura_id = ?

        AND fatura.fatura_status = 0

        GROUP BY categoriadoproduto.categoriadoproduto_id, parcelamento.parcelamento_id";
        $lista = $this->getDefaultAdapter()->fetchAll($sql, array($fatura_id, $fatura_id));

        # IMPOSTOS
        $sql = "SELECT * FROM fatura_imposto
        JOIN imposto ON imposto.imposto_id = fatura_imposto.fk_imposto_id AND imposto.imposto_status = 0
        WHERE fatura_imposto.fk_fatura_id = ? AND fatura_imposto.fatura_imposto_status = 0";
        $impostos = $this->getDefaultAdapter()->fetchAll($sql, array($fatura_id));

        # POR FUNCIONÁRIO
        $sql = "SELECT 

        produto.produto_codigo AS codigo,
        pessoa.pessoa_nome AS pessoa,
        COUNT(produto.produto_id) AS quantidade,
        produto.produto_nome AS produto,
        DATE_FORMAT(produto_agenda.produto_agenda_data_programada, '%d/%m/%Y') AS realizado_em,
        produto.produto_validade AS validade,
        ROUND(precificacao.precificacao_valor_venda, 2) AS valor,
        ROUND(SUM(precificacao.precificacao_valor_venda), 2) AS total

        FROM

        fatura 

        JOIN produto_fatura ON produto_fatura.fk_fatura_id = fatura.fatura_id AND produto_fatura.produto_fatura_status = 0 
        JOIN produto_agenda ON produto_agenda.produto_agenda_id = produto_fatura.fk_produto_agenda_id AND produto_agenda.produto_agenda_status = 0 
        JOIN agenda ON agenda.agenda_id = produto_agenda.fk_agenda_id
        JOIN pessoa ON pessoa.pessoa_id = agenda.fk_pessoa_id
        JOIN produto ON produto.produto_id = produto_fatura.fk_produto_id AND produto.produto_status = 0
        JOIN categoriadoproduto ON categoriadoproduto.categoriadoproduto_id = produto.fk_categoriadoproduto_id
        JOIN precificacao ON precificacao.precificacao_id = produto_agenda.fk_precificacao_id AND precificacao.precificacao_status = 0
        JOIN tabela ON tabela.tabela_id = precificacao.fk_tabela_id
        JOIN empresa ON empresa.empresa_id = tabela.fk_empresa_id

        WHERE 1

        AND fatura.fatura_id = ?
        AND fatura.fatura_status = 0

        GROUP BY produto.produto_id, pessoa.pessoa_id

        ORDER BY pessoa.pessoa_nome, produto.produto_nome ASC";
        $porfuncionario = $this->getDefaultAdapter()->fetchAll($sql, array($fatura_id));

        return array('fatura' => $fatura, 'cliente' => $cliente, 'contrato' => $contrato, 'lista' => $lista, 'porfuncionario' => $porfuncionario, 'impostos' => $impostos);
    }

    public function inadimplecia($cnpj) {

        $datavigente = date('Y-m-d');
        
        $sql = "SELECT 
                       f.fatura_id,
                       e.empresa_id,
                       f.fk_contrato_id,
                       f.fatura_data_inicio_apuracao,
                       f.fatura_data_fim_apuracao,
                       f.fatura_data_vencimento,
                       f.fatura_data_pagamento,
                       sf.statusfatura_nome,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia
                FROM empresa AS e
                    JOIN fatura AS f ON(f.fk_empresa_id = e.empresa_id)
                    JOIN statusfatura sf ON sf.statusfatura_id = f.fk_statusfatura_id
                WHERE e.empresa_status = 0
                      AND f.fatura_status = 0
                      AND e.empresa_cnpj IN('{$cnpj}')
                      AND (f.fk_statusfatura_id IN(2,4,8))
                GROUP BY
                       e.empresa_id,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia";
        //die($sql);
        $dados = $this->getDefaultAdapter()->fetchAll($sql);

        return $dados;
    }

}
