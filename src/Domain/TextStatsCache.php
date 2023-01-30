<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Language;
use App\Repository\TermRepository;
use App\Utils\Connection;


/**
 * Cached Text stats.
 *
 * When listing texts, it's far too slow to query and rebuild
 * stats all the time.
 */
class TextStatsCache {

    /** refresh */
    private static function do_refresh($sql_ids_to_update, $conn) {

        // TODO:storedproc Replace temp table with stored proc.
        //
        // Using a temp table to determine which texts to update.
        // I tried using left joins back to textstatscache, but it
        // was slow, despite indexing.  There is probably a better
        // way to do this, but this works for now.
        //
        // Ideally, this would be a temp table ... but then the stats update
        // query complains about "reopening the table", as it's used several
        // times in the query.
        //
        // This could be moved to a stored procedure, but this is good
        // enough for now.

        $sqls = [

            //prep stats records.
            "insert ignore into textstatscache (TxID)
select TxID from texts
where TxArchived = 0",

            // Assoc any pending textitem2's
            "update textitems2
inner join words on ti2textlc = wotextlc and ti2lgid = wolgid
set ti2woid = woid
where ti2woid = 0",


            // Temp table of textids.
            "drop table if exists TEMPupdateStatsTxIDs",
            "create table TEMPupdateStatsTxIDs (TxID int primary key)",

            // Load IDs to update.
            "insert into TEMPupdateStatsTxIDs " . $sql_ids_to_update,

            // Remove old
            "delete from textstatscache where TxID in
(select TxID from TEMPupdateStatsTxIDs)",

            // Load stats.
            "insert into textstatscache (
  TxID,
  updatedDate,
  wordcount,
  distinctterms,
  multiwordexpressions,
  sUnk,
  s1,
  s2,
  s3,
  s4,
  s5,
  sIgn,
  sWkn
)
SELECT
t.TxID As TxID,
CURRENT_TIMESTAMP,
wordcount.n as wordcount,
coalesce(distinctterms.n, 0) as distinctterms,
coalesce(mwordexpressions.n, 0) as multiwordexpressions,
sUnk, s1, s2, s3, s4, s5, sIgn, sWkn

FROM texts t
inner join TEMPupdateStatsTxIDs u on u.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID as TxID, COUNT(*) AS n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WordCount = 1
  GROUP BY Ti2TxID
) AS wordcount on wordcount.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID as TxID, COUNT(distinct Ti2WoID) AS n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WoID <> 0
  GROUP BY Ti2TxID
) AS distinctterms on distinctterms.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID AS TxID, COUNT(DISTINCT Ti2WoID) as n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WordCount > 1
  GROUP BY Ti2TxID
) AS mwordexpressions on mwordexpressions.TxID = t.TxID

LEFT OUTER JOIN (

      SELECT TxID,
      SUM(CASE WHEN status=0 THEN c ELSE 0 END) AS sUnk,
      SUM(CASE WHEN status=1 THEN c ELSE 0 END) AS s1,
      SUM(CASE WHEN status=2 THEN c ELSE 0 END) AS s2,
      SUM(CASE WHEN status=3 THEN c ELSE 0 END) AS s3,
      SUM(CASE WHEN status=4 THEN c ELSE 0 END) AS s4,
      SUM(CASE WHEN status=5 THEN c ELSE 0 END) AS s5,
      SUM(CASE WHEN status=98 THEN c ELSE 0 END) AS sIgn,
      SUM(CASE WHEN status=99 THEN c ELSE 0 END) AS sWkn

      FROM (
      SELECT Ti2TxID AS TxID, WoStatus AS status, COUNT(*) as c
      FROM textitems2
      inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
      INNER JOIN words ON WoID = Ti2WoID
      WHERE Ti2WoID <> 0
      GROUP BY Ti2TxID, WoStatus

      UNION
      SELECT Ti2TxID as TxID, 0 as status, COUNT(*) as c
      FROM textitems2
      inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
      WHERE Ti2WoID = 0 AND Ti2WordCount = 1
      GROUP BY Ti2TxID
  
      ) rawdata
      GROUP BY TxID
) AS statuses on statuses.TxID = t.TxID",

            // cleanup.
            "drop table if exists TEMPupdateStatsTxIDs"
        ];

        foreach ($sqls as $sql)
            TextStatsCache::exec_sql($sql, $conn);
    }


    /**
     * Check the current lastmaxstatuschanged key in settings table vs
     * actual word status changed values, return true if something has
     * changed.
     */
    public static function needs_refresh(): bool {
        $conn = TextStatsCache::getConnection();

        $sql = "select count(*) as c from settings
          where StKey = 'lastmaxstatuschanged'";
        $res = TextStatsCache::exec_sql($sql, $conn);
        $ret = $res->fetch_assoc();
        $missing_key = $ret['c'] == '0';
        if ($missing_key)
            return true;

        // Check the stored value vs. the current max changed value.
        $sql = "select count(*) as c from settings
          where StKey = 'lastmaxstatuschanged'
          and convert(StValue, unsigned) < (select convert(unix_timestamp(max(wostatuschanged)), unsigned) from words);";
        $res = TextStatsCache::exec_sql($sql, $conn);
        $ret = $res->fetch_assoc();
        $needs_refresh = $ret['c'] == '1';
        $conn->close();
        return $needs_refresh;
    }

    /**
     * Refresh stats for any texts needing refresh.
     */
    public static function refresh() {
        if (!TextStatsCache::needs_refresh())
            return;

        $conn = TextStatsCache::getConnection();

        $sql_text_ids_with_updated_words = "
select src.TxID
from (
  select
  t.TxID,
  max(WoStatusChanged) as maxwsc
  from texts t
  inner join textitems2 on Ti2TxID = t.TxID
  inner join words on WoID = Ti2WoID
  group by t.TxID
) src
inner join textstatscache tsc on tsc.TxID = src.TxID
where tsc.UpdatedDate IS NULL OR maxwsc > tsc.updatedDate";

        TextStatsCache::do_refresh($sql_text_ids_with_updated_words, $conn);

        $sql = "insert ignore into settings (StKey, StValue)
          values('lastmaxstatuschanged', (select convert(unix_timestamp(max(wostatuschanged)), char(40)) from words))";
        TextStatsCache::exec_sql($sql, $conn);
    }


    /**
     * Force refresh stats for $text
     */
    public static function force_refresh($text) {
        $conn = TextStatsCache::getConnection();
        TextStatsCache::do_refresh("select {$text->getID()}", $conn);
    }


    public static function markStale(array $text_ids) {
        if (count($text_ids) == 0)
            return;
        $ids = implode(', ', $text_ids);
        $sql = "DELETE from textstatscache where TxID in ({$ids})";
        $conn = TextStatsCache::getConnection();
        TextStatsCache::exec_sql($sql, $conn);
    }


    // Private.

    private static function getConnection()
    {
        return Connection::getFromEnvironment();
    }

    private static function exec_sql($sql, $conn) {
        // echo $sql . "\n";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception($conn->error);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $stmt->get_result();
    }

}