CREATE TABLE wordimages (
  WiID  mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  WiWoID mediumint(8) NOT NULL,
  WiSource varchar(500) NOT NULL,
  PRIMARY KEY (WiID),
  KEY WiWoID (WiWoID)
);
