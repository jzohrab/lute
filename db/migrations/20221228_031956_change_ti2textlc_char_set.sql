-- Ti2TextLC should have the same char set as Ti2Text.  Currently:
-- mysql> show create table textitems2;
-- ...
-- `Ti2Text` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
-- `Ti2TextLC` varchar(250) NOT NULL,
-- ...
-- That's probably not good. :-)

-- Drop trigger just in case.  The trigger gets recreated in
-- repeatable migration create_trigger_trig_textitems2_lcase.sql.
DROP TRIGGER trig_textitems2_lcase;

ALTER TABLE textitems2 DROP INDEX idx_textitems2_textlc;

ALTER TABLE textitems2 MODIFY Ti2TextLC varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL;

-- Just in case ... Not really sure if needed.
UPDATE textitems2 SET Ti2TextLC = LOWER(Ti2Text);

-- The trigger gets re-created during the repeatable migrations, but
-- we still need the index.

ALTER TABLE textitems2 ADD INDEX idx_textitems2_textlc (Ti2TextLC);
