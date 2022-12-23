-- The "set all to known" routine didn't set WoWordCount.

-- Sample query:
--
-- SELECT
-- WoID, WoWordCount, Ti2WordCount
-- FROM
-- words
-- INNER JOIN textitems2 on Ti2WoID = WoID
-- WHERE WoWordCount = 0;

-- SELECT
-- WoID, WoWordCount, Ti2WordCount
-- FROM
-- words
-- INNER JOIN textitems2 on Ti2WoID = WoID
-- WHERE WoID = 100169;

-- Single update:
-- UPDATE words
-- INNER JOIN textitems2 on Ti2WoID = WoID
-- SET WoWordCount = Ti2WordCount
-- WHERE WoID = 100169 AND WoWordCount = 0 AND Ti2WordCount > 0;

UPDATE words
INNER JOIN textitems2 on Ti2WoID = WoID
SET WoWordCount = Ti2WordCount
WHERE WoWordCount = 0 AND Ti2WordCount > 0;
