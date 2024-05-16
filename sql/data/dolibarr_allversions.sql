--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--
INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES(NULL, 'standard_conditionreportroom', 0, 'conditionreportroom', 'Etat des lieux d\'une pièce', NULL);
INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES(NULL, 'standard_conditionreport', 0, 'conditionreport', 'Etat des lieux', NULL);



INSERT INTO `llx_c_email_templates` ( `entity`, `module`, `type_template`, `lang`, `private`, `fk_user`, `datec`, `label`, `position`, `enabled`, `active`, `email_from`, `email_to`, `email_tocc`, `email_tobcc`, `topic`, `joinfiles`, `content`, `content_lines`) VALUES
( 1, 'conditionreport', 'conditionreport', '', 0, NULL, NULL, 'Votre état des lieux', 1, '1', 1, NULL, NULL, NULL, NULL, 'Votre état des lieux', '1', 'Bonjour,<br />\r\nveuillez trouver ci joint votre &eacute;tat des lieux compl&eacute;t&eacute; et signer ce jour.<br />\r\nBien &agrave; vous,<br />\r\n__USER_SIGNATURE__&nbsp;', NULL),
( 1, 'conditionreport', 'conditionreport', NULL, 0, NULL, NULL, 'Signature en ligne', 2, '1', 1, NULL, NULL, NULL, NULL, 'Signature en ligne de l\'état des lieux', NULL, 'Bonjour,<br />\r\nmerci de bien vouloir signer votre &eacute;tat des lieux en ligne en utillisant le lien ci-dessous :<br />\r\n__ONLINE_SIGN_URL__&nbsp;<br />\r\n<br />\r\nBien &agrave; vous,<br />\r\n__USER_SIGNATURE__&nbsp;', NULL);
