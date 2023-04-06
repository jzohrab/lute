CREATE TABLE `texttokens` (
  `TokTxID` smallint unsigned NOT NULL,
  `TokSentenceNumber` mediumint unsigned NOT NULL,
  `TokOrder` smallint unsigned NOT NULL,
  `TokIsWord` tinyint NOT NULL,
  `TokText` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`TokTxID`,`TokOrder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3
