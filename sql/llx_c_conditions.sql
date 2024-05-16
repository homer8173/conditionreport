
--
-- Structure de la table `llx_c_conditions`
--

CREATE TABLE `llx_c_conditions` (
  `rowid` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `color` varchar(9) NOT NULL DEFAULT '#000000',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

--
-- Index pour la table `llx_c_conditions`
--
ALTER TABLE `llx_c_conditions`
  ADD PRIMARY KEY (`rowid`);

--
-- AUTO_INCREMENT pour la table `llx_c_conditions`
--
ALTER TABLE `llx_c_conditions`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT;
--
-- Déchargement des données de la table `llx_c_conditions`
--

INSERT INTO `llx_c_conditions` (`rowid`, `label`, `color`, `position`, `active`) VALUES
(1, 'BadCondition', '#FF0000', 1, 1),
(2, 'PoorCondition', '#FFA500', 2, 1),
(3, 'GoodCondition', '#BFBF00', 3, 1),
(4, 'ExcellentCondition', '#00FF00', 4, 1);


