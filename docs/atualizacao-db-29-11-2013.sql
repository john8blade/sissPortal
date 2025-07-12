CREATE  TABLE IF NOT EXISTS `capacidade_atendimento` (
  `capacidade_atendimento_id` INT NOT NULL AUTO_INCREMENT ,
  `capacidade_atendimento_quantidade` INT NOT NULL ,
  `capacidade_atendimento_status` INT NOT NULL DEFAULT 0 COMMENT 'PRE' ,
  `capacidade_atendimento_tipo` ENUM('PRESENCIAL', 'ONLINE') NOT NULL DEFAULT 'PRESENCIAL' COMMENT 'PRESENCIAL - Quando é feito pela HIEST. ONLINE - Quando é feito via portal pela empresa' ,
  `capacidade_atendimento_observacao` TEXT NULL DEFAULT NULL ,
  `fk_unidade_id` INT NOT NULL ,
  PRIMARY KEY (`capacidade_atendimento_id`) ,
  INDEX `fk_capacidade_atendimento_unidade1_idx` (`fk_unidade_id` ASC) ,
  CONSTRAINT `fk_capacidade_atendimento_unidade1`
    FOREIGN KEY (`fk_unidade_id` )
    REFERENCES `unidade` (`unidade_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


CREATE  TABLE IF NOT EXISTS `usuario_portal` (
  `idusuario_portal_id` INT NOT NULL AUTO_INCREMENT ,
  `usuario_portal_login` VARCHAR(60) NOT NULL ,
  `usuario_portal_senha_padrao` VARCHAR(45) NOT NULL ,
  `usuario_portal_senha_alternativa` VARCHAR(45) NOT NULL COMMENT 'Senha alternativa para acesso especial dos profissinais de TI.' ,
  `usuario_portal_status` INT NOT NULL DEFAULT 0 COMMENT '0:ativo;1:Inativo;2:Excluido;3:Bloqueado' ,
  `usuario_portal_email_contato` VARCHAR(50) NOT NULL ,
  `usuario_portal_data_primeiro_acesso` DATE NULL ,
  `contrato_contrato_id` INT NOT NULL ,
  PRIMARY KEY (`idusuario_portal_id`) ,
  UNIQUE INDEX `usuario_portal_login_UNIQUE` (`usuario_portal_login` ASC) ,
  INDEX `fk_usuario_portal_contrato1_idx` (`contrato_contrato_id` ASC) ,
  CONSTRAINT `fk_usuario_portal_contrato1`
    FOREIGN KEY (`contrato_contrato_id` )
    REFERENCES `contrato` (`contrato_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


CREATE  TABLE IF NOT EXISTS `dias_sem_atendimento` (
  `dias_sem_atendimento_id` INT NOT NULL AUTO_INCREMENT ,
  `dias_sem_atendimento_data` DATE NOT NULL ,
  `dias_sem_atendimento_dia` VARCHAR(2) NULL ,
  `dias_sem_atendimento_mes` VARCHAR(2) NULL ,
  `dias_sem_atendimento_ano` VARCHAR(4) NULL ,
  `dias_sem_atendimento_motivo` ENUM('NACIONAL', 'MUNICIPAL', 'ESTADUAL', 'TRABALHO_INTERNO', 'FACULTATIVO', 'OUTRO') NOT NULL DEFAULT 'TRABALHO_INTERNO' ,
  `dias_sem_atendimento_status` INT NOT NULL DEFAULT 0 ,
  `dias_sem_atendimento_descricao` TEXT NULL ,
  `unidade_unidade_id` INT NULL COMMENT 'Este atribuito só não receberá valor se o atributo dias_sem_atendimento_motivo for igual a NACIONAL, isto significa que devido o motivo ser um feriado nacional, desta maneira  todas as unidades não trabalham.' ,
  PRIMARY KEY (`dias_sem_atendimento_id`) ,
  INDEX `fk_dias_sem_atendimento_unidade1_idx` (`unidade_unidade_id` ASC) ,
  CONSTRAINT `fk_dias_sem_atendimento_unidade1`
    FOREIGN KEY (`unidade_unidade_id` )
    REFERENCES `unidade` (`unidade_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

ALTER TABLE  `funcionario` ADD  `funcionario_forma_insercao` ENUM(  'ONLINE',  'PRESENCIAL' ) NOT NULL DEFAULT  'PRESENCIAL' AFTER  `funcionario_matricula`;
ALTER TABLE  `agenda` ADD  `agenda_inserida_via` ENUM(  'ONLINE',  'PRESENCIAL' ) NOT NULL DEFAULT  'PRESENCIAL' AFTER  `agenda_presente_clinico`;
ALTER TABLE  `usuario_portal` DROP FOREIGN KEY  `fk_usuario_portal_contrato1`;
ALTER TABLE  `usuario_portal` CHANGE  `contrato_contrato_id`  `fk_contrato_id` INT( 11 ) NOT NULL;
ALTER TABLE  `usuario_portal` ADD INDEX (  `fk_contrato_id` );
ALTER TABLE `usuario_portal` ADD FOREIGN KEY (`fk_contrato_id`) REFERENCES `contrato`(`contrato_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE  `usuario_portal` CHANGE  `idusuario_portal_id`  `usuario_portal_id` INT( 11 ) NOT NULL AUTO_INCREMENT;
ALTER TABLE  `dias_sem_atendimento` DROP FOREIGN KEY  `fk_dias_sem_atendimento_unidade1` ;
ALTER TABLE  `dias_sem_atendimento` CHANGE  `unidade_unidade_id`  `fk_unidade_id` INT( 11 ) NULL DEFAULT NULL COMMENT 'Este atribuito só não receberá valor se o atributo dias_sem_atendimento_motivo for igual a NACIONAL, isto significa que devido o motivo ser um feriado nacional, desta maneira  todas as unidades não trabalham.';
ALTER TABLE  `dias_sem_atendimento` DROP INDEX  `fk_dias_sem_atendimento_unidade1_idx`;
ALTER TABLE  `dias_sem_atendimento` ADD INDEX (  `fk_unidade_id` );
ALTER TABLE  `dias_sem_atendimento` ADD FOREIGN KEY (  `fk_unidade_id` ) REFERENCES  `desenv_otimizar`.`unidade` (
`unidade_id`
) ON DELETE NO ACTION ON UPDATE NO ACTION ;










