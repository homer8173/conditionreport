
--
-- Structure de la table `llx_c_type_heater`
--

CREATE TABLE `llx_c_type_heater` (
  `rowid` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `llx_c_type_heater`
--

INSERT INTO `llx_c_type_heater` (`rowid`, `code`, `label`, `active`) VALUES
(1, 'ELEC', 'Électricité', 1),
(2, 'GAZNAT', 'Gaz Naturel', 1),
(3, 'GAZPROP', 'Gaz Propane', 1),
(4, 'GAZBUT', 'Gaz Butane', 1),
(5, 'COLLECT', 'Collectif', 1),
(6, 'FIOUL', 'Fioul', 1),
(7, 'OTHER', 'Autre', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `llx_c_type_heater`
--
ALTER TABLE `llx_c_type_heater`
  ADD PRIMARY KEY (`rowid`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `llx_c_type_heater`
--
ALTER TABLE `llx_c_type_heater`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;
