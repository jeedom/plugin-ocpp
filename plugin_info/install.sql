CREATE TABLE IF NOT EXISTS `ocppTransaction` (
  `transactionId` int(20) PRIMARY KEY,
  `cpId` varchar(50) NOT NULL,
  `connectorId` INTEGER NOT NULL CHECK(`connectorId` BETWEEN 1 AND 4),
  `tagId` varchar(50) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime,
  `options` varchar(250)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
