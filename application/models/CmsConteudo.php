<?php

class Application_Model_CmsConteudo extends Zend_Db_Table {

    protected $_name = 'cms_conteudo';
    protected $_primary = 'cms_conteudo_id';

    public function obterRelacaoBanners() {

        $dia = date('Y/m/d');

        $comando = "SELECT  
                    c.cms_conteudo_id AS id,                  
                    c.cms_conteudo_url AS src,
                    c.cms_conteudo_texto AS txt,
                    c.cms_conteudo_link_texto AS link
                    FROM cms_conteudo c
                    WHERE c.cms_conteudo_status = 0
                    AND c.cms_conteudo_valido_ate >= '{$dia}'
                    AND c.fk_cms_id = 1
                    ORDER BY c.cms_conteudo_valido_ate ASC";
        $dados = $this->getDefaultAdapter()->fetchAll($comando);
/*
        foreach ($dados as $key => $value) {

            $limite = 25;
            $url = $value['src'];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);        // Inicia uma nova sessão do cURL
            curl_setopt($curl, CURLOPT_TIMEOUT, $limite); // Define um tempo limite da requisição
            curl_setopt($curl, CURLOPT_NOBODY, true);     // Define que iremos realizar uma requisição "HEAD"
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false); // Não exibir a saída no navegador
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Não verificar o certificado do site

            curl_exec($curl);  // Executa a sessão do cURL
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200; // Se a resposta for OK, a URL está ativa
            curl_close($curl); // Fecha a sessão do cURL
            #if ($key == 0) {
                if ($status) {
                    #echo "O link fornecido está disponível! ". $value['src'];exit();
                } else {
                    #echo "O link fornecido está quebrado. ". $value['src'];exit();
                    unset($dados[$key]);
                }
            #}

        }
        #util::dump($dados);
*/
        return $dados;
    }

    public function obterDadosUltimoRegistro() {

        $comando = "SELECT c.cms_conteudo_id 
                    FROM cms_conteudo c
                    WHERE c.cms_conteudo_status = 0
                    LIMIT 1";
        return $this->getDefaultAdapter()->fetchRow($comando);
    }

}
