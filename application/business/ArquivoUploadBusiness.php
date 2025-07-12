<?php

/**
 * Classe responsável para fazer o serviço de armazenamento de arquivo em banco de dados,
 * seja ele armazenado diretamento em banco (binário) ou apenas a referencia armazenando o arquivo
 * em disco(HD). <br/>
 * Esta classe foi desenvolvida de modo genérico com isso nem todos recursos precisam ser utilizados, apenas 
 * foi desenvolvida para tentar padronizar os diversos carregamento e descarregamento de arquivos.
 * @author Silas Stoffel <silas.stoffel@hiest.com.br>
 * @version 1.0
 */
class ArquivoUploadBusiness {

    public $id;
    public $mime = null;
    public $extensao = null;
    public $hash = null;
    public $tamanho = null;
    public $nome = null;
    public $conteudoBinario = null;
    public $url = null;
    public $urlThumb = null;
    public $dataHoraRegistro = null;
    public $dataHoraInativacao = null;
    public $status = 0;
    public $parametrosUpload = null;
    public $descricao = null;
    public $observacao = null;
    public $codigoControle = null;
    public $enderecoArquivoEmDisco = null;
    public $enderecoArquivoThumbEmDisco = null;

    public function __construct() {
        
    }

    /**
     * Inativa um ou mais registro(s) de upload na tabela "arquivo_upload" baseado no argumentos atribuidos.
     * @param array $colecaoId Coleção de itens contido na coluna/atributo 'arquivo_upload_id'
     * @param array $codigoControle Coleção de itens contido na coluna/atributo 'arquivo_upload_codigo_controle'
     * @param array $colecaoHash Coleção de itens contido na coluna/atributo 'arquivo_upload_hash'
     * @param array $colecaoMime Coleção de itens contido na coluna/atributo 'arquivo_upload_mime'
     * @param array $colecaoExtensao Coleção de itens contido na coluna/atributo 'arquivo_upload_extensao'
     * @param array $colecaoEnderecoEmDisco Coleção de itens contido na coluna/atributo 'arquivo_upload_end_disco'
     * @param array $colecaoUrl Coleção de itens contido na coluna/atributo 'arquivo_upload_url'
     * @return int número de linhas afetadas
     * @throws Exception
     */
    public static function inativar(array $colecaoId = array(), array $codigoControle = array(), array $colecaoHash = array(), array $colecaoMime = array(), array $colecaoExtensao = array(), array $colecaoEnderecoEmDisco = array(), array $colecaoUrl = array()) {
        $cntInativo = 0;
        if (count($colecaoId) > 0 or count($codigoControle) > 0 or count($colecaoHash) > 0 or count($colecaoMime) > 0 or count($colecaoExtensao) > 0 or count($colecaoEnderecoEmDisco) > 0 or count($colecaoUrl) > 0) {
            $colecaoCondicoes = array();
            if (count($colecaoId) > 0) {
                $x = implode(',', $colecaoId);
                $colecaoCondicoes[] = "arquivo_upload_id IN({$x})";
            }

            if (count($codigoControle) > 0) {
                $items = array();
                foreach ($codigoControle as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_codigo_controle IN({$x})";
            }
            
            if (count($colecaoHash) > 0) {
                $items = array();
                foreach ($colecaoHash as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_hash IN({$x})";
            }

            if (count($colecaoMime) > 0) {
                $items = array();
                foreach ($colecaoMime as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_mime IN({$x})";
            }

            if (count($colecaoExtensao) > 0) {
                $items = array();
                foreach ($colecaoExtensao as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_extensao IN({$x})";
            }

            if (count($colecaoEnderecoEmDisco) > 0) {
                $items = array();
                foreach ($colecaoEnderecoEmDisco as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_end_disco IN({$x})";
            }

            if (count($colecaoUrl) > 0) {
                $items = array();
                foreach ($colecaoUrl as $valor) {
                    $items[] = "'{$valor}'";
                }
                $x = implode(',', $items);
                $colecaoCondicoes[] = "arquivo_upload_url IN({$x})";
            }

            $x = implode(' AND ', $colecaoCondicoes);
            $comando = "UPDATE arquivo_upload SET arquivo_upload_status = 2 WHERE {$x} ";
            try {
                $Cnx = Zend_Db_Table::getDefaultAdapter();
                $prepararComando = $Cnx->prepare($comando);
                $executar = $prepararComando->execute();
                $cntInativo = $prepararComando->rowCount();
            } catch (Exception $exc) {
                throw $exc;
            }
        } else {
            throw new Exception('O método inativar requer que pelo menos uma parametro para realizar inativação');
        }
        return $cntInativo;
    }

    /**
     * Armazena informações e o conteudo de um arquivo no banco de dados.
     * @param array $phpSuperGlobalFile Uma cópia da super global $_FILES[''] do PHP. Exemplo $_FILE['arquivo']. Quando este arqumento for atribuido 
     * automaticamento os atributos de tamanho, conteudo binario,extensao e parametros em json do $_FILES['']
     * @return int Id do registro armazenado no banco de dados
     * @throws type
     * @throws Exception
     */
    public function armazenar(array $phpSuperGlobalFile = array()) {
        $salvouId = 0;
        $id = (int) $this->id;
        $dhr = (strlen($this->dataHoraRegistro) == 0) ? date('Y-m-d H:i:s') : $this->dataHoraRegistro;
        $this->dataHoraRegistro = $dhr;
        $dhi = (strlen($this->dataHoraInativacao) == 0) ? null : $this->dataHoraInativacao;
        $this->dataHoraInativacao = $dhi;
        $params = array(
            'arquivo_upload_mime' => $this->mime,
            'arquivo_upload_extensao' => $this->extensao,
            'arquivo_upload_hash' => $this->hash,
            'arquivo_upload_tamanho' => $this->tamanho,
            'arquivo_upload_nome' => $this->nome,
            'arquivo_upload_binario' => $this->conteudoBinario,
            'arquivo_upload_url' => $this->url,
            'arquivo_upload_url_thumb' => $this->urlThumb,
            'arquivo_upload_dh_registro' => $this->dataHoraRegistro,
            'arquivo_upload_dh_inativacao' => (strlen($this->dataHoraInativacao) == 0) ? null : $this->dataHoraInativacao,
            'arquivo_upload_status' => 0,
            'arquivo_upload_params_json' => $this->parametrosUpload,
            'arquivo_upload_descricao' => $this->descricao,
            'arquivo_upload_observacao' => $this->observacao,
            'arquivo_upload_codigo_controle' => $this->codigoControle,
            'arquivo_upload_end_disco' => $this->enderecoArquivoEmDisco,
            'arquivo_upload_end_disco_thumb' => $this->enderecoArquivoThumbEmDisco,
            'arquivo_upload_status' => $this->status
        );

        if (count($phpSuperGlobalFile) > 0) {
            try {
                $vld = self::_eValido($phpSuperGlobalFile);
                if ($vld == false) {
                    throw new Exception("Erro ao verificar se arquivo é válido. Detalhe: " . self::_obterMensagemErrorUpload($phpSuperGlobalFile));
                }
            } catch (Exception $ex) {
                throw $ex;
            }
            $paramArquivo = $phpSuperGlobalFile;
            $conteudoBinario = file_get_contents($paramArquivo['tmp_name']);
            $tipo = $paramArquivo['type'];
            $extensao = pathinfo($paramArquivo['name'], PATHINFO_EXTENSION);
            $params['arquivo_upload_params_json'] = json_encode($phpSuperGlobalFile);
            $params['arquivo_upload_extensao'] = $extensao;
            $params['arquivo_upload_mime'] = $tipo;
            $params['arquivo_upload_binario'] = $conteudoBinario;
            $params['arquivo_upload_tamanho'] = $paramArquivo['size'];
            if(strlen($params['arquivo_upload_hash']) == 0) {
                $params['arquivo_upload_hash'] = sha1_file($paramArquivo['tmp_name']);
            }
            try {
                $Modelo = new Application_Model_ArquivoUpload();
                if ($id == 0) {
                    $id = $Modelo->insert($params);
                } else {
                    $Modelo->update($params, array('arquivo_upload_id = ?' => $id));
                }
                $salvouId = $id;
            } catch (Exception $exc) {
                throw $exc;
            }
        }
        return $salvouId;
    }

    /**
     * Faz o download do arquivo(armazendo em modo binário no banco de dados) pelo id informado no argumento.
     * @param int $id Id do arquivo localizado na tabela arquivo_upload
     * @throws Exception
     */
    public static function descarregarArquivoArmazenadoEmBancoIdentificandoPeloId($id) {
        try {
            $Modelo = new Application_Model_ArquivoUpload();
            $Rst = $Modelo->fetchRow(array('arquivo_upload_id = ?' => $id));
            if ($Rst) {
                $novoNome = 'ArquivoUpload' . $Rst->arquivo_upload_id . '.' . $Rst->arquivo_upload_extensao;
                $mime = $Rst->arquivo_upload_mime;
                header('Content-Description: Arquivo Hospedado pelo Sistema Integrado de Saude e Seguranca');
                header("Content-Type: {$mime}");
                header('Content-Disposition: attachment; filename="' . $novoNome . '"');
                header('Content-Length: ' . $Rst->arquivo_upload_tamanho);
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Expires: 0');
                echo $Rst->arquivo_upload_binario;
            } else {
                throw new Exception('Arquivo não encontrado!');
            }
        } catch (Exception $exc) {
            throw $exc;
        }
    }

    /**
     * Verifica o perrcurso do UPLOAD da máquina do cliente para o servidor.
     * @param array $colecaoParametroPHPFile - Uma cópia da superglobal $_FILES['']
     * @return boolean true para sucesso e false para sem sucesso.
     * @throws Exception
     */
    private static function _eValido($colecaoParametroPHPFile) {
        $retorno = false;
        //http://php.net/manual/pt_BR/features.file-upload.errors.php
        try {
            $vld = self::_eUmParametroPHPFileUpload($colecaoParametroPHPFile);
            if ($vld == false) {
                throw new Exception('O argumento $colecaoParametroPHPFile no método _eValido() não é uma cópia da da superglobal $_FILES[] do PHP.');
            }
            $upld = $colecaoParametroPHPFile;
            $retorno = (is_numeric($upld['error']) && (int) $upld['error'] == UPLOAD_ERR_OK) ? true : false;
        } catch (Exception $Exc) {
            throw $Exc;
        }
        return $retorno;
    }

    /**
     * Verifica se argumento passado é uma cópia da superglobal $_FILES[''] do PHP.
     * @param array $phpSuperGlobalFile - Copia do SUPERGLOBAL $_FILES[''] do PHP.
     * @return boolean  - true para verdade e false para falso.
     */
    private static function _eUmParametroPHPFileUpload($phpSuperGlobalFile) {
        $retorno = false;
        if (is_array($phpSuperGlobalFile) && count($phpSuperGlobalFile) > 0) {
            $deveConter = array('name', 'type', 'tmp_name', 'error', 'size');
            $retorno = true;
            foreach ($deveConter as $param) {
                if (!isset($phpSuperGlobalFile[$param])) {
                    $retorno = false;
                    break;
                }
            }
        }
        return $retorno;
    }

    /**
     * Obtem a mensagem de erro de um upload
     * @param array $phpSuperGlobalFile Copia da SUPERGLOBAL $_FILES[''] do PHP.
     * @return string Mensagem com detalhes
     * @throws Exception
     */
    private static function _obterMensagemErrorUpload($phpSuperGlobalFile) {
        $upld = null;
        try {
            $vld = self::_eUmParametroPHPFileUpload($phpSuperGlobalFile);
            if ($vld == false) {
                throw new Exception('O argumento $phpSuperGlobalFile não é uma cópia da da superglobal $_FILES[] do PHP.');
            }
            $upld = $phpSuperGlobalFile;
        } catch (Exception $Exc) {
            throw $Exc;
        }
        $code = $upld['error'];
        $message = "Erro de upload desconhecido";
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "O arquivo enviado excede a directiva upload_max_filesize em php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "O arquivo enviado excede a diretiva MAX_FILE_SIZE que foi especificado no formulário HTML";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "O arquivo foi apenas parcialmente enviado";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "Nenhum arquivo foi enviado";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Está faltando uma pasta temporária";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Falha ao gravar arquivo em disco";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "Upload de arquivos parou pela extensão";
                break;
            default:
                $message = "Erro de upload desconhecido";
                break;
        }
        return $message;
    }

}
