CREATE TABLE `booktags` (
  `BtBkID` smallint unsigned NOT NULL,
  `BtT2ID` smallint unsigned NOT NULL,
  PRIMARY KEY (`BtBkID`,`BtT2ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;


insert into booktags (BtBkID, BtT2ID)
select TtTxID, TtT2ID from texttags;
