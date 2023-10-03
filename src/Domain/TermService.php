<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\Repository\LanguageRepository;
use App\Entity\Status;
use App\DTO\TermReferenceDTO;
use App\Utils\Connection;
use App\Repository\TermRepository;

class TermService {

    private TermRepository $term_repo;
    private array $pendingTerms;

    public function __construct(
        TermRepository $term_repo
    ) {
        $this->term_repo = $term_repo;
        $this->pendingTerms = array();
    }

    public function add(Term $term, bool $flush = true) {
        $this->pendingTerms[] = $term;
        $this->term_repo->save($term, false);
        if ($flush) {
            $this->flush();
        }
    }

    public function flush() {
        $this->term_repo->flush();
        $this->pendingTerms = array();
    }

    public function remove(Term $term): void
    {
        $this->term_repo->remove($term, false);
        $this->term_repo->flush();
    }

    /** Force delete of FlashMessage.
     *
     * I couldn't get the messages to actually get removed from the db
     * without directly hitting the db like this (see the comments in
     * ReadingFacade) ... I suspect something is getting cached but
     * can't sort it out.  This works fine, so it will do for now!
    */
    public function killFlashMessageFor(Term $term): void {
        $conn = Connection::getFromEnvironment();
        $sql = 'delete from wordflashmessages where WfWoID = ' . $term->getID();
        $conn->query($sql);
    }

    /**
     * Find a term by an exact match.
     */
    public function find(string $value, Language $lang): ?Term {
        $spec = new Term($lang, $value);
        return $this->term_repo->findBySpecification($spec);
    }

    /**
     * Get fully populated Term from database, or create a new one.
     *
     * @param lid  int    LgID, the language ID
     * @param text string
     *
     * @return Term
     */
    public function findOrNew(Language $language, string $text): Term
    {
        $t = $this->find($text, $language);
        if (null != $t)
            return $t;
        return new Term($language, $text);
    }

    /**
     * Find Terms by matching text.
     */
    public function findMatches(string $value, Language $lang, int $maxResults = 50): array
    {
        $spec = new Term($lang, $value);
        return $this->term_repo->findLikeSpecification($spec, $maxResults);
    }

    /**
     * Find all terms in a string.
     */
    public function findAllInString(string $s, Language $lang): array
    {
        $tokens = $lang->getParsedTokens($s);

        // 1. Build query to get terms.  Building full query instead
        // of using named params, only using query once so no benefit
        // to parameterizing.
        $conn = Connection::getFromEnvironment();

        $wordtokens = array_filter($tokens, fn($t) => $t->isWord);
        $parser = $lang->getParser();
        $tokstrings = array_map(fn($t) => $parser->getLowercase($t->token), $wordtokens);
        $tokstrings = array_unique($tokstrings);
        $termcriteria = array_map(fn($s) => $conn->quote($s), $tokstrings);
        $termcriteria = implode(',', $termcriteria);

        $zws = mb_chr(0x200B); // zero-width space.
        $is = array_map(fn($t) => $parser->getLowercase($t->token), $tokens);
        $lctokstring = $zws . implode($zws, $is) . $zws;
        $lctokstring = $conn->quote($lctokstring);

        // Querying all words that match the text is very slow, so
        // breaking it up into two parts.
        $lgid = $lang->getLgID();
        $sql = "select WoID from words
            where wotextlc in ($termcriteria)
            and WoTokenCount = 1 and WoLgID = $lgid

            UNION

            select WoID from words
            where WoLgID = $lgid AND
            WoTokenCount > 1 AND
            instr($lctokstring, WoTextLC) > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $this->term_repo->findBy(['id' => $ids]);
    }


    /**
     * Find references.
     */
    public function findReferences(Term $term): array
    {
        $conn = Connection::getFromEnvironment();
        $ret = [
            'term' => $this->getReferences($term, $conn),
            'children' => $this->getChildReferences($term, $conn),
            'parents' => $this->getParentReferences($term, $conn),
        ];
        return $ret;
    }

    private function buildTermReferenceDTOs($termlc, $res) {
        $ret = [];
        $zws = mb_chr(0x200B); // zero-width space.
        while (($row = $res->fetch(\PDO::FETCH_ASSOC))) {
            $s = $row['SeText'];
            $s = trim($s);

            $pattern = "/{$zws}({$termlc}){$zws}/ui";
            $replacement = "{$zws}<b>" . '${1}' . "</b>{$zws}";
            $s = preg_replace($pattern, $replacement, $s);
            $s = str_replace(
                [ $zws, '¶' ],
                [ '', '' ],
                $s
            );
            $ret[] = new TermReferenceDTO($row['TxID'], $row['TxTitle'], $s);
        }
        return $ret;
    }

    private function getReferences($term, $conn): array {
        if ($term == null)
            return [];
        $s = $term->getTextLC();
        $sql = "select distinct
            TxID,
            BkTitle || ' (' || TxOrder || '/' || pc.c || ')' as TxTitle,
            SeText
          from sentences
          inner join texts on TxID = SeTxID
          inner join books on BkID = texts.TxBkID
          inner join (
            select TxBkID, count(*) as c
            from texts
            group by TxBkID
          ) pc on pc.TxBkID = texts.TxBkID
          WHERE TxReadDate is not null
          AND lower(SeText) like '%' || char(0x200B) || ? || char(0x200B) || '%'
          LIMIT 20";
        $stmt = $conn->prepare($sql);

        // TODO:sqlite uses SQLITE3_TEXT
        $stmt->bindValue(1, $s, \PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $this->buildTermReferenceDTOs($s, $stmt);
    }

    private function getAllRefs($terms, $conn): array {
        $ret = [];
        foreach ($terms as $term) {
            $ret[] = $this->getReferences($term, $conn);
        }
        return array_merge([], ...$ret);
    }

    private function getParentReferences($term, $conn): array {
        $ret = [];
        foreach ($term->getParents() as $parent) {
            $ret[] = [
                'term' => $parent->getTextLC(),
                'refs' => $this->getFamilyReferences($parent, $term, $conn)
            ];
        }
        return $ret;
    }

    private function getFamilyReferences($parent, $term, $conn): array {
        if ($term == null || $parent == null)
            return [];
        $family = [$parent];
        foreach ($parent->getChildren() as $s)
            $family[] = $s;
        $family = array_filter($family, fn($t) => $t->getID() != $term->getID());
        return $this->getAllRefs($family, $conn);
    }

    private function getChildReferences($term, $conn): array {
        if ($term == null)
            return [];
        return $this->getAllRefs($term->getChildren(), $conn);
    }

}