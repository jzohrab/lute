update languages set LgParserType = 'spacedel' where LgParserType = 'romance';

alter table languages modify column LgParserType varchar(20) not null default 'spacedel';
