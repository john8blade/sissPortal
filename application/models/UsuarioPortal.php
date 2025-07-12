<?php

class Application_Model_UsuarioPortal extends Zend_Db_Table {

    protected $_name = 'usuario_portal';
    protected $_primary = 'usuario_portal_id';

    public function selecionarComRegraLogin($usuario, $senhaPadrao, $senhaAlternativa = null) {
        $resultado = array();
        $comando = "SELECT *,up.fk_contrato_id
                             FROM  usuario_portal up
                                        JOIN contrato c ON c.contrato_id = up.fk_contrato_id
                             WHERE up.usuario_portal_login = {$usuario}
                                         AND (
                                                    up.usuario_portal_senha_padrao = '{$senhaPadrao}'
                                                    OR usuario_portal_senha_alternativa LIKE '%{$senhaAlternativa}%'
                                         )
                                         AND c.contrato_status = 0
                                         AND up.usuario_portal_status = 0";
        $resultadoComandoLogin = $this->getDefaultAdapter()->fetchRow($comando);
        #util::dump($comando);
        if (is_array($resultadoComandoLogin) && count($resultadoComandoLogin) > 0) {
            $resultado = $resultadoComandoLogin;
            $contratoId = $resultado["contrato_id"];

            $comando = "SELECT *
                                 FROM contratante c
                                          JOIN empresa e ON e.empresa_id = c.fk_empresa_id
                                          JOIN endereco ed ON ed.endereco_id = e.fk_endereco_id
                                 WHERE e.empresa_status = 0
                                             AND c.contratante_empresa_principal = 1
                                             AND c.fk_contrato_id = {$contratoId}
                                 ORDER BY c.contratante_empresa_principal";
            $resultadoComandoEmpresa = $this->getDefaultAdapter()->fetchRow($comando);
            if (is_array($resultadoComandoEmpresa) && count($resultadoComandoEmpresa) > 0) {
                $resultado['empresa'] = array();
                $resultado['empresa'] = $resultadoComandoEmpresa;
            }
            # VERIFICANDO SE O CONTRATO TEM PENDENCIAS NAS FATURAS 
            # IN (2-ATRASADO, 4-INADIMPLENTE)
            $cnpj = $resultado['empresa']['empresa_cnpj'];
            $datavigente = date('Y-m-d');

            $script = "SELECT 
                       f.fatura_id,
                       e.empresa_id,
                       f.fk_contrato_id,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia
                FROM empresa AS e
                    JOIN fatura AS f ON(f.fk_empresa_id = e.empresa_id)
                WHERE e.empresa_status = 0
                      AND f.fatura_status = 0
                      AND e.empresa_cnpj IN('{$cnpj}')
                      AND (f.fk_statusfatura_id IN(2,4,8))
                GROUP BY
                       e.empresa_id,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia 
                UNION
                SELECT 
                       f.fatura_id,
                       e.empresa_id,
                       f.fk_contrato_id,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia
                FROM empresa AS e
                    JOIN fatura AS f ON(f.fk_empresa_id = e.empresa_id)
                WHERE e.empresa_status = 0
                      AND f.fatura_status = 0
                      AND e.empresa_cnpj IN('{$cnpj}')
                      AND f.fk_statusfatura_id = 5
                      AND f.fatura_data_vencimento < '{$datavigente}'
                GROUP BY
                       e.empresa_id,
                       e.empresa_tipo,
                       e.empresa_razao,
                       e.empresa_fantasia;";
            $resultadoComandoStatus = $this->getDefaultAdapter()->fetchAll($script);
            if (is_array($resultadoComandoStatus) && count($resultadoComandoStatus) > 0) {
                $resultado['situacaofatura'] = true;
                $resultado['situacaofatura']['dados'] = $resultadoComandoStatus;
            }else{
                $resultado['situacaofatura'] = false;
            }
        }
        
        return $resultado;
    }

    public function obterTermos($id) {
        $comando = "SELECT 
                        p.usuario_portal_aceitou_termos, 
                        p.usuario_portal_esocial_aceitou_termos,
                        p.usuario_portal_esocial_autoriza  
                    FROM usuario_portal p 
                    WHERE p.fk_contrato_id = {$id}";
        $dados = $this->getDefaultAdapter()->fetchRow($comando);
        return $dados;
    }

}
