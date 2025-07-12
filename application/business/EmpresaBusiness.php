<?php

class EmpresaBusiness {

    public $empresaId = 0;

    public function __construct($empresaId = 0) {
        $this->empresaId = $empresaId;
    }

    /**
     * Verifica se a empresa possui inadiplencia registrada no sistema.
     * Para ser considerada INADIPLENTE é necessário que o último registro
     * do CNPJ esteja definido como inadimplente.
     * @param string $paramEmpresaCnpj
     * @return boolean
     * @throws Exception
     */
    public function possuiRestricaoDeCredito($paramEmpresaCnpj) {
        if (strlen($paramEmpresaCnpj) < 14) {
            throw new Exception('O CNPJ informado não possui a quantidade de caracteres válidos para um CNPJ');
        }
        $resultado = false;
        $Modelo = null;
        $cnpjLimpo = str_replace(array('.', '-', '/', '=', ','), '', $paramEmpresaCnpj);
        $clausulaComando = ' 1 = 1 ';
        $clausulaComando .= " AND empresa.empresa_cnpj = '{$cnpjLimpo}' ";
        $clausulaComando .= " AND avaliacao_restricao.avaliacao_restricao_status = 0";
        $ordenarPor = "avaliacao_restricao.avaliacao_restricao_id DESC";
        try {
            $Modelo = new Application_Model_AvaliacaoRestricao();
            $colecaoResultados = $Modelo->buscarUsandoFiltro($clausulaComando, $ordenarPor);
            if (is_array($colecaoResultados) && count($colecaoResultados) > 0) {
                $UmResultado = $colecaoResultados[0];
                if (strcasecmp($UmResultado['avaliacao_restricao_resultado'], 'COM_RESTRICAO') == 0) {
                    $resultado = true;
                }
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        return $resultado;
    }

    /**
     * Verifica se uma empresa está inadimplente
     * @param array $colecaoUnidadeId Ids das unidades.
     * @param array $paramEmpresaCnpj [OPCIONAL] Colecao dos CNPJ's a serem considerados na consulta.
     * @param int $qtdDiasContabilizarInadimplencia [OPCIONAL] Quantidade de dias para uma fatura ser considerada como inadimplente. Valor padrão NULL dias.
     * @return bool TRUE se inadimplente ou FALSE se adimplente
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public static function estaInadimplente(array $colecaoUnidadeId, array $paramEmpresaCnpj = array(), $qtdDiasContabilizarInadimplencia = null) {
        $resultadoMetodo = false;
        try {
            $ModeloEmpresa = new Application_Model_Empresa();
            $rst = $ModeloEmpresa->obterEmpresaInadimplente($colecaoUnidadeId, $paramEmpresaCnpj, $qtdDiasContabilizarInadimplencia);
            $resultadoMetodo = (bool) (is_array($rst) && count($rst) > 0);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $resultadoMetodo;
    }

}