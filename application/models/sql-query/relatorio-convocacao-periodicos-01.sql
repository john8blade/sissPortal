SELECT
    sub1.fk_contrato_id
    ,sub1.fk_empresa_id
    ,sub1.fk_cargo_id
    ,sub1.fk_funcao_id
    ,sub1.fk_setor_id
    ,sub1.fk_tipoexame_id
    ,sub1.funcionario_id
    ,sub1.empresa_id
    ,sub1.empresa_fantasia
    ,sub1.pessoa_nome
    ,sub1.pessoa_cpf
    ,sub1.cargo_nome
    ,sub1.tipoexame_sigla
    ,sub1.tipoexame_nome
    ,sub1.data_referencia
    ,IF(sub1.fichamedica_resultado_aptidao=1,'APTO','INAPTO') AS aptidao
    ,sub1.produtos
    ,sub1.possui_audio_pos
    ,IF(sub1.tipoexame_sigla='ADM' AND (sub1.produtos LIKE '%AUD%' OR sub1.produtos LIKE '%AAAE%' OR sub1.produtos LIKE '%AABE%'),DATE_ADD(sub1.data_referencia,INTERVAL 180 DAY),NULL) AS audio_pos
FROM contrato co
    JOIN (SELECT
            fu.funcionario_id
            ,em.empresa_id
            ,pe.pessoa_nome
            ,pe.pessoa_cpf
            ,em.empresa_fantasia
            ,ca.cargo_nome
            ,sub2.fk_empresa_id
            ,sub2.fk_contrato_id
            ,al.fk_cargo_id
            ,al.fk_funcao_id
            ,al.fk_setor_id
            ,sub2.fk_tipoexame_id
            ,sub2.data_referencia
            ,sub2.fichamedica_resultado_aptidao
            ,sub2.tipoexame_sigla
            ,sub2.tipoexame_nome
            ,sub2.produtos
            ,(SELECT ip.item_pcmso_possui_audio_pos
                FROM item_pcmso ip
                WHERE ip.fk_ghe_id = al.fk_ghe_id
                    AND ip.fk_cargo_id = al.fk_cargo_id
                    AND ip.fk_setor_id = al.fk_setor_id
                    AND ip.fk_funcao_id = al.fk_funcao_id
                    AND ip.item_pcmso_status = 0
                    AND ip.fk_pcmso_id = (SELECT pc.pcmso_id FROM pcmso pc
                        WHERE pc.pcmso_status = 0
                            AND pc.fk_empresa_id = sub2.fk_empresa_id
                            AND pc.fk_contrato_id = sub2.fk_contrato_id
                        ORDER BY pc.pcmso_data_validade DESC
                        LIMIT 1)
            LIMIT 1) AS possui_audio_pos
        FROM funcionario fu
            JOIN pessoa pe ON pe.pessoa_id = fu.fk_pessoa_id
            JOIN empresa em ON em.empresa_id = fu.fk_empresa_id AND em.empresa_status = 0
            JOIN alocacao al ON al.fk_funcionario_id = fu.funcionario_id AND al.fk_empresa_id = em.empresa_id
            JOIN cargo ca ON ca.cargo_id = al.fk_cargo_id
            JOIN setor se ON se.setor_id = al.fk_setor_id
            JOIN funcao fn ON fn.funcao_id = al.fk_funcao_id
            JOIN ghe gh ON gh.ghe_id = al.fk_ghe_id
            JOIN (SELECT
                    ag.agenda_data_clinico AS data_referencia
                    ,fm.fichamedica_resultado_aptidao
                    ,te.tipoexame_sigla
                    ,te.tipoexame_nome
                    ,ag.fk_tipoexame_id
                    ,ag.fk_pessoa_id
                    ,ag.fk_empresa_id
                    ,ag.fk_contrato_id
                    ,(SELECT GROUP_CONCAT(pr.produto_sigla)
                        FROM produto_agenda pa
                        JOIN produto pr ON pr.produto_id = pa.fk_produto_id
                        WHERE pa.fk_agenda_id = ag.agenda_id AND pa.produto_agenda_status = 0) AS produtos
                FROM fichamedica fm
                    JOIN agenda ag ON ag.agenda_id = fm.fk_agenda_id AND ag.agenda_presente_exame = 1 AND ag.agenda_status = 0
                    JOIN tipoexame te ON te.tipoexame_id = ag.fk_tipoexame_id
                WHERE fm.fichamedica_status = 0
            ) AS sub2 ON sub2.fk_pessoa_id = pe.pessoa_id
        WHERE (fu.funcionario_motivo_inativacao IS NULL OR fu.funcionario_motivo_inativacao = '') AND fu.funcionario_status = 0
        ORDER BY pe.pessoa_nome ASC
    ) AS sub1 ON sub1.fk_contrato_id = co.contrato_id
WHERE 1
    /*PARAMETRO-SETOR*/
    /*PARAMETRO-EMPRESA*/
    /*PARAMETRO-CONTRATO*/
    /*PARAMETRO-FUNCIONARIO*/
ORDER BY sub1.empresa_fantasia, sub1.pessoa_nome ASC
LIMIT 999