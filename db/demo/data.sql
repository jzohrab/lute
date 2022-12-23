-- "Learning with Texts" (LWT) is free and unencumbered software 
-- released into the PUBLIC DOMAIN.
-- 
-- Anyone is free to copy, modify, publish, use, compile, sell, or
-- distribute this software, either in source code form or as a
-- compiled binary, for any purpose, commercial or non-commercial,
-- and by any means.
-- 
-- In jurisdictions that recognize copyright laws, the author or
-- authors of this software dedicate any and all copyright
-- interest in the software to the public domain. We make this
-- dedication for the benefit of the public at large and to the 
-- detriment of our heirs and successors. We intend this 
-- dedication to be an overt act of relinquishment in perpetuity
-- of all present and future rights to this software under
-- copyright law.
-- 
-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
-- EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
-- WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
-- AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
-- FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
-- OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
-- CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
-- THE SOFTWARE.
-- 
-- For more information, please refer to [http://unlicense.org/].
-- --------------------------------------------------------------
-- 
-- --------------------------------------------------------------
-- Installing an LWT demo database - demo data only
-- --------------------------------------------------------------

INSERT INTO archivedtexts VALUES('1','1','Bonjour!','Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui.','',NULL,'http://francebienvenue1.wordpress.com/2011/06/18/generosite/');

INSERT INTO archtexttags VALUES('1','1');
INSERT INTO archtexttags VALUES('1','4');
INSERT INTO archtexttags VALUES('1','8');

INSERT INTO newsfeeds VALUES(1, 9, 'National Geographic News', 'http://feeds.nationalgeographic.com/ng/News/News_Main?format=xml', '//div[@class="abstract"] | //*[@class[contains(concat(" ",normalize-space(.)," ")," text ")]]/p', '', 1455048658, 'edit_text=1');
INSERT INTO newsfeeds VALUES(2, 9, 'The Guardian', 'http://www.theguardian.com/theguardian/mainsection/rss', '//div[@id="article-wrapper"]', '//div[@id="main-content-picture"] | //div[@id="article-wrapper"]/span[@class="trackable-component component-wrapper six-col"]', 0, 'edit_text=1');
INSERT INTO newsfeeds VALUES(3, 1, 'Le Monde', 'http://www.lemonde.fr/rss/une.xml', '//*[@id="articleBody"] | //div[@class="entry-content"]/p', '', 0, 'edit_text=1');
INSERT INTO newsfeeds VALUES(4, 11, 'Il Corriere', 'http://xml.corriereobjects.it/rss/homepage.xml', '//div[@class="contenuto_articolo"]/p | //div[@id="content-to-read"]/p | //blockquote/p | //p[@class="chapter-paragraph"]', '', 0, 'edit_text=1,max_links=200');
INSERT INTO newsfeeds VALUES(5, 3, 'wissen.de', 'http://feeds.feedburner.com/wissen/wissen_de', '//div[@class="article-content"]', '//div[@class="file file-image file-image-jpeg"] | //em[last()] | //div[@class="imagegallery-wrapper hide"] | //ul[@class="links inline"] | //div[@class="smart-paging-pager"] | //div[@class="field-item even"]/div', 0, 'edit_text=1,max_links=500');
INSERT INTO newsfeeds VALUES(6, 3, 'Der Spiegel', 'http://www.spiegel.de/schlagzeilen/index.rss', '//p[@class="article-intro"] | //div[@class="article-section clearfix"]', '//*[@class[contains(concat(" ",normalize-space(.)," ")," js-module-box-image ")]] |  //*[@class[contains(concat(" ",normalize-space(.)," ")," asset-box ")]] |  //*[@class[contains(concat(" ",normalize-space(.)," ")," htmlartikellistbox ")]] |  //p/i', 0, 'edit_text=1,charset=meta');
INSERT INTO newsfeeds VALUES(7, 3, 'deutsche Welle Nachrichten', 'http://rss.dw-world.de/xml/DKpodcast_lgn_de', '//description', '', 0, 'article_source=description');
INSERT INTO newsfeeds VALUES(8, 10, 'El Pais', 'http://ep00.epimg.net/rss/elpais/portada.xml', '//div[@id="cuerpo_noticia"]/p', '', 0, '');
INSERT INTO newsfeeds VALUES(9, 5, 'Nikkei', 'http://www.zou3.net/php/rss/nikkei2rss.php?head=kurashi', '//*[@*[contains(.,"cmn-article_text")]]', '', 0, '');
INSERT INTO newsfeeds VALUES(10, 12, 'RIA Novosti', 'http://ria.ru/export/rss2/index.xml', '//div[@class="article_lead"] | //*[@*[contains(.,"articleBody")]]/p', '//p[@class="marker-quote3"]', 0, 'edit_text=1');
INSERT INTO newsfeeds VALUES(11, 13, 'Últimas Notícias - Diário Catarinense', 'http://diariocatarinense.feedsportal.com/c/34199/f/620394/index.rss', '//div[@class="materia-corpo entry-content"] | //div[@class="entry-content"]/p', '//p/em | //a/strong | //strong/a', 0, 'edit_text=1');
INSERT INTO newsfeeds VALUES(12, 6, 'Hankyoreh', 'http://kr.hani.feedsportal.com/c/34762/f/640633/index.rss', '//div[@class="article-contents"] | //div[@class="article-text"]', '//table | //div[@id="hani-popular-new-table"] | //a[@href[contains(.,"@hani.co.kr")]] | //a/b', 1455176479, '');
INSERT INTO newsfeeds VALUES(13, 7, 'ข่าวไทยรัฐออนไลน์', 'http://www.thairath.co.th/rss/news.xml', '//div[@class="entry"]/p', '//div[@id="content"]/p[@class="time"]', 1455049278, 'edit_text=1');
INSERT INTO newsfeeds VALUES(14, 14, 'Euronews Arabic', 'http://feeds.feedburner.com/euronews/ar/news/', '//div[@id="article-text"]/p |  //div[@id="articleTranscript"]/p', '//div[@id="article-text"]/p[@class="en-cpy"]', 0, '');
INSERT INTO newsfeeds VALUES(15, 10, 'Spanish Podcast', 'http://www.spanishpodcast.org/podcasts/index.xml', 'redirect://div[@class="figure-content caption"]//a | //div[@class="figure-content caption"]/p | //div/p[@class="MsoNormal"]', '', 0, 'edit_text=1');
INSERT INTO newsfeeds VALUES(16, 3, 'NachDenkSeiten', 'http://www.nachdenkseiten.de/?feed=audiopodcast', '//encoded/p', '', 0, 'edit_text=1,article_source=encoded');
INSERT INTO newsfeeds VALUES(17, 2, 'The Chairman''s Bao', 'http://www.thechairmansbao.com/feed/', '//encoded', '//p[last()]', 1453802401, 'edit_text=1,article_source=encoded');


INSERT INTO languages VALUES('1','French','http://www.wordreference.com/fren/###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=fr&tl=en&text=###','$y\\t$t\\n','100','´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','[A-Z].|Dr.','a-zA-ZÀ-ÖØ-öø-ȳ','0','0','0');
INSERT INTO languages VALUES('2','Chinese','http://ce.linedict.com/dict.html#/cnen/search?query=###','http://chinesedictionary.mobi/?handler=QueryWorddict&mwdqb=###','*http://translate.google.com/?ie=UTF-8&sl=zh&tl=en&text=###','$y\\t$t\\n','200','','.!?:;。！？：；','','一-龥','1','1','0');
INSERT INTO languages VALUES('3','German','http://de-en.syn.dict.cc/?s=###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=de&tl=en&text=###','$y\\t$t\\n','150','´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','[A-Z].|Dr.','a-zA-ZäöüÄÖÜß','0','0','0');
INSERT INTO languages VALUES('4','Chinese2','http://ce.linedict.com/dict.html#/cnen/search?query=###','http://chinesedictionary.mobi/?handler=QueryWorddict&mwdqb=###','*http://translate.google.com/?ie=UTF-8&sl=zh&tl=en&text=###','$y\\t$t\\n','200','','.!?:;。！？：；','','一-龥','1','0','0');
INSERT INTO languages VALUES('5','Japanese','http://jisho.org/words?eng=&dict=edict&jap=###','http://jisho.org/kanji/details/###','*http://translate.google.com/?ie=UTF-8&sl=ja&tl=en&text=###','$y\\t$t\\n','200','','.!?:;。！？：；','','一-龥ぁ-ヾ','1','1','0');
INSERT INTO languages VALUES('6','Korean','http://endic.naver.com/search.nhn?sLn=kr&isOnlyViewEE=N&query=###',NULL,'*http://translate.google.com/?text=###&ie=UTF-8&sl=ko&tl=en','$y\\t$t\\n','150','','.!?:;。！？：；','','가-힣ᄀ-ᇂ','0','0','0');
INSERT INTO languages VALUES('7','Thai','http://dict.longdo.com/search/###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=th&tl=en&text=###','$y\\t$t\\n','250','','.!?:;','','ก-๛','1','0','0');
INSERT INTO languages VALUES('8','Hebrew','*http://dictionary.reverso.net/hebrew-english/###',NULL,'*http://translate.google.com/?ie=UTF-8&sl=iw&tl=en&text=###','$y\\t$t\\n','150','','.!?:;','','\\x{0590}-\\x{05FF}','0','0','1');


-- tags are created in reference_data.sql


INSERT INTO tags2 VALUES('1','demo','');
INSERT INTO tags2 VALUES('2','basic','');
INSERT INTO tags2 VALUES('3','goethe','');
INSERT INTO tags2 VALUES('4','conversation','');
INSERT INTO tags2 VALUES('5','joke','');
INSERT INTO tags2 VALUES('6','chinesepod','');
INSERT INTO tags2 VALUES('7','literature','');
INSERT INTO tags2 VALUES('8','fragment','');
INSERT INTO tags2 VALUES('9','annotation','');


INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('1','1','Mon premier don du sang','Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui. \nOn peut dire ça comme ça. Qu’est-ce que vous avez fait, alors ?\nAlors, j’ai fait mon premier don du sang. Donc c’est à dire que on va dans une... Un organisme spécialisé vient dans l’IUT, dans notre université pour... pour prendre notre sang pour les malades de l’hôpital qui en ont besoin...\nOui, voilà, en cas d’accident par exemple, etc...\nEn cas d’accident ou en cas d’anémie ...\nOui, oui. D’accord. Et alors, donc, c’était la première fois que vous le faisiez ?','','https://learning-with-texts.sourceforge.io/media/dondusang.mp3','http://francebienvenue1.wordpress.com/2011/06/18/generosite/');
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('2','2','The Man and the Dog (annotated version)','一天，一个男人走在街上。突然，他看见前面有一只黑色的大狗，看起来很凶。男人非常害怕，不敢往前走。狗的旁边站着一个女人，男人问她：你的狗咬人吗？女人说：我的狗不咬人。这时，那只狗咬了男人。他气坏了，大叫：你说你的狗不咬人！女人回答：这不是我的狗。','4	一天	11	one day\n-1	，\n6	一	9	a\n8	个	12	(MW)\n12	男人	15	man\n14	走	16	walk\n16	在	17	at\n20	街上	20	on the street\n-1	。\n24	突然	21	suddenly\n-1	，\n26	他	184	he\n30	看见	185	catch sight\n34	前面	186	ahead\n36	有	187	have\n38	一	9	a\n40	只	188	(MW)\n44	黑色	189	black color\n46	的	190	\'s\n48	大	191	big\n50	狗	192	dog\n-1	，\n56	看起来	193	appears\n58	很	194	very\n60	凶	195	ferocious\n-1	。\n64	男人	15	man\n68	非常	196	exceptional\n72	害怕	197	be afraid\n-1	，\n74	不	198	not\n76	敢	199	dare\n80	往前	200	move ahead\n82	走	16	walk\n-1	。\n84	狗	192	dog\n86	的	190	\'s\n90	旁边	201	side\n92	站	202	stand\n94	着	203	(there)\n96	一	9	a\n98	个	12	(MW)\n102	女人	204	woman\n-1	，\n106	男人	15	man\n108	问	205	ask\n110	她	206	her\n-1	：\n114	你的	220	your\n116	狗	192	dog\n118	咬	208	bite\n120	人	14	person\n122	吗	209	(QW)\n-1	？\n126	女人	204	woman\n128	说	210	say\n-1	：\n132	我的	211	my\n134	狗	192	dog\n136	不	198	not\n138	咬	208	bite\n140	人	14	person\n-1	。\n144	这时	212	at this time\n-1	，\n146	那	213	that\n148	只	188	(MW)\n150	狗	192	dog\n152	咬	208	bite\n154	了	214	finish\n158	男人	15	man\n-1	。\n160	他	184	he\n164	气坏	215	furious\n166	了	214	(change)\n-1	，\n168	大	191	strong\n170	叫	216	shout\n-1	：\n172	你	207	you\n174	说	210	say\n178	你的	220	your\n180	狗	192	dog\n182	不	198	not\n184	咬	208	bite\n186	人	14	person\n-1	！\n190	女人	204	woman\n194	回答	217	answer\n-1	：\n196	这	218	this\n198	不	198	not\n200	是	219	be\n204	我的	211	my\n206	狗	192	dog\n-1	。','https://learning-with-texts.sourceforge.io/media/manandthedog.mp3','http://chinesepod.com/lessons/the-man-and-the-dog');
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('3','3','Die Leiden des jungen Werther','Wie froh bin ich, daß ich weg bin! Bester Freund, was ist das Herz des Menschen! Dich zu verlassen, den ich so liebe, von dem ich unzertrennlich war, und froh zu sein! Ich weiß, du verzeihst mir\'s. Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen? Die arme Leonore! Und doch war ich unschuldig. Konnt\' ich dafür, daß, während die eigensinnigen Reize ihrer Schwester mir eine angenehme Unterhaltung verschafften, daß eine Leidenschaft in dem armen Herzen sich bildete? Und doch – bin ich ganz unschuldig? Hab\' ich nicht ihre Empfindungen genährt?','','https://learning-with-texts.sourceforge.io/media/werther.mp3','http://www.gutenberg.org/ebooks/2407');
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('4','4','The Man and the Dog','一天，一 个 男人 走 在 街上。 突然，他 看见 前面 有 一 只 黑色 的 大 狗，看起来 很 凶。 男人 非常 害怕，不敢 往前 走。 狗 的 旁边 站着 一 个 女人，男人 问 她： 你 的 狗 咬 人 吗？ 女人 说： 我 的 狗 不 咬 人。 这时，那 只 狗 咬 了 男人。 他 气坏 了，大 叫： 你 说 你 的 狗 不 咬 人！ 女人 回答： 这 不是 我 的 狗。','','https://learning-with-texts.sourceforge.io/media/manandthedog.mp3','http://chinesepod.com/lessons/the-man-and-the-dog');
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('5','5','Some expressions','はい。いいえ。\nすみません。\nどうも。\nありがとうございます。\n日本語を話しますか。はい、少し。\nイギリスから来ました。','','https://learning-with-texts.sourceforge.io/media/jap.mp3',NULL);
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('6','6','Test in Korean','좋은 아침.\n안녕하세요.\n잘자요.\n잘가요.\n안녕하세요, 잘지냈어요?\n네, 잘지냈어요?\n네 그럼요.\n이름이 뭐에요?\n제 이름은 존이에요, 이름이 뭐에요?\n제 이름은 메리에요.','','https://learning-with-texts.sourceforge.io/media/korean.mp3',NULL);
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('7','7','Hello in Thai','ส วัส ดี ครับ\nส วัส ดี ค่ะ','','https://learning-with-texts.sourceforge.io/media/thai.mp3',NULL);
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('8','8','Greetings','בוקר טוב\nאחר צהריים טובים\nערב טוב\nלילה טוב\nלהתראות','','https://learning-with-texts.sourceforge.io/media/hebrew.mp3',NULL);
INSERT INTO texts(TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) VALUES('9','1','Mon premier don du sang (Short & annotated version)','Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui. \nOn peut dire ça comme ça. Qu’est-ce que vous avez fait, alors ?\nAlors, j’ai fait mon premier don du sang.','2	Bonjour	2	hello\n-1	 \n4	Manon	1	*\n-1	. \n-1	¶ \n7	Bonjour	2	hello\n-1	. \n-1	¶ \n10	Alors	3	well\n-1	, \n12	je	7	I\n-1	 \n14	crois	8	think\n-1	 \n16	qu	6	that\n-1	\'\n22	il y a	4	there is\n-1	 \n24	pas	170	(not)\n-1	 \n26	longtemps	171	long time\n-1	, \n28	là	172	there\n-1	, \n30	vous	146	you\n-1	 \n32	avez	150	have\n-1	 \n34	fait	147	done\n-1	 \n36	une	173	a\n-1	 \n40	bonne action	46	good deed\n-1	 ? \n-1	¶ \n43	Oui	165	yes\n-1	. \n-1	¶ \n46	On	166	one\n-1	 \n48	peut	167	can\n-1	 \n50	dire	26	say\n-1	 \n52	ça	168	that\n-1	 \n54	comme	169	as\n-1	 \n56	ça	168	that\n-1	. \n64	Qu\'est-ce que	22	what\n-1	 \n66	vous	146	you\n-1	 \n68	avez	150	have\n-1	 \n70	fait	147	done\n-1	, \n72	alors	3	then\n-1	 ? \n-1	¶ \n75	Alors	3	well\n-1	, \n77	j	174	I\n-1	\'\n79	ai	149	have\n-1	 \n81	fait	147	made\n-1	 \n83	mon	151	my\n-1	 \n85	premier	175	first\n-1	 \n91	don du sang	33	blood donation\n-1	.','https://learning-with-texts.sourceforge.io/media/dondusang_short.mp3','http://francebienvenue1.wordpress.com/2011/06/18/generosite/');


INSERT INTO texttags VALUES('1','1');
INSERT INTO texttags VALUES('1','4');
INSERT INTO texttags VALUES('2','1');
INSERT INTO texttags VALUES('2','5');
INSERT INTO texttags VALUES('2','6');
INSERT INTO texttags VALUES('2','9');
INSERT INTO texttags VALUES('3','1');
INSERT INTO texttags VALUES('3','3');
INSERT INTO texttags VALUES('3','7');
INSERT INTO texttags VALUES('4','1');
INSERT INTO texttags VALUES('4','5');
INSERT INTO texttags VALUES('4','6');
INSERT INTO texttags VALUES('5','1');
INSERT INTO texttags VALUES('5','2');
INSERT INTO texttags VALUES('6','1');
INSERT INTO texttags VALUES('6','2');
INSERT INTO texttags VALUES('7','1');
INSERT INTO texttags VALUES('7','2');
INSERT INTO texttags VALUES('8','1');
INSERT INTO texttags VALUES('8','2');
INSERT INTO texttags VALUES('9','1');
INSERT INTO texttags VALUES('9','4');
INSERT INTO texttags VALUES('9','9');


-- Settings are created in reference_data.sql
UPDATE settings set StValue = '1' WHERE StKey = 'currentlanguage';
UPDATE settings set StValue = '1' WHERE StKey = 'currenttext';
UPDATE settings set StValue = '2020-10-03' WHERE StKey = 'lastscorecalc';

