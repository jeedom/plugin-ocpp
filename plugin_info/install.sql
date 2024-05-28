CREATE TABLE IF NOT EXISTS `ocpp_transaction` (
  `transactionId` int(20) UNSIGNED NOT NULL UNIQUE PRIMARY KEY,
  `eqLogicId` int(11) UNSIGNED NOT NULL,
  `connectorId` INTEGER NOT NULL CHECK(`connectorId` BETWEEN 1 AND 4),
  `tagId` varchar(50) NOT NULL,
  `start` date NOT NULL,
  `end` date,
  `options` varchar(250)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
