drop table if exists bookstats;

create table bookstats (
  BkID int primary key,
  wordcount int,
  distinctterms int,
  distinctunknowns int,
  unknownpercent int
);
