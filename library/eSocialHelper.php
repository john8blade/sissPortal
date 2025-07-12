<?php

class eSocialHelper {

    const MAXIMO_EVENTOS_LOTE     = 50;
    const NAMESPACE_EVT_MONIT     = 'http://www.esocial.gov.br/schema/evt/evtMonit/v_S_01_02_00';
    const NAMESPACE_EVT_EXP_RISCO = 'http://www.esocial.gov.br/schema/evt/evtExpRisco/v_S_01_02_00';
    const NAMESPACE_EVT_CAT       = 'http://www.esocial.gov.br/schema/evt/evtCAT/v_S_01_02_00';

    /*
     as constantes abaixo seguem a mesma PK da evento na tabela de
     esocial_tipoevento 
    */
    const EVT_S2220 = 1;
    const EVT_S2240 = 2;
    const EVT_S2210 = 3;

    const URL_PROD_RESTRITA = 'https://webservices.producaorestrita.esocial.gov.br/servicos/empregador/enviarloteeventos/WsEnviarLoteEventos.svc?wsdl';
    const URL_PROD = 'https://webservices.envio.esocial.gov.br/servicos/empregador/enviarloteeventos/WsEnviarLoteEventos.svc?wsdl';

/**/ static $rawXml = <<< EOF
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://www.esocial.gov.br/servicos/empregador/lote/eventos/envio/v1_1_0">
        <soapenv:Header/>
        <soapenv:Body>
        <v1:EnviarLoteEventos>
            <v1:loteEventos>
                <eSocial xmlns="http://www.esocial.gov.br/schema/lote/eventos/envio/v1_1_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <envioLoteEventos grupo="3">
                        <ideEmpregador>
                            <tpInsc>1</tpInsc>
                                <nrInsc>39389887</nrInsc>
                            </ideEmpregador>
                            <ideTransmissor>
                                <tpInsc>1</tpInsc>
                                <nrInsc>03070274000307</nrInsc>
                            </ideTransmissor>
                            <eventos>
                                
                                    <eSocial xmlns="http://www.esocial.gov.br/schema/lote/eventos/envio/v1_1_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                        <evtMonit>
                                            <Id>36</Id>
                                            <ideEvento>
                                                <indRetif>1</indRetif>
                                                <nrRecibo/>
                                                <procEmi>3</procEmi>
                                                <verProc>SISSv1.0</verProc>
                                                <tpAmb>2</tpAmb>
                                            </ideEvento>
                                            <ideEmpregador>
                                                <tpInsc>1</tpInsc>
                                                <nrInsc>00000000000000</nrInsc>
                                            </ideEmpregador>
                                            <ideVinculo>
                                                <cpfTrab>17360089786</cpfTrab>
                                                <matricula>001</matricula>
                                                <codCateg/>
                                            </ideVinculo>
                                            <exMedOcup>
                                                <tpExameOcup>0</tpExameOcup>

                                                <aso>
                                                    <dtAso>23/11/2021</dtAso>
                                                    <resAso>1</resAso>

                                                    <exame>
                                                        <dtExm>20/11/2021</dtExm>
                                                        <procRealizado>9999</procRealizado>
                                                        <obsProc>lorem ipsum dolor</obsProc>
                                                        <ordExame></ordExame>
                                                        <indResult>1</indResult>
                                                    </exame>

                                                    <medico>
                                                        <nmMedico>DOUTOR LOREM IPSUM</nmMedico>
                                                        <nrCRM>123</nrCRM>
                                                        <ufCRM>ES</ufCRM>
                                                    </medico>

                                                    <respMonit>
                                                        <cpfResp>17360089786</cpfResp>
                                                        <nmResp>DOUTOR LOREM IPSUM</nmResp>
                                                        <nrCRM>123</nrCRM>
                                                        <ufCRM>ES</ufCRM>
                                                    </respMonit>
                                                </aso>
                                            </exMedOcup>
                                            <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
                                                <SignedInfo>
                                                    <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
                                                    <SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
                                                    <Reference URI="">
                                                        <Transforms>
                                                            <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
                                                            <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
                                                        </Transforms>
                                                        <DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                                        <DigestValue></DigestValue>
                                                    </Reference>
                                                </SignedInfo>
                                                <SignatureValue></SignatureValue>
                                                <KeyInfo>
                                                    <X509Data>
                                                        <X509Certificate></X509Certificate>
                                                    </X509Data>
                                                </KeyInfo>
                                            </Signature>
                                        </evtMonit>
                                    </eSocial>
                                </evento>
                            </eventos>
                        </envioLoteEventos>
                    </eSocial>
            </v1:loteEventos>
        </v1:EnviarLoteEventos>
    </soapenv:Body>
    </soapenv:Envelope>
EOF;

/**/ static $xmlHead = <<< EOF
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://www.esocial.gov.br/servicos/empregador/lote/eventos/envio/v1_2_0">
    <soapenv:Header/>
    <soapenv:Body>
        <v1:EnviarLoteEventos>
            <v1:loteEventos>
                <eSocial xmlns="http://www.esocial.gov.br/schema/lote/eventos/envio/v1_2_1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <envioLoteEventos grupo="3">
EOF;

/**/ static $xmlTails = <<< EOF
                        </envioLoteEventos>
                    </eSocial>
                </v1:loteEventos>
            </v1:EnviarLoteEventos>
        </soapenv:Body>
    </soapenv:Envelope>
EOF;

    static $identificador = [
        'ideEmpregador' => [
            'tpInsc' => '1',
            'nrInsc' => '',
        ],'ideTransmissor' => [
            'tpInsc' => '1',
            'nrInsc' => '03070274000307',
        ]
    ];

    static $S2210 = [
       'evtCAT' => [

            'Id' => '1',

            'ideEvento' => [
               'indRetif' => 1,
               'nrRecibo' => '',
               'tpAmb'    => 1,
               'procEmi'  => 1,
               'verProc'  => 'v_S_01_02_00'
            ],

            'ideEmpregador' => [
               'tpInsc' => 1,    //escolhendo CNPJ
               'nrInsc' => ''    // informar o cnpj
            ],

            'ideVinculo' => [
                'cpfTrab'   => '',
                'matricula' => '',
                'codCateg'   => '',
            ],

            'cat' => [
                'dtAcid' => '',
                'tpAcid' => '',
                 /* tpAcid
                 1-Típica,
                 2-Doença,
                 3-Trajeto
                 */
                'hrAcid' => '',
                'hrsTrabAntesAcid' => '',
                'tpCat' => '',
                 /* tpCat
                 1-Inicial,
                 2-Reabertura,
                 3-Comunicação de óbito
                 */
                'indCatObito' => '',
                 /* indCatObito
                 S-Sim,
                 N-Não
                 */
                'dtObito' => '',
                'indComunPolicia' => '',
                 /* indComunPolicia
                 S-Sim,
                 N-Não
                 */
                'codSitGeradora' => '', // Tabela 15/9char
                'iniciatCAT' => '',
                 /* iniciatCAT
                 1-Empregador,
                 2-Ordem judicial,
                 3-Determinação de órgão fiscalizador
                 */
                'obsCAT' => '', // 1-999char
                'ultDiaTrab' => '', // dtAcid >= [2023-01-16])
                'houveAfast' => '', 
                /* houveAfast
                 S-Sim,
                 N-Não
                 */

                'localAcidente' => [
                    'tpLocal' => '',
                     /* tpLocal
                     1-Estabelecimento do empregador no Brasil, 
                     2-Estabelecimento do empregador no Exterior,
                     3-Estabelecimento de terceiros onde o empregador presta serviços,
                     4-Via pública,
                     5-Área rural,
                     6-Embarcação,
                     9-Outros
                     */
                    'dscLocal' => '',
                    'tpLograd' => '', // Tabela 20/1-4char
                    'dscLograd' => '',
                    'nrLograd' => '',
                    'complemento' => '',
                    'bairro' => '',
                    'cep' => '',
                    'codMunic' => '',
                    'uf' => '',
                    'pais' => '', // Tabela 06/3char
                    'codPostal' => '',

                    'ideLocalAcid' => [
                        'tpInsc'        => '',
                         /* tpInsc
                         1-CNPJ,
                         3-CAEPF,
                         4-CNO
                         */
                        'nrInsc'        => '',
                    ],

                ],

                'parteAtingida' => [
                    'codParteAting' => '', // Tabela 13/9char
                    'lateralidade'  => '',
                     /* lateralidade
                     0-Não aplicável,
                     1-Esquerda,
                     2-Direita,
                     3-Ambas
                     */

                ],

                'agenteCausador' => [
                    'codAgntCausador' => '', // Tabela 14 ou 15/9char

                ],

                'atestado' => [
                    'dtAtendimento' => '',
                    'hrAtendimento' => '', 
                    'indInternacao' => '',
                     /* indInternacao
                     S-Sim,
                     N-Não
                     */ 
                    'durTrat' => '', 
                    'indAfast' => '',
                     /* indAfast
                     S-Sim,
                     N-Não
                     */
                    'dscLesao' => '', // Tabela 17/9char
                    'dscCompLesao' => '', 
                    'diagProvavel' => '', 
                    'codCID' => '', // Tabela CID/3-4char
                    'observacao' => '',

                    'emitente' => [
                        'nmEmit' => '',
                        'ideOC' => '',
                         /* ideOC
                         1-CRM,
                         2-CRO,
                         3-RMS
                         */
                        'nrOC' => '',
                        'ufOC' => '',
                    ],

                ],

                'catOrigem' => [
                    'nrRecCatOrig' => '',

                ],
            ],

        ] 
    ];

    static $S2220 = [
       'evtMonit' => [

            'Id' => '1',

            'ideEvento' => [
               'indRetif' => '',
               'nrRecibo' => '',
               'tpAmb'    => '',
               'procEmi'  => 1,
               'verProc'  => 'v_S_01_02_00'
            ],

            'ideEmpregador' => [
               'tpInsc' => 1,    //escolhendo CNPJ
               'nrInsc' => ''    // informar o cnpj
            ],

            'ideVinculo' => [
                'cpfTrab'   => '',
                'matricula' => '',
                'codCateg'   => '',
            ],

            'exMedOcup' => [
                'tpExameOcup' => '',
                /* ^ Tipo do exame médico ocupacional.
                 Valores válidos:
                 0 - Exame médico admissional
                 1 - Exame médico periódico, conforme Norma Regulamentadora 07 - NR-07 e/ou planejamento do Programa de Controle Médico de Saúde Ocupacional - PCMSO
                 2 - Exame médico de retorno ao trabalho
                 3 - Exame médico de mudança de função ou de mudança de risco ocupacional
                 4 - Exame médico de monitoração pontual, não enquadrado nos demais casos
                 9 - Exame médico demissional

                 Validação: Se informado [0], não pode existir outro evento
                 S-2220 para o mesmo contrato com dtAso anterior
                */

                'aso' => [
                    'dtAso' => '',
                    'resAso' => '',

                    'exame' => [
                        'dtExm'         => '',
                        'procRealizado' => '',
                        'obsProc'       => '',
                        'ordExame'      => '',
                        'indResult'     => ''
                    ],

                    'medico' => [
                        'nmMed' => '',
                        'nrCRM' => '',
                        'ufCRM' => ''
                    ],
                ],

                'respMonit' => [
                    'cpfResp' => '',
                    'nmResp'  => '',
                    'nrCRM'   => '',
                    'ufCRM'   => ''
                ]
            ],
        ] 
    ];

    static $S2240 = [
        'evtExpRisco' => [

            'Id' => '1',

            'ideEvento' => [
               'indRetif' => '1',
               'nrRecibo' => '',
               'tpAmb'    => '1',
               'procEmi'  => 1,
               'verProc'  => 'v_S_01_02_00'
            ],

            'ideEmpregador' => [
               'tpInsc' => 1,    //escolhendo CNPJ
               'nrInsc' => ''    // informar o cnpj
            ],

            'ideVinculo' => [
                'cpfTrab'   => '',
                'matricula' => '',
                'codCateg'   => '',
            ],

            'infoExpRisco' => [
                'dtIniCondicao' => '',

                'infoAmb' => [
                    'localAmb' => '',
                    'dscSetor' => '',
                    'tpInsc'   => '',
                    'nrInsc'   => ''
                ],

                'infoAtiv' => [
                    'dscAtivDes' => ''
                ],

                'agNoc' => [        // 1..=999
                    'codAgNoc'   => '',
                    'dscAgNoc'   => '',
                    'tpAval'     => '',
                    'intConc'    => '',
                    'limTol'     => '',
                    'unMed'      => '',
                    'tecMedicao' => '',
                    'epcEpi' => [
                        'utilizEPC' => '',
                        'eficEpc'   => '',
                        'utilizEPI' => '',
                        'eficEpi'   => '',

                        'epi'         => [  // 0..=50
                            'docAval' => '',
                            'dscEPI'  => '',
                        ],

                        'epiCompl' => [
                            'medProtecao'   => '',
                            'condFuncto'    => '',
                            'usoInint'       => '',
                            'przValid'      => '',
                            'periodicTroca' => '',
                            'higienizacao'  => '',
                        ]
                    ],
                ],

                'respReg' => [
                    'cpfResp' => '',
                    'ideOC'   => '',
                    'dscOC'   => '',
                    'nrOC'    => '',
                    'ufOC'    => ''
                ],

                'obs' => [
                    'obsCompl' => ''
                ]
            ],
        ]
    ];

    static $lote = [
        'eSocial xmlns="http://www.esocial.gov.br/schema/lote/eventos/envio/v1_2_0"' => [
            'envioLoteEventos grupo="3"' => [
                'ideEmpregador' => [
                    'tpInsc' => '',
                    'nrInsc' => ''
                ],

                'ideTransmissor' => [
                    'tpInsc' => '',
                    'nrInsc' => ''
                ],
                'eventos' => ''
            ],
        ]
    ];

    private static $signature ='<Signature xmlns="http://www.w3.org/2000/09/xmldsig"> <SignedInfo> <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n20010315" /> <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" /> <Reference URI=""> <Transforms> <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#envelopedsignature" /> <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /> </Transforms> <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" /> <DigestValue></DigestValue> </Reference> </SignedInfo> <SignatureValue></SignatureValue> <KeyInfo> <X509Data> <X509Certificate></X509Certificate> </X509Data> </KeyInfo> </Signature>';

    public static function obterXMLAssinatura() {
        return simplexml_load_string(self::$signature);
    }
    
    /* static $signature = [
            'SignedInfo' => [
                'CanonicalizationMethod Algorithm="http//www.w3.org/TR/2001/REC-xml-c14n20010315"' => '',
                'SignatureMethod Algorithm=""' => '',
                'Reference URI=""' => [
                    'Transforms' => [
                        'Transform Algorithm="http//www.w3.org/2000/09/xmldsig#envelopedsignature"' => '',
                        'Transform Algorithm="http//www.w3.org/TR/2001/REC-xml-c14n-20010315"' => '',
                    ],
                    'DigestMethod Algorithm="http//www.w3.org/2000/09/xmldsig#sha1"' => '',
                    'DigestValue' => '',
                ],
            ],
            'SignatureValue' => '',
            'KeyInfo' => [
                'X509Data' => [
                    'X509Certificate' => ''
                ]
            ]
        ];
    */
}