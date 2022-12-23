/*
A helper script to find bad data.

LWT would sometimes create multiword expressions, but not put them in
the "right place" in textitems2, or would not update textitems2
Ti2Text and Ti2TextLC correctly.

For example, suppose we had the following textitems2 data:

| Ti2Text    | Ti2WordCount | Ti2Order |
| un gato    | 2            | 14       |
| Yo         | 1            | 14       |
|            | 0            | 15       |
| tengo      | 1            | 16       |

This data is "bad" because the multiword expression "un gato" has been
put in position Ti2Order 14, but the actual parsed text had "Yo, ,
tengo" for those positions.  When the text is rendered, "un gato"
would hide the words "Yo", " ", "tengo", and the text wouldn't make
sense.

This query is slow and so should not be run on the browser -- it's a
back-end data cleanup job, that should be run as a separate command.

*/

-- all data
-- select * from textitems2 order by ti2txid, ti2order, ti2wordcount desc;

-- Check single-word terms mapped ok.
-- select * from textitems2 where ti2woid != 0 and (ti2text is null or ti2text = '') and ti2wordcount = 1;

-- Check multiwords -- sometimes the text is null!
-- select * from textitems2 where ti2woid != 0 and (ti2text is null or ti2text = '') and ti2wordcount > 1;


/*
-- Heavy query to compare the multi-word-terms with the mword that
-- _would_ be created if we just joined the parse textitem2 entries in
-- the same range.
--
-- This query doesn't fix anything, but it could be used to do a fix.
select
wotext,
txtitle,
txid,
ti2order as ord,
ti2woid as wid,
ti2wordcount as wc,
multitermtext as mterm,
rawjoinedtext
from
(
  select ti2txid, ti2order, ti2woid, ti2wordcount, ti2text as multitermtext,
  (
    select GROUP_CONCAT(tinner.ti2text order by tinner.ti2order separator '')
    from textitems2 tinner
    where tinner.ti2txid = ti2.ti2txid
    and tinner.ti2wordcount < 2
    and tinner.ti2order >= ti2.ti2order
    and tinner.ti2order <= ti2.ti2order + 2 * (ti2.ti2wordcount - 1)
  ) rawjoinedtext
  from textitems2 ti2
  where ti2wordcount > 1
) srcdata
inner join words on woid = ti2woid
inner join texts on txid = ti2txid
where multitermtext != rawjoinedtext
order by ti2txid, ti2order;
*/


-- Lighter query to find mwords with no text!
select distinct ti2txid
from textitems2
where ti2wordcount > 1 and (ti2text is null or ti2text = '');


-- Cleanup bad (no text) mwords:
/*
drop table if exists zzcleanup;

create table zzcleanup(ti2txid int);

insert into zzcleanup
select distinct ti2txid
from textitems2
where ti2wordcount > 1 and (ti2text is null or ti2text = '');

-- select * from zzcleanup;

-- cleanup, to force re-parsing on read:
delete from textitems2 where ti2txid in
(select ti2txid from zzcleanup);

drop table zzcleanup;
*/


/*
-- spot-check
select *
from textitems2
where ti2wordcount > 1
order by ti2txid, ti2order;
*/


-- Non-archived texts that have 0 textitems2
-- (aka have been cleaned, ready for re-parse)
select
txid, txtitle
from texts
left outer join textitems2 on txid = ti2txid
where ti2txid is null and txarchived = 0
order by txtitle;
