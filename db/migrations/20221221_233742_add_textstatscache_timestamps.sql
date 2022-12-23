alter table textstatscache
add column UpdatedDate timestamp default '1970-01-01 00:00:00'
after TxID;

alter table textstatscache
add column LastParse timestamp default '1970-01-01 00:00:00'
after TxID;
