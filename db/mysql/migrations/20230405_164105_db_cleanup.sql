-- CLEANUP TIME for sqlite importing.
-- Have to fix keys so sqlite is setup correctly, and create necessary fks.

-- Drop unused trash.
drop table if exists newsfeeds;
drop table if exists feedlinks;

-- Fix table types!
-- SELECT concat('alter table ', TABLE_NAME, ' ENGINE = InnoDB;') engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'test_lute';
alter table _migrations ENGINE = InnoDB;
alter table books ENGINE = InnoDB;
alter table bookstats ENGINE = InnoDB;
alter table booktags ENGINE = InnoDB;
alter table languages ENGINE = InnoDB;
alter table sentences ENGINE = InnoDB;
alter table settings ENGINE = InnoDB;
alter table statuses ENGINE = InnoDB;
alter table tags ENGINE = InnoDB;
alter table tags2 ENGINE = InnoDB;
alter table texts ENGINE = InnoDB;
alter table texttags ENGINE = InnoDB;
alter table texttokens ENGINE = InnoDB;
alter table wordimages ENGINE = InnoDB;
alter table wordparents ENGINE = InnoDB;
alter table words ENGINE = InnoDB;
alter table wordtags ENGINE = InnoDB;


-- Fix keys so that sqlite export creates auto-incrementing keys.
alter table languages modify column LgID INTEGER NOT NULL AUTO_INCREMENT;

alter table books modify column BkID INTEGER NOT NULL AUTO_INCREMENT;
alter table books modify column BkLgID INTEGER NOT NULL;

alter table booktags modify column BtBkID INTEGER NOT NULL;
alter table booktags modify column BtT2ID INTEGER NOT NULL;

alter table sentences modify column SeID INTEGER NOT NULL AUTO_INCREMENT;
alter table sentences modify column SeLgID INTEGER NOT NULL;
alter table sentences modify column SeTxID INTEGER NOT NULL;

alter table statuses modify column StID INTEGER NOT NULL;

alter table tags modify column TgID INTEGER NOT NULL AUTO_INCREMENT;
alter table tags2 modify column T2ID INTEGER NOT NULL AUTO_INCREMENT;

alter table texts modify column TxID INTEGER NOT NULL AUTO_INCREMENT;
alter table texts modify column TxBkID INTEGER NOT NULL;
alter table texts modify column TxLgID INTEGER NOT NULL;

alter table texttags modify column TtTxID INTEGER NOT NULL;
alter table texttags modify column TtT2ID INTEGER NOT NULL;

alter table texttokens modify column TokTxID INTEGER NOT NULL;
alter table wordimages modify column WiID INTEGER NOT NULL AUTO_INCREMENT;
alter table wordimages modify column WiWoID INTEGER NOT NULL;

alter table wordparents modify column WpWoID INTEGER NOT NULL;
alter table wordparents modify column WpParentWoID INTEGER NOT NULL;

alter table words modify column WoID INTEGER NOT NULL AUTO_INCREMENT;
alter table words modify column WoLgID INTEGER NOT NULL;

alter table wordtags modify column WtWoID INTEGER NOT NULL;
alter table wordtags modify column WtTgID INTEGER NOT NULL;

-- delete any data that doesn't respect keys - it's invisible/unreachable/invalid trash data anyway.
-- set @col = 'woid';
-- set @tbl = 'words';
-- select concat('delete from ', table_name, ' where ', column_name, ' not in (select ', @col, ' from ', @tbl, ');') from information_schema.columns where table_schema = 'test_lute' and table_name <> @tbl and column_name like concat('%', @col, '%');

delete from books where BkLgID not in (select lgid from languages);
delete from sentences where SeLgID not in (select lgid from languages);
delete from texts where TxLgID not in (select lgid from languages);
delete from words where WoLgID not in (select lgid from languages);

delete from bookstats where BkID not in (select bkid from `books`);
delete from booktags where BtBkID not in (select bkid from `books`);
delete from texts where TxBkID not in (select bkid from `books`);   

delete from wordtags where WtTgID not in (select tgid from tags);

delete from booktags where BtT2ID not in (select t2id from tags2);
delete from texttags where TtT2ID not in (select t2id from tags2);

delete from sentences where SeTxID not in (select txid from texts);
delete from texttags where TtTxID not in (select txid from texts);
delete from texttokens where TokTxID not in (select txid from texts);  

delete from wordimages where WiWoID not in (select woid from words);
delete from wordparents where WpWoID not in (select woid from words);
delete from wordparents where WpParentWoID not in (select woid from words);
delete from wordtags where WtWoID not in (select woid from words);

-- set up fk relationships
-- set @col = 'woid';
-- set @tbl = 'words';
-- select concat('alter table ', table_name, ' add foreign key (', column_name, ') references ', @tbl, '(', @col, ');')
-- from information_schema.columns where table_schema = 'test_lute' and table_name <> @tbl and column_name like concat('%', @col, '%');


alter table books add foreign key (BkLgID) references languages (lgid);
alter table sentences add foreign key (SeLgID) references languages (lgid);
alter table texts add foreign key (TxLgID) references languages (lgid);
alter table words add foreign key (WoLgID) references languages (lgid);

alter table bookstats add foreign key (BkID) references books (BkID);
alter table booktags add foreign key (BtBkID) references books (BkID);
alter table texts add foreign key (TxBkID) references books (BkID);

alter table wordtags add foreign key (WtTgID) references tags (tgid);

alter table booktags add foreign key (BtT2ID) references tags2 (t2id);
alter table texttags add foreign key (TtT2ID) references tags2 (t2id);

alter table sentences add foreign key (SeTxID) references texts (txid);
alter table texttags add foreign key (TtTxID) references texts (txid);
alter table texttokens add foreign key (TokTxID) references texts (txid);

alter table wordimages add foreign key (WiWoID) references words (woid);
alter table wordparents add foreign key (WpWoID) references words (woid);
alter table wordparents add foreign key (WpParentWoID) references words (woid);
alter table wordtags add foreign key (WtWoID) references words (woid);
