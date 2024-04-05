--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--
INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES(NULL, 'standard_conditionreportroom', 0, 'conditionreportroom', 'Etat des lieux d\'une pièce', NULL);
INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES(NULL, 'standard_conditionreport', 0, 'conditionreport', 'Etat des lieux', NULL);