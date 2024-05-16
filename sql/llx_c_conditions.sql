
--
-- Structure de la table `llx_c_conditions`
--

CREATE TABLE `llx_c_conditions` (
  `rowid` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour la table `llx_c_conditions`
--
ALTER TABLE `llx_c_conditions`
  ADD PRIMARY KEY (`rowid`);
ALTER TABLE `llx_c_conditions`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT;
--
--  données de la table `llx_c_conditions`
--

INSERT INTO `llx_c_conditions` (`rowid`, `label`, `position`, `active`) VALUES
(1, 'BadCondition', 1, 1),
(2, 'PoorCondition', 2, 1),
(3, 'GoodCondition', 3, 1),
(4, 'ExcellentCondition', 4, 1);
