alter table textstatscache
add column UpdatedDate timestamp NULL
after TxID;

alter table textstatscache
add column LastParse timestamp NULL
after TxID;
