-- LOWER(textitems2.Ti2Text) is often joined with words.WoTextLC, which is slow.

ALTER TABLE textitems2 ADD COLUMN Ti2TextLC varchar(250) AFTER Ti2Text;

UPDATE textitems2 SET Ti2TextLC = LOWER(Ti2Text);

ALTER TABLE textitems2 MODIFY Ti2TextLC varchar(250) NOT NULL;

CREATE TRIGGER trig_textitems2_lcase BEFORE INSERT ON textitems2 FOR EACH row SET NEW.Ti2TextLC = LOWER(NEW.Ti2Text);

ALTER TABLE textitems2 ADD INDEX idx_textitems2_textlc (Ti2TextLC);
