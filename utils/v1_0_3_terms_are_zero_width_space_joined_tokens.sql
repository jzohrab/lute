/**
This script is only needed for people using Lute equal to or earlier
than v1.0.3.

Lute used to store Terms in the words table as simple joins of reading
pane tokens (eg, on the reading pane, "Hello", " ", "there" would be
saved as "Hello there".)

This has been changed: now Term tokens are joined with a zero-width
space, so the same string would be saved as "Hello[ZWS] [ZWS]there".

Reason for the change: Japanese and other non-space languages don't
_have_ spaces, so if the tokens are joined as-is, it's impossible to
break them apart again.  All Japanese multiword Terms are also saved
with zero-width spaces (e.g. "genki[ZWS]desu").  By using ZWS joins
for Terms everywhere, Lute now standardizes parsing and searching,
which makes subsequent processing much easier.

This script changes existing terms so that they use the correct format
going forward.
*/

-- list all multi-term words, save in a file somewhere.
select woid, wotext from words where wowordcount > 1;

-- List words that have spaces but that don't have zero-width spaces.  Will be updated in next step.
select woid, wotext from words where wowordcount > 1 and wotext like '% %' and wotext not like concat('%', 0xE2808B, '%');

-- Update words with spaces but no zero-width spaces.
update words set wotext = replace(wotext, ' ', concat(0xE2808B, ' ', 0xE2808B))
where wowordcount > 1 and wotext like '% %' and wotext not like concat('%', 0xE2808B, '%');

-- List potential trouble makers
select woid, wotext from words where wowordcount > 1 and (wotext like '%,%' or wotext like '%"%' or wotext like '%\'%' or wotext like '%-%');

-- fix any troublemakers manually.
-- e.g.
-- update words set wotext = concat('Fri', 0xE2808B, '\'', 0xE2808B, 'it') where woid = 101772;

-- update the textlc
update words set wotextlc = lower(wotext) where wowordcount > 1;

-- check
select replace(wotext, 0xE2808B, '[ZWS]') from words where wowordcount > 1;

