-- Trigger trig_textitems2_lcase was created in migrtion
-- 20221120_163341_add_textitems2_lcase.sql,
-- but triggers screw up mysqldump and reimport (blah, mysqldump bug).
--
-- To make export/import possible, the db has to be exported *without*
-- triggers in the dump file, so we have to always recreate the trigger.

-- Have to check if exists, because the trigger won't be there if
-- a dump file is imported.
DROP TRIGGER IF EXISTS trig_textitems2_lcase;

CREATE TRIGGER trig_textitems2_lcase BEFORE INSERT ON textitems2 FOR EACH row SET NEW.Ti2TextLC = LOWER(NEW.Ti2Text);
