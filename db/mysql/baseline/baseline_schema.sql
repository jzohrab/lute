-- Database dump generated with mysqldump

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `archivedtexts`
--

DROP TABLE IF EXISTS `archivedtexts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archivedtexts` (
  `AtID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `AtLgID` tinyint(3) unsigned NOT NULL,
  `AtTitle` varchar(200) NOT NULL,
  `AtText` text NOT NULL,
  `AtAnnotatedText` longtext NOT NULL,
  `AtAudioURI` varchar(200) DEFAULT NULL,
  `AtSourceURI` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`AtID`),
  KEY `AtLgID` (`AtLgID`),
  KEY `AtLgIDSourceURI` (`AtSourceURI`(20),`AtLgID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `archtexttags`
--

DROP TABLE IF EXISTS `archtexttags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archtexttags` (
  `AgAtID` smallint(5) unsigned NOT NULL,
  `AgT2ID` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`AgAtID`,`AgT2ID`),
  KEY `AgT2ID` (`AgT2ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feedlinks`
--

DROP TABLE IF EXISTS `feedlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedlinks` (
  `FlID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `FlTitle` varchar(200) NOT NULL,
  `FlLink` varchar(400) NOT NULL,
  `FlDescription` text NOT NULL,
  `FlDate` datetime NOT NULL,
  `FlAudio` varchar(200) NOT NULL,
  `FlText` longtext NOT NULL,
  `FlNfID` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`FlID`),
  UNIQUE KEY `FlTitle` (`FlNfID`,`FlTitle`),
  KEY `FlLink` (`FlLink`(333)),
  KEY `FlDate` (`FlDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `languages` (
  `LgID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `LgName` varchar(40) NOT NULL,
  `LgDict1URI` varchar(200) NOT NULL,
  `LgDict2URI` varchar(200) DEFAULT NULL,
  `LgGoogleTranslateURI` varchar(200) DEFAULT NULL,
  `LgExportTemplate` varchar(1000) DEFAULT NULL,
  `LgTextSize` int(5) unsigned NOT NULL DEFAULT '100',
  `LgCharacterSubstitutions` varchar(500) NOT NULL,
  `LgRegexpSplitSentences` varchar(500) NOT NULL,
  `LgExceptionsSplitSentences` varchar(500) NOT NULL,
  `LgRegexpWordCharacters` varchar(500) NOT NULL,
  `LgRemoveSpaces` tinyint(1) unsigned NOT NULL,
  `LgSplitEachChar` tinyint(1) unsigned NOT NULL,
  `LgRightToLeft` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`LgID`),
  UNIQUE KEY `LgName` (`LgName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newsfeeds`
--

DROP TABLE IF EXISTS `newsfeeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsfeeds` (
  `NfID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `NfLgID` tinyint(3) unsigned NOT NULL,
  `NfName` varchar(40) NOT NULL,
  `NfSourceURI` varchar(200) NOT NULL,
  `NfArticleSectionTags` text NOT NULL,
  `NfFilterTags` text NOT NULL,
  `NfUpdate` int(12) unsigned NOT NULL,
  `NfOptions` varchar(200) NOT NULL,
  PRIMARY KEY (`NfID`),
  KEY `NfLgID` (`NfLgID`),
  KEY `NfUpdate` (`NfUpdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sentences`
--

DROP TABLE IF EXISTS `sentences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sentences` (
  `SeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `SeLgID` tinyint(3) unsigned NOT NULL,
  `SeTxID` smallint(5) unsigned NOT NULL,
  `SeOrder` smallint(5) unsigned NOT NULL,
  `SeText` text,
  `SeFirstPos` smallint(5) NOT NULL,
  PRIMARY KEY (`SeID`),
  KEY `SeLgID` (`SeLgID`),
  KEY `SeTxID` (`SeTxID`),
  KEY `SeOrder` (`SeOrder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `StKey` varchar(40) NOT NULL,
  `StValue` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`StKey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `TgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `TgComment` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`TgID`),
  UNIQUE KEY `TgText` (`TgText`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags2`
--

DROP TABLE IF EXISTS `tags2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags2` (
  `T2ID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `T2Text` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `T2Comment` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`T2ID`),
  UNIQUE KEY `T2Text` (`T2Text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temptextitems`
--

DROP TABLE IF EXISTS `temptextitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temptextitems` (
  `TiCount` smallint(5) unsigned NOT NULL,
  `TiSeID` mediumint(8) unsigned NOT NULL,
  `TiOrder` smallint(5) unsigned NOT NULL,
  `TiWordCount` tinyint(3) unsigned NOT NULL,
  `TiText` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tempwords`
--

DROP TABLE IF EXISTS `tempwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tempwords` (
  `WoText` varchar(250) DEFAULT NULL,
  `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `WoTranslation` varchar(500) NOT NULL DEFAULT '*',
  `WoRomanization` varchar(100) DEFAULT NULL,
  `WoSentence` varchar(1000) DEFAULT NULL,
  `WoTaglist` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`WoTextLC`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `textitems2`
--

DROP TABLE IF EXISTS `textitems2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `textitems2` (
  `Ti2WoID` mediumint(8) unsigned NOT NULL,
  `Ti2LgID` tinyint(3) unsigned NOT NULL,
  `Ti2TxID` smallint(5) unsigned NOT NULL,
  `Ti2SeID` mediumint(8) unsigned NOT NULL,
  `Ti2Order` smallint(5) unsigned NOT NULL,
  `Ti2WordCount` tinyint(3) unsigned NOT NULL,
  `Ti2Text` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `Ti2Translation` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`Ti2TxID`,`Ti2Order`,`Ti2WordCount`),
  KEY `Ti2WoID` (`Ti2WoID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texts`
--

DROP TABLE IF EXISTS `texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts` (
  `TxID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `TxLgID` tinyint(3) unsigned NOT NULL,
  `TxTitle` varchar(200) NOT NULL,
  `TxText` text NOT NULL,
  `TxAnnotatedText` longtext NOT NULL,
  `TxAudioURI` varchar(200) DEFAULT NULL,
  `TxSourceURI` varchar(1000) DEFAULT NULL,
  `TxPosition` smallint(5) NOT NULL DEFAULT '0',
  `TxAudioPosition` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`TxID`),
  KEY `TxLgID` (`TxLgID`),
  KEY `TxLgIDSourceURI` (`TxSourceURI`(20),`TxLgID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texttags`
--

DROP TABLE IF EXISTS `texttags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texttags` (
  `TtTxID` smallint(5) unsigned NOT NULL,
  `TtT2ID` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`TtTxID`,`TtT2ID`),
  KEY `TtT2ID` (`TtT2ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tts`
--

DROP TABLE IF EXISTS `tts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tts` (
  `TtsID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `TtsTxt` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `TtsLc` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`TtsID`),
  UNIQUE KEY `TtsTxtLC` (`TtsTxt`,`TtsLc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `words`
--

DROP TABLE IF EXISTS `words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `words` (
  `WoID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `WoLgID` tinyint(3) unsigned NOT NULL,
  `WoText` varchar(250) NOT NULL,
  `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `WoStatus` tinyint(4) NOT NULL,
  `WoTranslation` varchar(500) NOT NULL DEFAULT '*',
  `WoRomanization` varchar(100) DEFAULT NULL,
  `WoSentence` varchar(1000) DEFAULT NULL,
  `WoWordCount` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `WoCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `WoStatusChanged` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `WoTodayScore` double NOT NULL DEFAULT '0',
  `WoTomorrowScore` double NOT NULL DEFAULT '0',
  `WoRandom` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`WoID`),
  UNIQUE KEY `WoTextLCLgID` (`WoTextLC`,`WoLgID`),
  KEY `WoLgID` (`WoLgID`),
  KEY `WoStatus` (`WoStatus`),
  KEY `WoTranslation` (`WoTranslation`(333)),
  KEY `WoCreated` (`WoCreated`),
  KEY `WoStatusChanged` (`WoStatusChanged`),
  KEY `WoTodayScore` (`WoTodayScore`),
  KEY `WoTomorrowScore` (`WoTomorrowScore`),
  KEY `WoRandom` (`WoRandom`),
  KEY `WoWordCount` (`WoWordCount`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wordtags`
--

DROP TABLE IF EXISTS `wordtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wordtags` (
  `WtWoID` int(11) unsigned NOT NULL,
  `WtTgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`WtWoID`,`WtTgID`),
  KEY `WtTgID` (`WtTgID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-11-14 19:36:59
