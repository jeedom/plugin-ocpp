CREATE TABLE IF NOT EXISTS `ocpp_transaction` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `transactionId` int(20) NOT NULL UNIQUE,
  `eqLogicId` int(11) NOT NULL,
  `connectorId` INTEGER NOT NULL CHECK(`connectorId` BETWEEN 1 AND 4),
  `tagId` varchar(50) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime,
  `options` varchar(250)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
