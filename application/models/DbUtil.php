<?php

class Application_Model_DbUtil extends Zend_Db_Table {

    /**
     * Resgata todas as colunas de uma tabela ou lista de colunas das tabelas e retorna em estrutura
     * do tipo <b>"hash table"</b> com valores nulos. O índice do hash table é nome da coluna definida na tabela
     * do banco de dados. Valor de todos as colunas/atributos é do tipo null. Veja o exemplo no tipo de retorno.
     *  
     * @param array|string $tabela - Nome ou uma lista "array" com nomes das tabelas
     * @return mixed
     * Exemplo de retorno:
     * <ul>
     *  <li>$item["cliente_nome"] = null</li>
     *  <li>$item["cliente_cpf"] = null</li>
     *  <li>$item["funcionario_cargo"] = null</li>
     * </ul>
     */
    public static function obterAtributosTabelaComoChaveDoVetor($tabela) {
        $listaTabelas = array();
        if (is_string($tabela)) {
            $listaTabelas[] = $tabela;
        } else if (is_array($tabela)) {
            $listaTabelas = $tabela;
        }
        $conexao = Zend_Db_Table::getDefaultAdapter();
        $resultado = array();
        foreach ($listaTabelas as $nome) {
            $comando = 'DESC ' . $nome;
            $consulta = $conexao->fetchAll($comando);
            if (is_array($consulta) && count($consulta) > 0) {
                foreach ($consulta as $consulta) {
                    $chave = $consulta['Field'];
                    $resultado[$chave] = null;
                }
            }
        }
        return $resultado;
    }

    public static function obterUnidadesFormatadasEmHtmlParaRodapeDocumentos() {
        /*
         * Ativa rastreio de problemas.
         * Recomendamos ficar como false no
         * Ambiente de produção
         */
        $monitorarScript = false;
        $htmlRodape = null;
        $comando = "SELECT * FROM componentehtml WHERE componentehtml_status = 0 AND componentehtml_nome = 'Rodape Documentos PDFs'";
        try {
            $conexao = Zend_Db_Table::getDefaultAdapter();
            $resultado = $conexao->fetchRow($comando);
            if (is_array($resultado) && count($resultado) > 0) {
                $htmlRodape = $resultado['componentehtml_conteudo'];
            }
        } catch (Exception $e) {
            if ($monitorarScript) {
                echo $e->getMessage();
            }
        }
        return $htmlRodape;
    }

    public static function obterUnidadesFormatadasEmHtmlParaRodapeDocumentoss() {
        /*
         * Ativa rastreio de problemas.
         * Recomendamos ficar como false no
         * Ambiente de produção
         */
        $monitorarScript = false;
        $htmlRodape = null;
        $comando = "SELECT * FROM componentehtml WHERE componentehtml_status = 0 AND componentehtml_nome = 'Rodape Documentos PDFs'";
        try {
            $conexao = Zend_Db_Table::getDefaultAdapter();
            $resultado = $conexao->fetchRow($comando);
            if (is_array($resultado) && count($resultado) > 0) {
                $htmlRodape = $resultado['componentehtml_conteudo'];
            }
        } catch (Exception $e) {
            if ($monitorarScript) {
                echo $e->getMessage();
            }
        }
        return $htmlRodape;
    }

}
