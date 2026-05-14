<?php

class IndexController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {
        $atributos = $_SESSION;
        $atributos["produtoContratado"] = array();

        try {
            // Resgata o endereço da empresa
            $e = new Application_Model_Endereco();
            $enderecoId = $atributos['empresa']['fk_endereco_id'];
            $enderecoEmpresa = $e->fetchRow("endereco_id = $enderecoId")->toArray();
            $atributos['empresa']['endereco'] = $enderecoEmpresa;

            // Resgata a vigência do contrato
            $v = new Application_Model_Vigencia();
            $vigenciaContrato = $v->fetchRow("fk_contrato_id = {$atributos['contrato_id']} AND vigencia_status = 0");
            $atributos['contrato']['vigencia'] = (!is_null($vigenciaContrato) && count($vigenciaContrato) > 0) ? $vigenciaContrato : Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('vigencia');
            $atributos['contrato']['vigencia']['vigencia_data_inicio'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_inicio']);
            $atributos['contrato']['vigencia']['vigencia_data_fim'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_fim']);

            // Resgata os produtos contratados do contrato
            $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
            $filtros = " produto_contratado.produto_contratado_status = 0";
            $filtros .= " AND os.fk_contrato_id = {$contratoId}";
            $proCon = new Application_Model_ProdutoContratado();
            $resultadoProCon = $proCon->buscarCompletoUsandoClausula($filtros, "produto_nome ASC");
            $atributos["produtoContratado"] = $resultadoProCon;

            $contrato = new Application_Model_Empresa();
            $contato = $contrato->listarContatoHiest($contratoId);

            $ModelUserPortal = new Application_Model_UsuarioPortal();
            $termos = $ModelUserPortal->obterTermos($contratoId);

            $ModelAloc = new Application_Model_Alocacao();
            $qtdAloc = $ModelAloc->dadosAlocacao($contratoId);

            $ModelFatura = new Application_Model_Fatura();
            $faturastatus = $ModelFatura->inadimplecia($_SESSION['empresa']['empresa_cnpj']);
            $_SESSION['inadimplecia'] = $faturastatus;

            $f = new Application_Model_Funcionario();
            $FuncAtivos = 0;
            $where = " WHERE list.func_status IN('ATIVO')";
            $ResAtv = $f->RelacFunc($where);
            $FuncAtivos = count($ResAtv);

            $PerAvencer = 0;
            $where = " WHERE list.func_status IN('ATIVO') AND list.periodico_status LIKE '%A vencer%'";
            $ResAvenc = $f->RelacFunc($where);
            $PerAvencer = count($ResAvenc);

            $PerVencido = 0;
            $where = " WHERE list.func_status IN('ATIVO') AND list.periodico_status LIKE '%Vencido%'";
            $ResVenc = $f->RelacFunc($where);
            $PerVencido = count($ResVenc);

            $ultimosExames = $this->_resgatarUltimosExames(
                (int) $atributos['empresa']['empresa_id'],
                (int) $atributos['contrato_id'],
                10
            );
            $agendaIds = array();
            foreach ($ultimosExames as $exameRow) {
                if (isset($exameRow['agenda_id'])) {
                    $agendaIds[] = (int) $exameRow['agenda_id'];
                }
            }
            $anexosPorAgenda = $this->_resgatarAnexosDasAgendas($agendaIds);

        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }

        $this->view->faturastatus = $faturastatus;
        $this->view->qtd = count($qtdAloc);
        $this->view->termos = $termos;
        $this->view->atributos = $atributos;
        $this->view->contato = @$contato;
        $this->view->perAvencer = $PerAvencer;
        $this->view->perVencido = $PerVencido;
        $this->view->funcAtivos = $FuncAtivos;
        $this->view->ultimosExames = isset($ultimosExames) ? $ultimosExames : array();
        $this->view->anexosPorAgenda = isset($anexosPorAgenda) ? $anexosPorAgenda : array();
    }

    /**
     * Últimos exames realizados pelos colaboradores do escopo (empresa+contrato).
     * Filtra agendas cujo prontuário tem `anx_proc` apontando pra `arquivo_upload`
     * — ou seja, exame "concluído" com algum anexo gravado (ASO assinado pelo
     * dashsiss ou exame/laudo subido via Acervo Digital). Dedupe por agenda via
     * EXISTS subquery.
     */
    private function _resgatarUltimosExames($empresaId, $contratoId, $limit) {
        if ($empresaId <= 0 || $contratoId <= 0) {
            return array();
        }
        $limit = max(1, min(100, (int) $limit));
        $sql = "
            SELECT
                a.agenda_id,
                p.pessoa_nome,
                DATE(COALESCE(a.agenda_data_hora_presenca_exame, a.agenda_data_exame)) AS data_exame,
                te.tipoexame_nome
            FROM agenda a
            JOIN tipoexame te ON te.tipoexame_id = a.fk_tipoexame_id
            JOIN pessoa p ON p.pessoa_id = a.fk_pessoa_id
            WHERE a.fk_empresa_id = ?
              AND a.fk_contrato_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM prontuario pr
                  JOIN procedimento proc ON proc.fk_prontuario_id = pr.prontuario_id
                  JOIN anx_proc ap ON ap.fk_procedimento_id = proc.procedimento_id
                                  AND ap.anx_proc_status = 0
                  JOIN arquivo_upload au ON au.arquivo_upload_id = ap.fk_arquivo_upload_id
                                        AND au.arquivo_upload_status = 0
                  WHERE pr.fk_agenda_exame_id = a.agenda_id
              )
            ORDER BY data_exame DESC
            LIMIT {$limit}
        ";
        try {
            $cnx = Zend_Db_Table::getDefaultAdapter();
            $stmt = $cnx->prepare($sql);
            $stmt->execute(array($empresaId, $contratoId));
            $rows = $stmt->fetchAll();
            return is_array($rows) ? $rows : array();
        } catch (Exception $exc) {
            error_log('IndexController _resgatarUltimosExames falhou: ' . $exc->getMessage());
            return array();
        }
    }

    /**
     * Carrega os anexos (linhas de `arquivo_upload` ligadas via `anx_proc`) das
     * agendas listadas em "Últimos exames realizados". Retorna mapa indexado por
     * `agenda_id`. Agenda sem anexo não aparece no mapa.
     *
     * Os IDs vêm do próprio controller (saída de _resgatarUltimosExames), nunca
     * do request — sem risco de IDOR pela entrada.
     */
    private function _resgatarAnexosDasAgendas(array $agendaIds) {
        $agendaIds = array_values(array_filter(array_map('intval', $agendaIds), function ($id) {
            return $id > 0;
        }));
        if (count($agendaIds) === 0) {
            return array();
        }
        $placeholders = implode(',', array_fill(0, count($agendaIds), '?'));
        $sql = "
            SELECT
                pr.fk_agenda_exame_id AS agenda_id,
                au.arquivo_upload_id,
                au.arquivo_upload_descricao,
                au.arquivo_upload_extensao,
                ap.anx_proc_tipo
            FROM prontuario pr
            JOIN procedimento proc ON proc.fk_prontuario_id = pr.prontuario_id
            JOIN anx_proc ap ON ap.fk_procedimento_id = proc.procedimento_id
                            AND ap.anx_proc_status = 0
            JOIN arquivo_upload au ON au.arquivo_upload_id = ap.fk_arquivo_upload_id
                                  AND au.arquivo_upload_status = 0
            WHERE pr.fk_agenda_exame_id IN ({$placeholders})
            ORDER BY pr.fk_agenda_exame_id, au.arquivo_upload_id
        ";
        try {
            $cnx = Zend_Db_Table::getDefaultAdapter();
            $stmt = $cnx->prepare($sql);
            $stmt->execute($agendaIds);
            $rows = $stmt->fetchAll();
            $byAgenda = array();
            foreach ($rows as $r) {
                $aid = (int) $r['agenda_id'];
                if (!isset($byAgenda[$aid])) {
                    $byAgenda[$aid] = array();
                }
                $byAgenda[$aid][] = array(
                    'id' => (int) $r['arquivo_upload_id'],
                    'descricao' => $r['arquivo_upload_descricao'],
                    'extensao' => $r['arquivo_upload_extensao'],
                    'tipo' => $r['anx_proc_tipo'],
                );
            }
            return $byAgenda;
        } catch (Exception $exc) {
            error_log('IndexController _resgatarAnexosDasAgendas falhou: ' . $exc->getMessage());
            return array();
        }
    }

}
