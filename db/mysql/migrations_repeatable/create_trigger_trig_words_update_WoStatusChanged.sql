-- When words.WoStatus changes, update the WoStatusChanged.
-- Ref https://www.mysqltutorial.org/mysql-triggers/mysql-before-update-trigger/

-- Have to check if exists, because the trigger won't be there if
-- a dump file is imported.
DROP TRIGGER IF EXISTS trig_words_update_WoStatusChanged;

CREATE TRIGGER trig_words_update_WoStatusChanged BEFORE UPDATE ON words
FOR EACH ROW
BEGIN
    IF new.WoStatus <> old.WoStatus THEN
        SET new.WoStatusChanged = CURRENT_TIMESTAMP;
    END IF;
END;
