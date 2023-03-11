CREATE TABLE `tagsforbooks` (
  `T4BID` smallint unsigned NOT NULL AUTO_INCREMENT,
  `T4BText` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `T4BComment` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`T4BID`),
  UNIQUE KEY `T4BText` (`T4BText`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;


insert into tagsforbooks (T4BID, T4BText, T4BComment)
select T2ID, T2Text, T2Comment
from tags;


CREATE TABLE `booktags` (
  `BtBkID` smallint unsigned NOT NULL,
  `BtT4BID` smallint unsigned NOT NULL,
  PRIMARY KEY (`BtTxID`,`TtT2ID`),
  KEY `BtT4BID` (`BtT4BID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3


insert into booktags (BtBkID, BtT4BID)
select TtTxID, TtT2ID from texttags;
