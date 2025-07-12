<?php 

require 'eSocialHelper.php';

/**
 * Classe com métodos referentes ao disparo de eventos para o servidor SOAP do eSocial
 * 
 * Para conteudo não contemplado nos links favor referir ao manual oficial da W3C
 * construido com os leiatues do eSocial disponiveis no link da anotacao
 * @link https://www.gov.br/esocial/pt-br/documentacao-tecnica/leiautes-esocial-nt-03-2021-html/index.html
 * @link https://www.gov.br/esocial/pt-br/documentacao-tecnica/manuais/manualorientacaodesenvolvedoresocialv1-10.pdf
 * @author Arthur Xavier | garok102@gmail.com
 * 
 * -- Ganhei uma cafeteira por isso -- 
**/
class eSocialActions {

    static $evtId = '';

    public static function baixarXmlS2220($empresaId, array $intervaloTempo, $funcionarioId) {
        $dadosDaConsulta = self::_geraEvtS2220($empresaId, $intervaloTempo, $funcionarioId);

        if (count($dadosDaConsulta) == 0) {
            echo '<script>alert("Nenhum dado encontrado");</script>';
            die();
        }
        self::empacotarVersaoS1($dadosDaConsulta, '<?xml version="1.0" encoding="UTF-8"?><eSocial xmlns="' . eSocialHelper::NAMESPACE_EVT_MONIT . '" />' , eSocialHelper::NAMESPACE_EVT_MONIT);
    }

    public static function baixarXmlS2240($empresaId, $funcionarioId) {
        $dadosDaConsulta = self::_geraEvtS2240($empresaId, $funcionarioId);

        if (count($dadosDaConsulta) == 0) {
            echo '<script>alert("Nenhum dado encontrado");</script>';
            die();
        }
        self::empacotarVersaoS1($dadosDaConsulta, '<?xml version="1.0" encoding="UTF-8"?><eSocial xmlns="' . eSocialHelper::NAMESPACE_EVT_EXP_RISCO . '" />' , eSocialHelper::NAMESPACE_EVT_EXP_RISCO);
    }

    /**
     * Limpa todos os arquivos de uma dada extensao
     * @param string $ext: extensao de arquivos a serem excluidos
     * @author Arthur Xavier
    **/
    private static function _limparArquivos($ext) {
        $ls = scandir(TMP);

        foreach ($ls as $_ => $file) {
            if (preg_match("/.+\.$ext/", $file)) {
                unlink(TMP . $file);
            }
        }
    }

    /**
     * @param array  $nomeEmdisco: um vetor contendo os nomes dos arquivos a serem zipados
     * @param string $nomeFinal: nome do arquivo zip gerado, este eh por padrao gravado em <DOCUMENT_ROOT>/tmp/<$nomeFinal>
     * @author Arthur Xavier;
    **/
    private static function _ziparArquivos(array $nomeEmDisco, $nomeFinal) {
        $outPath = TMP . $nomeFinal;

        $zip = new ZipArchive();

        if (!$zip->open($outPath, ZipArchive::CREATE))
            die('Erro');

        foreach ($nomeEmDisco as $arquivo) {
            $zip->addFile($arquivo, basename($arquivo));
        }
        $zip->close();
    }

    /**
     * Empacota os dados de acordo com a versao 01_00_00 do esocial. Versao essa que ja estava depreciada no momento
     * da implementacao desta funcao.
     * 
     * @param array $dados      : array associativo contendo os dados a serem transformados em xml
     * @param string $cabecalho : string da raiz do documento xml
     * @since 31/01/2021
     * @author Arthur Xavier : garok102@gmail.com
    **/
    public static function empacotarVersaoS1(array $dados, $cabecalho, $namespace) {
        $names = [];
        $cnpj = $_SESSION['empresa']['empresa_cnpj'];

        $modelEmpresa    = new Application_Model_Empresa();
        $dadosEmp = $modelEmpresa->obterEmpresaEndId($_SESSION['empresa']['empresa_id']);
        if ($dadosEmp['empresa_modalidade'] == 0) {
           $Ident = substr($dadosEmp['empresa_cnpj'], 0, 8);         
        }else {
           $Ident = $dadosEmp['empresa_cpf'];           
        }

        switch ($namespace) {
            case eSocialHelper::NAMESPACE_EVT_EXP_RISCO:
                $tagEvt = 'evtExpRisco';
                break;
            case eSocialHelper::NAMESPACE_EVT_MONIT:
                $tagEvt = 'evtMonit';
                break;
            default:
                throw new Exception('Namespace nao definido');
                break;
        }

        $inc = 1;

        foreach ($dados as $i => $cval) {
            #self::_gerarEventoId($cnpj, $i + 1);
            self::_gerarEventoId($dadosEmp['empresa_modalidade'], $Ident, $inc++);
            
            /*
            if (array_key_exists('evtMonit', $dados[0])) {
                #$cval['evtMonit']['Id'] = self::$evtId;          
            } else {
                #$cval['evtExpRisco']['Id'] = self::$evtId;                
            }
            */
 
            $xml = new SimpleXMLElement($cabecalho);
            Util::array_to_xml($cval, $xml);

            $xml->$tagEvt->addAttribute('Id', self::$evtId);    
            unset($xml->$tagEvt->Id);
            
            $xml_raw = preg_replace('/epi[0-9]+/', 'epi',
                preg_replace('/agNoc[0-9]+/', 'agNoc' ,
                preg_replace('/evento[0-9]+/', 'evento',
                //preg_replace('/<\/evento Id="ID[0-9]+">/', '</evento>',
                // preg_replace('</eSocial xmlns="http://www.esocial.gov.br/schema/lote/eventos/envio/v1_2_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">', '/eSocial',
                preg_replace('/exame[0-9]+/', 'exame',
                preg_replace('/<\?xml version="1.0"\?>/', '',
            $xml->asXML())))));

            $names[$i] = TMP . "{$_SESSION['usuario_portal_id']}_{$_SERVER['REQUEST_TIME']}_{$i}.xml";
            $formatado = Util::formatXmlString($xml_raw);

            file_put_contents(
                $names[$i],
                $formatado
            );
        }        
        self::_ziparArquivos($names, $_SERVER['REQUEST_TIME'] . '.zip');

        header("Content-type: application/zip"); 
        header("Content-Disposition: attachment; filename=" . sprintf('%s.zip', $_SESSION['empresa']['empresa_razao'])); 
        header("Pragma: no-cache"); 
        header("Expires: 0");

        readfile(TMP . $_SERVER['REQUEST_TIME'] . '.zip');

        self::_limparArquivos('zip');
        self::_limparArquivos('xml');
        
        die();
    }

    /** 
     * @param array $intervaloTempo deve conter as chaves 'inicio' e 'fim' seus valores devem ser datas em formato ISO
     * @return array
     * @author Arthur Xavier
    **/
    private static function _geraEvtS2220($empresaId, $intervaloTempo, $funcionarioId = []) {

        $funcionario = $funcionarioId;

        $sql = "SELECT
            tp.tipoexame_id,
            a.agenda_id,
            p.pessoa_cpf AS cpfTrab,
            f2.funcionario_matricula AS matricula,
            f2.funcionario_categoria AS codCateg,
            DATE_FORMAT(a.agenda_data_exame,'%Y-%m-%d') AS dtExm,
            DATE_FORMAT(f.fichamedica_apto_em, '%Y-%m-%d') AS dtAso,
            CASE 
                WHEN t.tipoexame_id = 1 THEN 0
                WHEN t.tipoexame_id = 2 THEN 1
                WHEN t.tipoexame_id = 4 THEN 3
                WHEN t.tipoexame_id = 3 THEN 9
                ELSE 2
            END AS tpExameOcup,
            IF (f.fichamedica_resultado_aptidao IS NULL, 0 , 1) AS resAso,
            e.examinador_nome AS nmMed,
            e.examinador_crm AS nrCRM,
            em.empresa_modalidade AS modalidade,
            IF (em.empresa_modalidade = 0, em.empresa_cnpj, em.empresa_cpf) AS nrInsc,
            em.empresa_cnpj AS nrInsc,
            #em.empresa_cnpj AS nrInsc,
            c.coordenacao_cpf AS cpfResp,
            c.coordenacao_medico AS nmResp,
            c.coordenacao_crm AS nrCrmResp,
            uf.unidade_federativa_sigla as respCRMUF,
            uf2.unidade_federativa_sigla as medCRMUF
            FROM fichamedica f
              JOIN agenda a ON
                  a.agenda_id = f.fk_agenda_id 
              JOIN alocacao al ON 
                   al.alocacao_id = a.fk_alocacao_id
              JOIN funcionario f2 ON
                  f2.funcionario_id = al.fk_funcionario_id AND f2.funcionario_status IN(0,1)
              JOIN pessoa p ON
                  p.pessoa_id = f2.fk_pessoa_id 
              JOIN tipoexame t ON
                  t.tipoexame_id = a.fk_tipoexame_id
              LEFT JOIN examinador e ON
                  e.examinador_id = f.fk_examinador_id
              JOIN empresa em ON
                  em.empresa_id = a.fk_empresa_id
              JOIN contrato ct ON 
                   ct.contrato_id = a.fk_contrato_id
              LEFT JOIN usuario_portal up ON up.fk_contrato_id = ct.contrato_id
              JOIN pcmso p2 ON
                  p2.fk_empresa_id = em.empresa_id  AND p2.pcmso_status = 0
              JOIN coordenacao c ON
                  c.coordenacao_id = p2.fk_coordenacao_id
              LEFT JOIN unidade_federativa uf ON
                  uf.unidade_federativa_id = c.fk_unidade_federativa_id
              LEFT JOIN unidade_federativa uf2 ON
                  uf2.unidade_federativa_id = e.fk_unidade_federativa_id
              JOIN tipoexame tp ON
                  tp.tipoexame_id = a.fk_tipoexame_id
              JOIN funcionario ON
                  p.pessoa_id = funcionario.fk_pessoa_id
             LEFT JOIN esocial_detalhe_envio ed ON ed.fk_agenda_id = a.agenda_id AND ed.esocial_detalhe_envio_status = 0
            WHERE em.empresa_id = $empresaId
                AND a.agenda_data_clinico BETWEEN '{$intervaloTempo['inicio']}' AND '{$intervaloTempo['fim']}'
                AND ed.esocial_detalhe_envio_id IS NULL
                AND f.fichamedica_status = 0
                AND f.fichamedica_resultado_aptidao IN(1,2)
                AND f.fichamedica_apto_em != '0000-00-00'
                AND a.agenda_status = 0 "
                . ((is_string($funcionario)) ? "AND funcionario.funcionario_id IN({$funcionario})" : '')
                // -- AND f2.funcionario_matricula IS NOT NULL
                // -- AND f2.funcionario_matricula NOT IN('')
            . " GROUP BY a.agenda_id 
        ORDER BY 1 DESC";  

        $sqlObterProdutos = "SELECT
                p.produto_codigo_esocial
            FROM
                produto p
            JOIN produto_agenda pa ON
                pa.fk_produto_id = p.produto_id 
            JOIN agenda a ON
                a.agenda_id = pa.fk_agenda_id 
        WHERE a.agenda_id = ?
        AND pa.produto_agenda_status = 0
        AND (p.produto_codigo_esocial IS NOT NULL AND p.produto_codigo_esocial != '')";

        $db = Zend_Db_Table::getDefaultAdapter();
        $dados = $db->fetchAll($sql);

        $resultado = [];

        foreach ($dados as $_ => $row) {
            $evt = eSocialHelper::$S2220;
            $shortcut = &$evt['evtMonit']['exMedOcup'];

            /* PREENCHER */
            $evt['evtMonit']['ideEvento']['indRetif'] = 1;
            $evt['evtMonit']['ideEvento']['nrRecibo'] = '';
            $evt['evtMonit']['ideEvento']['tpAmb']    = 1; 

            if ($row['modalidade'] == 0) {
                $evt['evtMonit']['ideEmpregador']['tpInsc'] = 1; // tipo CNPJ
                $evt['evtMonit']['ideEmpregador']['nrInsc'] = substr($row['nrInsc'], 0, 8);
            } else {
                $evt['evtMonit']['ideEmpregador']['tpInsc'] = 2; // tipo CPF
                $evt['evtMonit']['ideEmpregador']['nrInsc'] = $row['nrInsc'];
            }

            $evt['evtMonit']['ideVinculo']['cpfTrab']      = trim($row['cpfTrab']);
            $evt['evtMonit']['ideVinculo']['matricula']    = trim($row['matricula']);
            #$evt['evtMonit']['ideVinculo']['codCateg']      = ''; // todos trabalhadores no contexto tem vinculo empregaticio
            if (empty($row['matricula'])) {
                $evt['evtMonit']['ideVinculo']['codCateg'] = trim($row['codCateg']);
            }

            $shortcut['tpExameOcup']        = $row['tpExameOcup'];
            $shortcut['aso']['dtAso']       = $row['dtAso'];
            $shortcut['aso']['resAso']      = $row['resAso'];

            if (isset($shortcut['aso']['exame'])) {
                unset($shortcut['aso']['exame']);
            }

            $produtos = $db->fetchAll($sqlObterProdutos, $row['agenda_id']);
            foreach ($produtos as $i => $value) {
                $shortcut['aso']["exame{$i}"]['dtExm'] = $row['dtExm'];
                $shortcut['aso']["exame{$i}"]['procRealizado'] = trim($value['produto_codigo_esocial']);
                $shortcut['aso']["exame{$i}"]['obsProc']       = '';
                $shortcut['aso']["exame{$i}"]['indResult']     = '';
                /* 
                 caso o exame seja admissional e o exame realizado seja audiometria 
                 ordExame sera 1 (inicial) caso contratrario 2 (sequencial)
                */
                if (($row['tpExameOcup'] == 0) && ($value['produto_codigo_esocial'] == '0281')) {
                    $shortcut['aso']["exame{$i}"]['ordExame'] = 1;
                } elseif ($value['produto_codigo_esocial'] == '0281') {
                    $shortcut['aso']["exame{$i}"]['ordExame'] = 2;
                } else {
                    $shortcut['aso']["exame{$i}"]['ordExame'] = '';
                }

            }

            $med = $shortcut['aso']['medico'];
            unset($shortcut['aso']['medico']);
            $shortcut['aso']['medico'] = $med;
            /* unset _esquizofrenico_
                por solicitacao do thiago layber o arquivo TEM QUE ESTAR NA ORDEM do evento do esocial
                so que ja que o php nao permite que um a-array nao possua duas chaves com o mesmo nome 
                os exames sao adicionados com numero e entrar APOS o medico

                unsetar o medico aqui faz com que ele possa ser inserido novamente dessa vez em ultimo apos os exames
            */
            $nomeMed = trim($row['nmMed']);
            if (mb_detect_encoding($nomeMed, 'UTF-8', true) === false) {
                $nomeMed = iconv('ISO-8859-1', 'UTF-8', $nomeMed);
            }
            $nomeMed = str_replace("\\'", "'", $nomeMed);
            $shortcut['aso']['medico']['nmMed'] = $nomeMed ?: '';
            #$shortcut['aso']['medico']['nmMed'] = html_entity_decode(trim($row['nmMed']), ENT_QUOTES, 'UTF-8') ?: '';

            $aux = '';
            preg_match('/[0-9]+/', $row['nrCRM'], $aux);

            $shortcut['aso']['medico']['nrCRM'] = @$aux[0] ?: '';

            preg_match('/[a-zA-Z]+/', $row['nrCRM'], $aux);
            $shortcut['aso']['medico']['ufCRM'] = trim($row['medCRMUF']);
            /* caso o preg_match nao retorne a uf ou N de crm o erro eh omitido*/
            
            if (!empty(trim($row['nmResp'])) && !empty(trim($row['cpfResp']))) {
                $shortcut['respMonit']['cpfResp'] = trim($row['cpfResp']) ?: '';
                $shortcut['respMonit']['nmResp']  = trim($row['nmResp']);

                preg_match('/[0-9]+/', $row['nrCrmResp'], $aux);
                $shortcut['respMonit']['nrCRM'] = @$aux[0];

                preg_match('/[a-zA-Z]+/', $row['nrCrmResp'], $aux);
                $shortcut['respMonit']['ufCRM'] = $row['respCRMUF'];
            }
            $resultado[] = $evt;
        }
        
        $resultado = self::_array_remove_empty($resultado);
        
        return $resultado;
    }

    /**
     * Obtem dados do evento S2240 a partir do id de uma empresa
     * 
     * Cada evento representa um trabalhador, o grupo de agentes nocivos a 
     * qual o mesmo está exposto e os EPIs que usa
     * 
     * @author Arthur Xavier
    **/
    private static function _geraEvtS2240($empresaId, $funcionarioId) {
        $colecaoEvts = [];

        /*
         * busca todos trabalhadores que estao alocados em uma dada empresa
        */
        $sqlEvt = " SELECT 
                       ps.pessoa_id,
                       al.alocacao_id,
                       pr.ppra_id,
                       pri.ppra_item_id,
                       e.empresa_cnpj,
                       ps.pessoa_cpf AS 'cpfTrab',
                       f.funcionario_matricula AS 'matricula',
                       f.funcionario_categoria AS 'codCateg', 
                       pri.ppra_item_dt_ini_validade AS 'dtIniCondicao',
                       pr.ppra_local_trabalho AS 'localAmb',
                       s.setor_nome AS 'descSetor',
                       pr.ppra_local_trabalho_tipo_inscricao AS 'tpInsc',
                       pr.ppra_local_trabalho_numero_inscricao AS 'nrInsc',
                       pri.ppra_item_descricao_atividades AS 'descAtivDesc'
                     FROM alocacao al
                     JOIN setor s ON s.setor_id = al.fk_setor_id
                       AND s.setor_status = 0
                     JOIN funcionario f ON 
                       f.funcionario_id = al.fk_funcionario_id
                       AND f.funcionario_status = 0
                       AND (f.funcionario_motivo_inativacao IS NULL OR f.funcionario_motivo_inativacao = '')
                     JOIN empresa e ON 
                       e.empresa_id = f.fk_empresa_id
                       AND e.empresa_status = 0
                     JOIN contrato c ON 
                       c.contrato_id = f.fk_contrato_id
                       AND c.contrato_status = 0
                     JOIN pessoa ps ON 
                       ps.pessoa_id = f.fk_pessoa_id
                       AND ps.pessoa_status = 0
                     LEFT JOIN ppra_item pri ON
                       pri.ppra_item_id = al.fk_ppra_item_id
                       AND pri.ppra_item_status = 0
                     LEFT JOIN ppra pr ON 
                       pr.ppra_id = pri.fk_ppra_id
                       AND pr.ppra_status = 0
                    WHERE e.empresa_id = {$empresaId}
                     "
                    . ((isset($colecaoFuncionario)) ? " AND f.funcionario_id IN({$colecaoFuncionario})": '')
                    . " GROUP BY f.funcionario_id
                ORDER BY f.funcionario_id DESC"; 

        $sqlAgNoc = "SELECT 
                      *
                      FROM 
                      (
                          SELECT 
                              '' AS 'ppra_item_risco_id',
                              ae.atividades_especiais_nome AS 'risco_nome',
                              ae.atividades_especiais_codigo AS 'codAgNoc',
                              '' AS 'dscAgNoc',
                              '' AS 'tpAval',
                              '' AS 'intConc',
                              '' AS 'limTol',
                              '' AS 'unMed',
                              '' AS 'tecMedicao',
                              '' AS 'utilizEPC',
                              '' AS 'eficEpc',
                              '' AS 'utilizEPI',
                              '' AS 'eficEpi',
                              '' AS 'medProtecao',
                              '' AS 'condFuncto',
                              '' AS 'usoInit',
                              '' AS 'przValid',
                              '' AS 'periodicTroca',
                              '' AS 'higienizacao'
                          FROM ppra_item_atividades_especiais pat
                          JOIN atividades_especiais ae ON 
                            ae.atividades_especiais_id = pat.fk_atividades_especiais_id
                            AND ae.atividades_especiais_status = 0    
                          WHERE pat.fk_ppra_item_id = ?
                            AND pat.ppra_item_atividades_especiais_status = 0
                            UNION
                          SELECT     
                              pir.ppra_item_risco_id, 
                              r.risco_nome AS 'risco_nome',
                              r.risco_codigo AS 'codAgNoc',
                              pir.ppra_item_risco_descricao AS 'dscAgNoc',
                              IF (pir.fk_tipo_agente_nocivo_id = 1, 1, 2) AS 'tpAval',
                              pir.ppra_item_risco_intensidade AS 'intConc',
                              pir.ppra_item_risco_limite_tolerancia AS 'limTol',
                              pir.fk_risco_und_medida_id AS 'unMed', 
                              pir.ppra_item_risco_tecnica_medicao AS 'tecMedicao',                              
                              pir.ppra_item_risco_implementa AS 'utilizEPC',
                              CASE 
                                WHEN pir.ppra_item_risco_eficaz = 1 THEN 'S'
                                WHEN pir.ppra_item_risco_eficaz = 0 THEN 'N'
                              END AS 'eficEpc',
                              pir.ppra_item_risco_utiliza_epi AS 'utilizEPI',
                              CASE 
                                WHEN pir.ppra_item_risco_eficaz_risco = 1 THEN 'S'
                                WHEN pir.ppra_item_risco_eficaz_risco = 0 THEN 'N'
                              END AS 'eficEpi',
                              CASE 
                                WHEN pri.ppra_item_epi_implementacao = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_implementacao = 0 THEN 'N'
                              END AS 'medProtecao',                                  
                              CASE 
                                WHEN pri.ppra_item_epi_funcionamento = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_funcionamento = 0 THEN 'N'
                              END AS 'condFuncto',
                              CASE 
                                WHEN pri.ppra_item_epi_ininterrupto = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_ininterrupto = 0 THEN 'N'
                              END AS 'usoInit',
                              CASE 
                                WHEN pri.ppra_item_epi_validade_ca = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_validade_ca = 0 THEN 'N'
                              END AS 'przValid',
                              CASE 
                                WHEN pri.ppra_item_epi_periodicidade = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_periodicidade = 0 THEN 'N'
                              END AS 'periodicTroca',
                              CASE 
                                WHEN pri.ppra_item_epi_higienizacao = 1 THEN 'S'
                                WHEN pri.ppra_item_epi_higienizacao = 0 THEN 'N'
                              END AS 'higienizacao'                                                 
                              FROM ppra_item pri                         
                              LEFT JOIN ppra_item_risco pir ON
                               pir.fk_ppra_item_id = pri.ppra_item_id
                               AND pir.ppra_item_risco_status = 0
                              LEFT JOIN risco r ON 
                               r.risco_id = pir.fk_risco_id
                               AND r.risco_status = 0
                              LEFT JOIN ppra pr ON 
                               pr.ppra_id = pri.fk_ppra_id
                               AND pr.ppra_status = 0
                              WHERE pri.ppra_item_id = ?
                               AND pri.ppra_item_status = 0
                      ) AS consulta
                      WHERE consulta.codAgNoc IS NOT NULL AND consulta.codAgNoc != ''
                      ORDER BY consulta.risco_nome";

        $sqlEpi = "SELECT
                ep.ppra_item_risco_epi_certificado_ca_doc_epi AS 'docAval',
                ep.ppra_item_risco_epi_descricao              AS 'dscEPI'
              FROM ppra_item_risco_epi ep
              JOIN epi e ON 
                  e.epi_id = ep.fk_epi_id
                  AND e.epi_status = 0
              WHERE ep.fk_ppra_item_risco_id = ?
                  AND ep.ppra_item_risco_epi_status = 0";

        $sqlDadosResp = "SELECT 
                p.ppra_id,
                pr.ppra_responsavel_cpf AS 'cpfResp',
                pr.ppra_responsavel_registro AS 'nrOC',
                po.ppra_orgao_id AS 'ideOC',
                uf.unidade_federativa_sigla AS 'ufOC'
            FROM empresa e 
            LEFT JOIN ppra p ON 
                e.empresa_id = p.fk_empresa_id
                AND p.ppra_status = 0
            LEFT JOIN ppra_responsavel pr ON
                p.ppra_id = pr.fk_ppra_id
            INNER JOIN ppra_orgao po ON 
                po.ppra_orgao_id = pr.fk_ppra_orgao_id
            INNER JOIN unidade_federativa uf ON 
                uf.unidade_federativa_id = pr.fk_unidade_federativa_id
        WHERE e.empresa_id = {$empresaId}";

        /* consulta obtem a maior data entre a data do ASO referente a agenda
         * realizada enquanto o funcionário estava vinculada ao referido item do
         * PGR e a data de inicio de validade do item do PGR, essa regra foi
         * solicitada por Chrystian  
        */
        /*
        $sqlDtIniCondicao = "SELECT GREATEST(
            (SELECT f.fichamedica_apto_em FROM agenda a JOIN fichamedica f ON f.fk_agenda_id = a.agenda_id WHERE f.fichamedica_status = 0 AND a.fk_pessoa_id = ? AND a.fk_alocacao_id = ?),
            COALESCE((SELECT pi2.ppra_item_dt_ini_validade FROM ppra_item pi2 WHERE pi2.ppra_item_id = ?), 0) 
        )";
        */
        $sqlDtIniCondicao = "SELECT 
                             *
                             FROM 
                             (
                                 (SELECT fun.funcionario_data_admissao AS 'dtIniCondicao' FROM alocacao alc 
                                 JOIN funcionario fun ON fun.funcionario_id = alc.fk_funcionario_id AND fun.funcionario_status = 0
                                 WHERE alc.alocacao_id = ?
                                 LIMIT 1)
                                 UNION ALL 
                                 (SELECT ppri.ppra_item_dt_ini_validade AS 'dtIniCondicao' FROM alocacao alc 
                                 LEFT JOIN ppra_item ppri ON
                                   ppri.ppra_item_id = alc.fk_ppra_item_id
                                   AND ppri.ppra_item_status = 0
                                 LEFT JOIN ppra ppr ON 
                                   ppr.ppra_id = ppri.fk_ppra_id
                                   AND ppr.ppra_status = 0
                                 WHERE alc.alocacao_id = ?
                                 LIMIT 1)
                                 UNION ALL 
                                 (SELECT fun.fichamedica_apto_em AS 'dtIniCondicao' 
                                 FROM agenda ag 
                                 JOIN fichamedica fun ON fun.fk_agenda_id = ag.agenda_id AND fun.fichamedica_status = 0
                                 WHERE ag.fk_alocacao_id = ?
                                 AND ag.fk_tipoexame_id = 1
                                 AND ag.agenda_presente_clinico = 1
                                 LIMIT 1)
                             ) AS lista
                            ORDER BY dtIniCondicao DESC 
                            LIMIT 1";

        $db = Zend_Db_Table::getDefaultAdapter();

        $dadosPpra       = $db->fetchAll($sqlEvt);
        $dadosResp = $db->fetchRow($sqlDadosResp);
        
        foreach ($dadosPpra as $val) {
            $evt = eSocialHelper::$S2240;
            
             
           
            $dtIniCondicao   = $db->fetchOne($sqlDtIniCondicao, [$val['alocacao_id'], $val['alocacao_id'], $val['alocacao_id']]);

            $shortcut = &$evt['evtExpRisco'];
            $shortcut['Id'] = 'XXX';
              
            $shortcut['ideEmpregador']['nrInsc'] = substr($val['empresa_cnpj'], 0, 8);

            $shortcut['ideVinculo']['cpfTrab']   = trim($val['cpfTrab']);
            $shortcut['ideVinculo']['matricula'] = trim($val['matricula']);
            if (empty($val['matricula'])) {
                $shortcut['ideVinculo']['codCateg'] = trim($val['codCateg']);
            }

            $shortcut['infoExpRisco']['dtIniCondicao'] = $dtIniCondicao;
            
            $shortcut['infoExpRisco']['infoAmb']['localAmb'] = trim($val['localAmb']);
            $shortcut['infoExpRisco']['infoAmb']['dscSetor'] = html_entity_decode(strip_tags($val['descSetor']));
            $shortcut['infoExpRisco']['infoAmb']['tpInsc']   = $val['tpInsc'];
            $shortcut['infoExpRisco']['infoAmb']['nrInsc']   = trim($val['nrInsc']);

            #$shortcut['infoExpRisco']['infoAmb']['dscSetor'] = substr(preg_replace('~[[:cntrl:]]~', '', html_entity_decode(strip_tags($val['descSetor']))), 0, 99);
            $shortcut['infoExpRisco']['infoAmb']['dscSetor'] = trim($val['descSetor']);
            $shortcut['infoExpRisco']['infoAtiv']['dscAtivDes'] = preg_replace('~[[:cntrl:]]~', '', html_entity_decode(strip_tags($val['descAtivDesc'])));
            //$shortcut['infoExpRisco']['infoAtiv']['dscAtivDes'] = substr(preg_replace('~[[:cntrl:]]~', '', html_entity_decode(strip_tags($val['dscAtivDes']))), 0, 999);
            
            $dadosAgNoc = $db->fetchAll($sqlAgNoc, array($val['ppra_item_id'], $val['ppra_item_id']));

            unset($shortcut['infoExpRisco']['respReg']);
            foreach ($dadosAgNoc as $i => $valAgNoc) {
                /* TROCANDO NULL POR UMA STRING VAZIA */
                $valAgNoc = array_map(function($val) { return $val != null ? $val : ''; }, $valAgNoc);
                $shortcutAgNoc = &$shortcut['infoExpRisco']["agNoc{$i}"];

                /*  ausencia de riscos especificos :
                 *  em caso de ausencia de risco especifico apenas preenche 
                 *  o codigo do risco 
                **/
                if ($valAgNoc['codAgNoc'] == '09.01.001') {
                    // $shortcutAgNoc = &$shortcut['infoExpRisco']['agNoc'];
                    $shortcutAgNoc['codAgNoc'] = $valAgNoc['codAgNoc'];
                    continue;
                }
                
                $shortcutAgNoc['codAgNoc']   = $valAgNoc['codAgNoc'];
                $shortcutAgNoc['dscAgNoc']   = $valAgNoc['dscAgNoc'];
                $shortcutAgNoc['tpAval']     = $valAgNoc['tpAval'];
                //$shortcutAgNoc['intConc']    = (float) $valAgNoc['intConc'];
                if ($valAgNoc['intConc'] == 'ND') {
                    $shortcutAgNoc['intConc']    = (string) '00.00';
                }else{
                    $valAgNoc['intConc'] = str_replace(",", ".", $valAgNoc['intConc']);
                    $valAgNoc['intConc'] = filter_var($valAgNoc['intConc'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $shortcutAgNoc['intConc']    =  trim($valAgNoc['intConc']);
                }
                
                if ($valAgNoc['tpAval'] == 1 AND ($valAgNoc['codAgNoc'] == '01.18.001' || $valAgNoc['codAgNoc'] == '02.01.014')) {
                    $valAgNoc['limTol'] = str_replace(",", ".", $valAgNoc['limTol']);
                    $valAgNoc['limTol'] = filter_var($valAgNoc['limTol'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); 
                    $shortcutAgNoc['limTol'] = $valAgNoc['limTol']; 
                }  
                
                if ($valAgNoc['tpAval'] == 1) {
                    $shortcutAgNoc['unMed']  = $valAgNoc['unMed'];
                    $shortcutAgNoc['tecMedicao'] = trim($valAgNoc['tecMedicao']);
                }

                $shortcutAgNoc['epcEpi']['utilizEPC']   = (string) $valAgNoc['utilizEPC'];
                if ((int) $valAgNoc['utilizEPC'] == 2) {
                    $shortcutAgNoc['epcEpi']['eficEpc']     = $valAgNoc['eficEpc'];
                }                
                $shortcutAgNoc['epcEpi']['utilizEPI']   = (string) $valAgNoc['utilizEPI'];
                if ((int) $valAgNoc['utilizEPI'] == 2) {
                    $shortcutAgNoc['epcEpi']['eficEpi']     = $valAgNoc['eficEpi'];
                }                

                foreach ($db->fetchAll($sqlEpi, $valAgNoc['ppra_item_risco_id']) as $j => $valEpi) {
                    $evt['evtExpRisco']['infoExpRisco']["agNoc{$i}"]['epcEpi']["epi{$j}"]['docAval'] = trim($valEpi['docAval']);
                    if (empty($valEpi['docAval'])) {
                        $evt['evtExpRisco']['infoExpRisco']["agNoc{$i}"]['epcEpi']["epi{$j}"]['dscEPI']  = trim($valEpi['dscEPI']);
                    }                    
                }

                if ((int) $valAgNoc['utilizEPI'] == 2) {
                    $shortcutAgNoc['epcEpi']['epiCompl']['medProtecao']   = $valAgNoc['medProtecao'];
                    $shortcutAgNoc['epcEpi']['epiCompl']['condFuncto']    = $valAgNoc['condFuncto'];
                    $shortcutAgNoc['epcEpi']['epiCompl']['usoInint']      = $valAgNoc['usoInit'];
                    $shortcutAgNoc['epcEpi']['epiCompl']['przValid']      = $valAgNoc['przValid'];
                    $shortcutAgNoc['epcEpi']['epiCompl']['periodicTroca'] = $valAgNoc['periodicTroca'];
                    $shortcutAgNoc['epcEpi']['epiCompl']['higienizacao']  = $valAgNoc['higienizacao'];
                }

            }
            unset($shortcut['infoExpRisco']['agNoc']);
            unset($evt['evtExpRisco']['infoExpRisco']['agNoc']['epcEpi']["epi"]);

            $shortcut['infoExpRisco']['respReg']['cpfResp'] = trim($dadosResp['cpfResp']);
            $shortcut['infoExpRisco']['respReg']['ideOC']   = trim($dadosResp['ideOC']);
            if ((int) $dadosResp['ideOC'] == 9) {
                $shortcut['infoExpRisco']['respReg']['dscOC'] = trim($dadosResp['dscOC']);                
            }            
            $shortcut['infoExpRisco']['respReg']['nrOC']    = trim($dadosResp['nrOC']);
            $shortcut['infoExpRisco']['respReg']['ufOC']    = $dadosResp['ufOC'];
            
            $colecaoEvts[] = $evt;
        }
        
        $colecaoEvts = self::_array_remove_empty($colecaoEvts);

        return $colecaoEvts;
    }

    /**
     * Obtem dados do evento S2240 a partir do id de uma empresa
     * @author Arthur Xavier
    **/
    private static function _gerarEventoId($modalidade, $ident, $incremento) {
        #$cnpj = substr($cnpj, 0, 11);
        $sequencia = '00000';
        if ($incremento >= 1 && $incremento <= 9) {
           $sequencia = '0000' . $incremento;
        }elseif ($incremento >= 10 && $incremento <= 99) {
           $sequencia = '000' . $incremento;
        }elseif ($incremento >= 100 && $incremento <= 999) {
           $sequencia = '00' . $incremento; 
        }

        if ($modalidade == 0) {
            self::$evtId = 'ID1' . $ident . '000000' . date('Ymdhis') . $sequencia;
        } else {
            self::$evtId = 'ID2' . $ident . '000' . date('Ymdhis') . $sequencia;
        }         
    }

    /**
     * Remove todos os valores vazios ou null do array
     * 
     * @link https://stackoverflow.com/questions/7696548/php-how-to-remove-empty-entries-of-an-array-recursively
     * @since 22/07/2022
    **/
    private static function _array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = self::_array_remove_empty($haystack[$key]);
            }
    
            if ($haystack[$key] == '' || $haystack[$key] == null) {
                unset($haystack[$key]);
            }
        }
    
        return $haystack;
    }
}