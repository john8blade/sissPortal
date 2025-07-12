<?php

class Application_Model_DocumentoOperacional {

    public function obterFiltroPeriodicos($filtro, $data1, $data2) {

    #####################################################
    #                       CONSULTA                    #
    #####################################################
    $Consulta = NULL;
    try {

      $script = "SELECT  
                    consulta.agenda_id,
                    consulta.empresa_id, consulta.contrato_id, consulta.pessoa_id, 
                    consulta.empresa_fantasia, consulta.contrato_numero, 
                    consulta.pessoa_cpf, consulta.pessoa_nome, consulta.funcao_nome,
                    consulta.tipoexame_id, consulta.tipoexame_nome,  
                    consulta.item_pcmso_possui_audio_pos, consulta.produto_agenda_data_programada, consulta.produto_id, consulta.produto_nome,
                    consulta.periodo_id, consulta.periodo_nome, consulta.produto_periodo, consulta.proximoexame
                  FROM ( 
                    SELECT  
                      
                      exames.agenda_id, exames.empresa_id, exames.contrato_id, exames.pessoa_id, 
                      exames.empresa_fantasia, exames.contrato_numero, 
                      exames.pessoa_cpf, exames.pessoa_nome, exames.funcao_nome,
                      exames.tipoexame_id,
                      'Periódico' AS tipoexame_nome,
                      exames.item_pcmso_possui_audio_pos, exames.produto_agenda_data_programada, exames.produto_id, exames.produto_nome,
                      exames.periodo_id, exames.periodo_nome,
                      
                      CASE  
                        WHEN exames.tipoexame_id = 1 AND exames.produto_id = 11 AND exames.item_pcmso_possui_audio_pos = 1 THEN CONCAT(exames.produto_nome, ' ( Semestral* )')
                        WHEN exames.produto_periodo IS NULL AND exames.tipoexame_id = 1 AND exames.produto_id = 11 AND exames.item_pcmso_possui_audio_pos = 1 THEN CONCAT(exames.produto_nome, ' ( Semestral* )')
                        WHEN exames.produto_periodo IS NULL THEN CONCAT(exames.produto_nome, ' ( Anual* )') 
                        ELSE exames.produto_periodo END AS produto_periodo,  

                      CASE 
                        WHEN exames.tipoexame_id = 1 AND exames.produto_id = 11 AND exames.item_pcmso_possui_audio_pos = 1 THEN ADDDATE(exames.produto_agenda_data_programada, INTERVAL 6 MONTH) 
                        WHEN exames.proximoexame IS NULL AND exames.tipoexame_id = 1 AND exames.produto_id = 11 AND exames.item_pcmso_possui_audio_pos = 1 THEN ADDDATE(exames.produto_agenda_data_programada, INTERVAL 6 MONTH) 
                        WHEN exames.proximoexame IS NULL THEN ADDDATE(exames.produto_agenda_data_programada, INTERVAL 1 YEAR) 
                        ELSE exames.proximoexame END AS proximoexame

                    FROM (
                      SELECT  
                      
                        funcionarios.*,
                        CONCAT(funcionarios.produto_nome, ' ( ', funcionarios.periodo_nome,' )') AS produto_periodo,  
                        CASE funcionarios.periodo_id 
                        WHEN 5 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 6 MONTH) /*SEMESTRAL*/
                        WHEN 6 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 1 YEAR) /*ANUAL*/
                        WHEN 7 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 2 YEAR) /*BIENAL*/
                        WHEN 8 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 3 YEAR) /*TRIENAL*/
                        WHEN 9 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 5 YEAR) /*QUINQUENAL*/
                        WHEN 10 THEN ADDDATE(funcionarios.produto_agenda_data_programada, INTERVAL 5 YEAR) /*QUADRIENAL*/ END AS proximoexame
                      FROM (
                          SELECT          
                            a.agenda_id, e.empresa_id, c.contrato_id, ps.pessoa_id, e.empresa_fantasia, 
                            c.contrato_numero, ps.pessoa_cpf, ps.pessoa_nome,
                            CASE 
                            WHEN pp.ppra_item_funcao IS NULL OR pp.ppra_item_funcao = '' THEN cg.cargo_nome
                            ELSE pp.ppra_item_funcao END AS funcao_nome,
                            tp.tipoexame_id, tp.tipoexame_nome,
                            ip.item_pcmso_possui_audio_pos, pa.produto_agenda_data_programada, p.produto_id, p.produto_nome,
                                      
                            (SELECT pe2.periodo_id
                            FROM item_pcmso_produto ipp2
                            JOIN periodo pe2 ON pe2.periodo_id = ipp2.fk_periodo_id AND pe2.periodo_status = 0
                            WHERE ipp2.fk_item_pcmso_id = ip.item_pcmso_id AND ipp2.fk_produto_id = p.produto_id AND ipp2.fk_tipoexame_id = 2
                            ORDER BY ipp2.fk_tipoexame_id ASC
                            LIMIT 1) AS periodo_id,
                            
                            (SELECT pe2.periodo_nome  
                                FROM item_pcmso_produto ipp2
                                JOIN periodo pe2 ON pe2.periodo_id = ipp2.fk_periodo_id AND pe2.periodo_status = 0
                                WHERE ipp2.fk_item_pcmso_id = ip.item_pcmso_id AND ipp2.fk_produto_id = p.produto_id
                                AND ipp2.fk_tipoexame_id = 2
                                ORDER BY ipp2.fk_tipoexame_id ASC LIMIT 1) AS periodo_nome
                            
                          FROM produto_agenda pa
                            LEFT JOIN agenda a ON a.agenda_id = pa.fk_agenda_id AND a.agenda_status = 0
                            LEFT JOIN alocacao al ON al.alocacao_id = a.fk_alocacao_id
                            JOIN funcionario fu ON fu.funcionario_id = al.fk_funcionario_id AND fu.funcionario_status = 0 AND (fu.funcionario_motivo_inativacao = '' OR fu.funcionario_motivo_inativacao IS NULL)
                            LEFT JOIN pessoa ps ON ps.pessoa_id = a.fk_pessoa_id AND ps.pessoa_status = 0
                            LEFT JOIN contrato c ON c.contrato_id = a.fk_contrato_id AND c.contrato_status = 0
                            LEFT JOIN empresa e ON e.empresa_id = a.fk_empresa_id AND e.empresa_status = 0
                            LEFT JOIN cargo cg ON cg.cargo_id = al.fk_cargo_id
                            LEFT JOIN ppra_item pp ON pp.ppra_item_id = al.fk_ppra_item_id
                            LEFT JOIN produto p ON p.produto_id = pa.fk_produto_id AND p.produto_status = 0
                            LEFT JOIN tipoexame tp ON tp.tipoexame_id = a.fk_tipoexame_id AND tp.tipoexame_status = 0
                            LEFT JOIN pcmso pc ON pc.fk_empresa_id = a.fk_empresa_id AND pc.fk_contrato_id = a.fk_contrato_id AND pc.pcmso_status = 0
                            LEFT JOIN item_pcmso ip ON ip.fk_pcmso_id = pc.pcmso_id AND al.fk_setor_id = ip.fk_setor_id AND al.fk_cargo_id = ip.fk_cargo_id AND al.fk_ghe_id = ip.fk_ghe_id AND al.fk_funcao_id = ip.fk_funcao_id AND ip.item_pcmso_status = 0
                          WHERE pa.produto_agenda_status = 0 AND pa.produto_agenda_executado = 1 AND tp.tipoexame_id IN(1,2,4,5) 
                          ORDER BY e.empresa_fantasia, ps.pessoa_nome ASC) AS funcionarios
                    ) AS exames
                  ) AS consulta 
                  WHERE {$filtro}                  
                  ORDER BY consulta.empresa_fantasia, consulta.pessoa_nome, consulta.produto_nome ASC, consulta.proximoexame DESC;"; 
      //echo '<per>'.$script.'</per>';exit(0);
      //$Consulta = $this->getDefaultAdapter()->fetchAll($script);
      $Cnx = Zend_Db_Table::getDefaultAdapter();
      $Consulta = $Cnx->fetchAll($script);
      #util::dump($Consulta);      
      $lista = $Consulta;
      
      $listaCompleta = [];
      #REGRA PARA VERIFICAR SE EXISTE EXAME POSTERIOR AO IDENTIFICADO
      foreach ($lista as $key => $item) {

        $comando = "SELECT 
                    a.agenda_id, pa.produto_agenda_data_programada, a.fk_pessoa_id, a.fk_tipoexame_id,
                    ADDDATE(pa.produto_agenda_data_programada, INTERVAL 1 YEAR) AS proximoexame
                    FROM agenda a
                    JOIN produto_agenda pa ON pa.fk_agenda_id = a.agenda_id 
                      AND pa.produto_agenda_status = 0
                    WHERE a.agenda_status = 0
                      AND a.fk_empresa_id = {$item['empresa_id']}
                      AND a.fk_contrato_id = {$item['contrato_id']}
                      AND a.fk_pessoa_id = {$item['pessoa_id']}
                      AND pa.fk_produto_id = {$item['produto_id']}
                    ORDER BY a.agenda_id DESC LIMIT 1;"; 
        //echo '<per>'.$comando.'</per>';exit(0);
        //$temExame = $this->getDefaultAdapter()->fetchRow($comando);
        //$Cnx = Zend_Db_Table::getDefaultAdapter();
        $temExame = $Cnx->fetchRow($comando);             
        //util::dump($temExame); 
        
        if (isset($temExame) AND $temExame['agenda_id'] != $item['agenda_id'] AND strtotime($temExame['produto_agenda_data_programada']) > strtotime($item['produto_agenda_data_programada']) AND strtotime($temExame['proximoexame']) > strtotime($data2)) {
           unset($lista[$key]);
        }

        $script = "SELECT 
                    a.agenda_id, a.fk_tipoexame_id
                    FROM agenda a
                    JOIN produto_agenda pa ON pa.fk_agenda_id = a.agenda_id 
                      AND pa.produto_agenda_status = 0
                    WHERE a.agenda_status = 0
                      AND a.fk_empresa_id = {$item['empresa_id']}
                      AND a.fk_contrato_id = {$item['contrato_id']}
                      AND a.fk_pessoa_id = {$item['pessoa_id']}
                    ORDER BY a.agenda_id DESC LIMIT 1;"; 
        //echo '<per>'.$script.'</per>';exit(0);
        //$UltimaAgDemissional = $this->getDefaultAdapter()->fetchRow($script);
        $UltimaAgDemissional = $Cnx->fetchRow($script);

        //util::dump($UltimaAgDemissional);  
         
        if ($UltimaAgDemissional['fk_tipoexame_id'] == 3) {
           unset($lista[$key]);
        }        
          
      } 
      
      $Consulta = $lista;
      //util::dump($Consulta);
    } catch (Exception $e) {
      throw new Exception("Erro ao processar consulta no banco de dados do relatório de convocação de periódicos - ".$e, 1);          
    }

        return $Consulta;
    }

    public static function _convocacaoDePeriodico() {
        $Cnx = Zend_Db_Table::getDefaultAdapter();

        # APLICAR FILTROS
        $comando = file_get_contents(APPLICATION_PATH . '/models/sql-query/relatorio-convocacao-periodicos-01.sql');

        $data1 = Util::getParam('data1');
        $data2 = Util::getParam('data2');
        $setor = (int) Util::getParam('setor');
        $empresa = (int) Util::getParam('empresa');
        $contrato = (int) Util::getParam('contrato');
        $funcionario = (int) Util::getParam('funcionario');

        $setorDados = $Cnx->fetchRow("SELECT * FROM setor WHERE setor_id = ?", array($setor));
        $empresaDados = $Cnx->fetchRow("SELECT * FROM empresa WHERE empresa_id = ?", array($empresa));
        $contratoDados = $Cnx->fetchRow("SELECT * FROM contrato WHERE contrato_id = ?", array($contrato));
        $funcionarioDados = $Cnx->fetchRow("SELECT * FROM funcionario JOIN pessoa ON pessoa_id = fk_pessoa_id WHERE funcionario_id = ?", array($funcionario));

        $filtros = array(
            'data1' => $data1,
            'data2' => $data2,
            'setor' => $setorDados ? $setorDados['setor_nome'] : '',
            'empresa' => $empresaDados ? $empresaDados['empresa_fantasia'] : '',
            'contrato' => $contratoDados ? $contratoDados['contrato_numero'] : '',
            'funcionario' => $funcionarioDados ? $funcionarioDados['pessoa_nome'] : '');

        if ($setor > 0)
            $comando = str_replace('/*PARAMETRO-SETOR*/', "AND fk_setor_id = '{$setor}'", $comando);
        if ($empresa > 0)
            $comando = str_replace('/*PARAMETRO-EMPRESA*/', "AND empresa_id = '{$empresa}'", $comando);
        if ($contrato > 0)
            $comando = str_replace('/*PARAMETRO-CONTRATO*/', "AND contrato_id = '{$contrato}'", $comando);
        if ($funcionario > 0)
            $comando = str_replace('/*PARAMETRO-FUNCIONARIO*/', "AND funcionario_id = '{$funcionario}'", $comando);

        # CONSULTAS DENTRO DO FILTRO
        $consultas = $Cnx->fetchAll($comando);

        $convocacao = array();

        # PARA CADA CONSULTA...
        foreach ($consultas as $i => $consulta) {

            $data_referencia = $consulta['data_referencia'];

            # EXAMES REALIZADOS NA CONSULTA
            $examesRealizados = explode(',', $consulta['produtos']);

            # OBTER EXAMES DO ITEM-PCMSO
            $comando = "CALL SpObterColecaoExameParaFuncaoDoPcmsoRecente({$consulta['fk_contrato_id']}, {$consulta['fk_empresa_id']}, {$consulta['fk_cargo_id']}, {$consulta['fk_funcao_id']}, {$consulta['fk_setor_id']}, {$consulta['fk_tipoexame_id']})";
            $Comando = $Cnx->query($comando);
            $examesDoPCMSO = $Comando->fetchAll();
            $Comando->closeCursor();

            # PARA CADA EXAME NO ITEM-PCMSO...
            $consulta['retorno'] = array();
            foreach ($examesDoPCMSO as $exameDoPCMSO) {

                # SE O EXAME FOI REALIZADO...
                if (in_array($exameDoPCMSO['produto_sigla'], $examesRealizados)) {

                    # COLETA INFORMAÇÕES
                    $pcmsoPeriodo = substr(strtoupper($exameDoPCMSO['periodo_nome']), 0, 3);

                    if (!isset($consulta['retorno'][$pcmsoPeriodo]))
                        $consulta['retorno'][$pcmsoPeriodo] = array();
                    $consulta['retorno'][$pcmsoPeriodo][] = $exameDoPCMSO['produto_nome'];
                }
            }

            $consulta['retorno']['datas'] = array();

            if (isset($consulta['retorno']['SEM']))
                $consulta['retorno']['datas']['SEM'] = Util::DateAddInterval($data_referencia, '6 MONTH');
            if (isset($consulta['retorno']['ANU']))
                $consulta['retorno']['datas']['ANU'] = Util::DateAddInterval($data_referencia, '1 YEAR');
            if (isset($consulta['retorno']['BIE']))
                $consulta['retorno']['datas']['BIE'] = Util::DateAddInterval($data_referencia, '2 YEAR');
            if (isset($consulta['retorno']['TRI']))
                $consulta['retorno']['datas']['TRI'] = Util::DateAddInterval($data_referencia, '3 YEAR');


            $data1 = Util::dataBD($data1);
            $data2 = Util::dataBD($data2);

            if (($consulta['possui_audio_pos'] == '1' && Util::DateBetweenInterval($consulta['audio_pos'], $data1, $data2))) :
                $consulta['data_compativel'] = 'AUD';
                $convocacao[] = $consulta;
            elseif (isset($consulta['retorno']['datas']['SEM']) && Util::DateBetweenInterval($consulta['retorno']['datas']['SEM'], $data1, $data2)):
                $consulta['data_compativel'] = 'SEM';
                $convocacao[] = $consulta;
            elseif (isset($consulta['retorno']['datas']['ANU']) && Util::DateBetweenInterval($consulta['retorno']['datas']['ANU'], $data1, $data2)):
                $consulta['data_compativel'] = 'ANU';
                $convocacao[] = $consulta;
            elseif (isset($consulta['retorno']['datas']['BIE']) && Util::DateBetweenInterval($consulta['retorno']['datas']['BIE'], $data1, $data2)):
                $consulta['data_compativel'] = 'BIE';
                $convocacao[] = $consulta;
            elseif (isset($consulta['retorno']['datas']['TRI']) && Util::DateBetweenInterval($consulta['retorno']['datas']['TRI'], $data1, $data2)):
                $consulta['data_compativel'] = 'TRI';
                $convocacao[] = $consulta;
            endif;
        }
        return array('info' => $filtros, 'list' => $convocacao);
    }

}
