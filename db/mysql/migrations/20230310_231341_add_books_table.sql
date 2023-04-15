-- Add "books"

CREATE TABLE `books` (
  `BkID` smallint unsigned NOT NULL AUTO_INCREMENT,
  `BkLgID` tinyint unsigned NOT NULL,
  `BkTitle` varchar(200) NOT NULL,
  `BkSourceURI` varchar(1000) DEFAULT NULL,
  `BkArchived` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`BkID`),
  KEY `BkLgID` (`BkLgID`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

-- modify texts table - add BkID, TxOrder
ALTER TABLE texts ADD COLUMN TxBkID int AFTER TxID;
ALTER TABLE texts ADD COLUMN TxOrder int AFTER TxBkID;

-- load books table - load with existing texts.
INSERT INTO books (BkID, BkLgID, BkTitle, BkSourceURI, BkArchived)
SELECT TxID, TxLgID, TxTitle, TxSourceURI, TxArchived
FROM texts;

-- Link texts to their book.
UPDATE texts
INNER JOIN books on BkID = TxID
SET TxBkID = BkID, TxOrder = 1;

-- alter texts, BkID not null, TxOrder not null
ALTER TABLE texts MODIFY COLUMN TxBkID int NOT NULL;
ALTER TABLE texts MODIFY COLUMN TxOrder int NOT NULL;

ALTER TABLE texts ADD CONSTRAINT TxBkIDTxOrder UNIQUE (TxBkID, TxOrder);

ALTER TABLE books ADD COLUMN BkCurrentTxID int NOT NULL DEFAULT 0;
