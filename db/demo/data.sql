-- "Learning Using Texts" (Lute) is free and unencumbered software
-- released into the PUBLIC DOMAIN.
-- 
-- --------------------------------------------------------------
-- Installing demo data for Lute.
--
-- This is *minimal* data at the moment.  We can add other files, or
-- expand this one, as needed.
-- --------------------------------------------------------------


INSERT INTO languages VALUES('1','French','http://www.wordreference.com/fren/###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=fr&tl=en&text=###','$y\\t$t\\n','100','´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','[A-Z].|Dr.','a-zA-ZÀ-ÖØ-öø-ȳ','0','0','0');

INSERT INTO languages VALUES('3','German','http://de-en.syn.dict.cc/?s=###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=de&tl=en&text=###','$y\\t$t\\n','150','´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','[A-Z].|Dr.','a-zA-ZäöüÄÖÜß','0','0','0');

INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI, TxArchived) VALUES('1','1','Mon premier don du sang','Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui. \nOn peut dire ça comme ça. Qu’est-ce que vous avez fait, alors ?\nAlors, j’ai fait mon premier don du sang. Donc c’est à dire que on va dans une... Un organisme spécialisé vient dans l’IUT, dans notre université pour... pour prendre notre sang pour les malades de l’hôpital qui en ont besoin...\nOui, voilà, en cas d’accident par exemple, etc...\nEn cas d’accident ou en cas d’anémie ...\nOui, oui. D’accord. Et alors, donc, c’était la première fois que vous le faisiez ?','','https://learning-with-texts.sourceforge.io/media/dondusang.mp3','http://francebienvenue1.wordpress.com/2011/06/18/generosite/', 0);

INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('3','3','Die Leiden des jungen Werther','Wie froh bin ich, daß ich weg bin! Bester Freund, was ist das Herz des Menschen! Dich zu verlassen, den ich so liebe, von dem ich unzertrennlich war, und froh zu sein! Ich weiß, du verzeihst mir\'s. Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen? Die arme Leonore! Und doch war ich unschuldig. Konnt\' ich dafür, daß, während die eigensinnigen Reize ihrer Schwester mir eine angenehme Unterhaltung verschafften, daß eine Leidenschaft in dem armen Herzen sich bildete? Und doch – bin ich ganz unschuldig? Hab\' ich nicht ihre Empfindungen genährt?','','https://learning-with-texts.sourceforge.io/media/werther.mp3','http://www.gutenberg.org/ebooks/2407');
