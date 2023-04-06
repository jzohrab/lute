alter table words add column WoTokenCount tinyint unsigned NOT NULL DEFAULT 0 AFTER WoWordCount;

-- bugfix: wordcount was 0 in some cases!
update words set WoWordCount = 1 where WoWordCount = 0;

update words set WoTokenCount = WoWordCount;

-- If language keeps spaces, token count = wc * 2 - 1.
-- For Japanese, the wordcount is correct.
update words
set WoTokenCount = (WoWordCount * 2 - 1)
where words.WoLgID in (select LgID from languages where lgremovespaces = 0);

create index WoTokenCount on words (WoTokenCount);
