<?php 
class Application_Model_EsocialCat extends Zend_Db_Table {

    protected $_name = 'esocial_cat';
    protected $_primary = 'esocial_cat_id';

    public function obterCatContrato($filtro) {
        $sql = "SELECT 
                *
                FROM (
                    SELECT 
                        ct.*, 
                        c.contrato_id, c.contrato_numero, 
                        e.empresa_cnpj, e.empresa_razao, f.funcionario_id, 
                        f.funcionario_matricula, p.pessoa_id, 
                        p.pessoa_nome, p.pessoa_cpf,
                        CASE 
                           WHEN ct.esocial_cat_tpAcid = 1 THEN 'Típica'
                           WHEN ct.esocial_cat_tpAcid = 2 THEN 'Doença'
                           WHEN ct.esocial_cat_tpAcid = 3 THEN 'Trajeto'
                           ELSE ''
                        END AS 'tpAcid',
                        CASE 
                           WHEN ct.esocial_cat_indCatObito = 'S' THEN 'Sim'
                           WHEN ct.esocial_cat_indCatObito = 'N' THEN 'Não'
                           ELSE ''
                        END AS 'indCatObito',
                        (SELECT tc.esocial_envio_tecnospeed_datahora FROM esocial_detalhe_envio es
                          JOIN esocial_envio_tecnospeed tc ON tc.esocial_envio_tecnospeed_id = es.fk_esocial_envio_tecnospeed_id
                        WHERE es.esocial_detalhe_envio_status = 0
                          AND es.fk_esocial_tipoevento_id_id = 3
                          AND es.fk_alocacao_id = ct.fk_alocacao_id LIMIT 1) AS envio_data,
                        (SELECT tc.esocial_envio_tecnospeed_disparo_id FROM esocial_detalhe_envio es
                          JOIN esocial_envio_tecnospeed tc ON tc.esocial_envio_tecnospeed_id = es.fk_esocial_envio_tecnospeed_id
                        WHERE es.esocial_detalhe_envio_status = 0
                          AND es.fk_esocial_tipoevento_id_id = 3
                          AND es.fk_alocacao_id = ct.fk_alocacao_id LIMIT 1) AS envio_reg
                    FROM esocial_cat ct
                    JOIN empresa e ON e.empresa_id = ct.fk_empresa_id
                    JOIN contrato c ON c.contrato_id = ct.fk_contrato_id
                    JOIN alocacao a ON a.alocacao_id = ct.fk_alocacao_id
                    JOIN funcionario f ON f.funcionario_id = a.fk_funcionario_id AND f.funcionario_status = 0
                    JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id
                    ORDER BY ct.esocial_cat_dtAcid DESC, p.pessoa_nome ASC
                ) AS dados
                WHERE esocial_cat_status = 0 
                {$filtro}";
                //util::dump($sql);
        $dados = $this->getDefaultAdapter()->fetchAll($sql);
        return $dados;
    }

    public function obterCatId($id) {
        $sql = "SELECT 
                    ct.*, 
                    c.contrato_id, c.contrato_numero, 
                    e.empresa_cnpj, e.empresa_razao, f.funcionario_id, 
                    f.funcionario_matricula, p.pessoa_id, 
                    p.pessoa_nome, p.pessoa_cpf,
                    (SELECT es.esocial_detalhe_envio_id FROM esocial_detalhe_envio es
                    WHERE es.esocial_detalhe_envio_status = 0
                    AND es.fk_esocial_tipoevento_id_id = 3
                    AND es.fk_alocacao_id = ct.fk_alocacao_id LIMIT 1) AS envio
                FROM esocial_cat ct
                JOIN empresa e ON e.empresa_id = ct.fk_empresa_id
                JOIN contrato c ON c.contrato_id = ct.fk_contrato_id
                JOIN alocacao a ON a.alocacao_id = ct.fk_alocacao_id
                JOIN funcionario f ON f.funcionario_id = a.fk_funcionario_id AND f.funcionario_status = 0
                JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id
                WHERE ct.esocial_cat_status = 0 
                AND ct.esocial_cat_id = {$id}" ;
        $dados = $this->getDefaultAdapter()->fetchRow($sql);
        return $dados;
    }

    public function obterDadosExistentes($contrato_id, $data, $alocacao_id) {
        $sql = "SELECT 
                    ct.*, 
                    c.contrato_id, c.contrato_numero, 
                    e.empresa_cnpj, e.empresa_razao, f.funcionario_id, 
                    f.funcionario_matricula, p.pessoa_id, 
                    p.pessoa_nome, p.pessoa_cpf 
                FROM esocial_cat ct
                JOIN empresa e ON e.empresa_id = ct.fk_empresa_id
                JOIN contrato c ON c.contrato_id = ct.fk_contrato_id
                JOIN alocacao a ON a.alocacao_id = ct.fk_alocacao_id
                JOIN funcionario f ON f.funcionario_id = a.fk_funcionario_id AND f.funcionario_status = 0
                JOIN pessoa p ON p.pessoa_id = f.fk_pessoa_id
                WHERE ct.esocial_cat_status = 0 
                AND c.contrato_id = {$contrato_id}
                AND ct.esocial_cat_dtAcid LIKE '%{$data}%' 
                AND a.alocacao_id = {$alocacao_id}";                
        $dados = $this->getDefaultAdapter()->fetchRow($sql);
        return $dados;
    }

    public function obterCatDoencas($ano, $contrato_id) {
        $sql = "SELECT 
                    IF (p.ppra_item_funcao IS NULL OR p.ppra_item_funcao = '', cg.cargo_nome , p.ppra_item_funcao) AS funcao,
                    COUNT(c.esocial_cat_id) AS qts_cat 
                FROM esocial_cat c
                    JOIN alocacao a ON a.alocacao_id = c.fk_alocacao_id
                    JOIN cargo cg ON cg.cargo_id = a.fk_cargo_id
                    LEFT JOIN ppra_item p ON p.ppra_item_id = a.fk_ppra_item_id
                WHERE c.esocial_cat_status = 0
                    AND c.esocial_cat_tpAcid = 2
                    AND c.esocial_cat_dtAcid LIKE '%$ano%'
                    AND c.fk_contrato_id = {$contrato_id}
                ORDER BY funcao ASC;" ;
        $dados = $this->getDefaultAdapter()->fetchAll($sql);

        return $dados;
    }

    public function obterCatTipo($ano, $contrato_id) {
        $sql = "SELECT 
                    IF (p.ppra_item_funcao IS NULL OR p.ppra_item_funcao = '', cg.cargo_nome , p.ppra_item_funcao) AS funcao,
                    CASE 
                        WHEN c.esocial_cat_tpAcid = 1 THEN 'Típica'
                        WHEN c.esocial_cat_tpAcid = 2 THEN 'Doença'
                        WHEN c.esocial_cat_tpAcid = 3 THEN 'Trajeto'
                        ELSE ''
                    END AS 'tpAcid',
                    COUNT(c.esocial_cat_id) AS qts_cat 
                FROM esocial_cat c
                    JOIN alocacao a ON a.alocacao_id = c.fk_alocacao_id
                    JOIN cargo cg ON cg.cargo_id = a.fk_cargo_id
                    LEFT JOIN ppra_item p ON p.ppra_item_id = a.fk_ppra_item_id
                WHERE c.esocial_cat_status = 0
                    AND c.esocial_cat_dtAcid LIKE '%$ano%'
                    AND c.fk_contrato_id = {$contrato_id}
                GROUP BY funcao, tpAcid
                ORDER BY funcao, tpAcid ASC;" ;
        $dados = $this->getDefaultAdapter()->fetchAll($sql);

        return $dados;
    }
    
}