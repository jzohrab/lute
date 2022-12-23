-- indexes required for refreshing textstatscache.
alter table textitems2 add index idx_textitems2_textid (Ti2TxID);

drop table if exists textstatscache;

create table textstatscache (
  TxID int primary key,
  wordcount int,
  distinctterms int,
  multiwordexpressions int,
  sUnk int,
  s1 int,
  s2 int,
  s3 int,
  s4 int,
  s5 int,
  sIgn int,
  sWkn int
);


