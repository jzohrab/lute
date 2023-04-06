-- Before dropping any keys, we have to change WoStatusChanged.
-- WoStatusChanged should default to TIMESTAMP, because when a new
-- record is added, that's when the WoStatus really changed.  I don't
-- care if that causes trouble later.

ALTER TABLE words MODIFY WoStatusChanged timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE words SET WoStatusChanged = WoCreated WHERE WoStatusChanged = 0;

-- The words table has too many odd keys -- I can't see these being
-- effective.

ALTER TABLE words DROP KEY WoTranslation;
ALTER TABLE words DROP KEY WoCreated;
ALTER TABLE words DROP KEY WoStatusChanged;
ALTER TABLE words DROP KEY WoTodayScore;
ALTER TABLE words DROP KEY WoTomorrowScore;


-- words.WoTranslation should be nullable.  If users don't enter
-- anything, it means null, not '*' (the current default.

ALTER TABLE words MODIFY WoTranslation varchar(500) null;

UPDATE words set WoTranslation = NULL where WoTranslation = '*';

UPDATE words set WoTranslation = NULL where WoTranslation = '';
