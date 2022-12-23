-- Migrate the existing arch texts and tags back to active tables and mark as archived.


-- Create mapping of arch text IDs to new IDs.
SET @baseid := (select ifnull(max(txid), 0) from texts);
SET @currid := @baseid;
drop temporary table if exists zzidmap;
create temporary table zzidmap (AtID int, TxID int);
insert into zzidmap (AtID, TxID)
select AtID,
@currid := @currid + 1 as TxID
from archivedtexts;
-- select * from zzidmap; -- check


-- Move the texts, using the mapping.
INSERT INTO texts
(
TxID,
TxLgID,
TxTitle,
TxText,
TxAnnotatedText,
TxAudioURI,
TxSourceURI,
TxPosition,
TxAudioPosition,
TxArchived
)
SELECT
TxID,
AtLgID,
AtTitle,
AtText,
AtAnnotatedText,
AtAudioURI,
AtSourceURI,
0, -- position
0, -- audio pos
1 -- archived
from archivedtexts a
inner join zzidmap z on z.AtID = a.AtID;


-- Move the tags, using the mapping.
INSERT into texttags (TtTxID, TtT2ID)
SELECT z.TxID,
a.AgT2ID
from archtexttags a
inner join zzidmap z on z.AtID = a.AgAtID;


-- Kill mapping.
drop temporary table if exists zzidmap;
