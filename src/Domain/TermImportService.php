<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\Entity\Book;
use App\Repository\LanguageRepository;
use App\Repository\TermTagRepository;
use App\Entity\Status;
use App\DTO\TermReferenceDTO;
use App\Domain\TermService;
use App\Utils\Connection;
use App\Repository\TermRepository;

class TermImportService {

    private TermRepository $term_repo;
    private LanguageRepository $lang_repo;
    private TermTagRepository $termtag_repo;
    private TermService $termsvc;


    /**
     * Convert a csv file to array of hashes.
     */
    public static function loadImportFile($filename): array {

        $makeHsh = function($arr) {
            $tarr = array_map(fn($s) => trim($s), $arr);
            return [
                'language' => $tarr[0],
                'term' => $tarr[1],
                'translation' => $tarr[2],
                'parent' => $tarr[3],
                'status' => $tarr[4],
                'tags' => $tarr[5],
                'pronunciation' => $tarr[6]
            ];
        };

        // First row must have specific headings, ensures good data.
        $temp = $makeHsh([1,2,3,4,5,6,7]);
        $colcount = count($temp);
        $required_headings = implode(', ', array_keys($temp));

        $importdata = [];
        $handle = fopen($filename, "r");
        if ($handle === false)
            throw new \Exception("Failure opening file {$filename}");

        $headings = fgetcsv($handle, 0);
        $headings = array_map(fn($s) => trim($s), $headings);
        $actual_headings = implode(', ', $headings);
        if ("{$actual_headings}" != "{$required_headings}") {
            fclose($handle);
            throw new \Exception('first row must have headings:' . $required_headings . '; got ' . $actual_headings);
        }
            
        while (($rec = fgetcsv($handle, 0)) !== false) {
            if (count($rec) != $colcount) {
                fclose($handle);
                throw new \Exception("Each row must have {$colcount} columns.");
            }
            $importdata[] = $makeHsh($rec);
        }
        fclose($handle);

        // dump($importdata);
        return $importdata;
    }

    public function __construct(
        LanguageRepository $lang_repo,
        TermRepository $term_repo,
        TermTagRepository $termtag_repo
    ) {
        $this->lang_repo = $lang_repo;
        $this->term_repo = $term_repo;
        $this->termtag_repo = $termtag_repo;

        $this->termsvc = new TermService($term_repo);
    }

    /** Kills everything in the entity manager. */
    private function flushClear() {
        $this->term_repo->flush();
        $this->term_repo->clear();
    }

    private function getUniqueLanguageNames($import) {
        $langs = array_map(fn($hsh) => $hsh['language'], $import);
        $langs = array_map(fn($s) => trim($s), $langs);
        return array_unique($langs);
    }

    private function validateLanguages($import) {
        $langs = $this->getUniqueLanguageNames($import);
        foreach ($langs as $s) {
            $ltest = $this->lang_repo->findOneByName($s);
            if ($ltest == null)
                throw new \Exception('Unknown language "' . $s . '"');
        }
    }

    private function validateStatuses($import) {
        $statuses = array_map(fn($hsh) => $hsh['status'], $import);
        $statuses = array_map(fn($s) => trim($s), $statuses);
        $statuses = array_unique($statuses);
        foreach ($statuses as $s) {
            if ($this->getStatus($s) == null)
                throw new \Exception('Status must be one of 1,2,3,4,5,I,W, or blank');
        }
    }

    private function validateTermsExist($import) {
        $blanks = array_filter($import, fn($hsh) => trim($hsh['term']) == '');
        if (count($blanks) > 0)
            throw new \Exception('Term is required');
    }

    private function validateNoDuplicateTerms($import) {
        $langterms = array_map(fn($hsh) => $hsh['language'] . '_' . mb_strtolower($hsh['term']), $import);
        $c = count($langterms);
        $cu = count(array_unique($langterms));
        if ($c != $cu)
            throw new \Exception('Duplicate terms in import');
    }


    private function getStatus($s): ?int {
        $map = [ ''=>1, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, 'W'=>Status::WELLKNOWN, 'I'=>Status::IGNORED ];
        if (array_key_exists($s, $map))
            return $map[$s];
        return null;
    }
        
    // Get all necessary Langs.
    private function createLangsDict($import) {
        $dict = [];
        $langs = array_map(fn($hsh) => $hsh['language'], $import);
        $langs = array_filter($langs, fn($s) => trim($s) != '');
        $langs = array_unique($langs);
        foreach ($langs as $s) {
            $dict[$s] = $this->lang_repo->findOneByName($s);
        }
        return $dict;
    }

    // Get all necessary TermTags.
    private function createTermTagsDict($import) {
        $dict = [];
        $tags = array_map(fn($hsh) => $hsh['tags'], $import);
        $tags = array_filter($tags, fn($s) => trim($s) != '');
        $tags = array_map(fn($s) => explode(',', $s), $tags);
        $tags = array_merge([], ...$tags);
        $tags = array_map(fn($s) => trim($s), $tags);
        $tags = array_filter($tags, fn($s) => trim($s) != '');
        $tags = array_unique($tags);
        foreach ($tags as $t) {
            $dict[$t] = $this->termtag_repo->findOrCreateByText($t);
        }
        return $dict;
    }

    /** NOTE: this fails for large import files when APP_ENV = TEST. */
    public function importFile($filename) {
        $import = TermImportService::loadImportFile($filename);
        return $this->doImport($import);
    }

    /** Import a data record. */
    private function importRow($rec, $lang, $tagsdict) {
        $t = new Term($lang, $rec['term']);
        $t->setTranslation($rec['translation']);
        $t->setStatus($this->getStatus($rec['status']));
        $t->setRomanization($rec['pronunciation']);

        $addTags = function() use ($t, $rec, $tagsdict) {
            $tags = explode(',', $rec['tags']);
            $tags = array_map(fn($s) => trim($s), $tags);
            foreach ($tags as $tag) {
                $t->addTermTag($tagsdict[$tag]);
            }
        };
        if ($rec['tags'] != '')
            $addTags();

        $this->term_repo->save($t, false);
        $t = null;
    }

    /** NOTE: this fails for large import files when APP_ENV = TEST. */
    public function doImport($import) {
        $this->validateLanguages($import);
        $this->validateTermsExist($import);
        $this->validateStatuses($import);
        $this->validateNoDuplicateTerms($import);

        $created = 0;
        $skipped = 0;
        $this->term_repo->stopSqlLog();
        foreach (array_chunk($import, 100) as $batch) {
            $tagsdict = $this->createTermTagsDict($batch);
            $langsdict = $this->createLangsDict($batch);
            $svc = new TermService($this->term_repo);
            foreach ($batch as $hsh) {
                $lgname = $hsh['language'];
                $lang = $langsdict[$lgname];
                if ($svc->find($hsh['term'], $lang) == null) {
                    $this->importRow($hsh, $lang, $tagsdict);
                    $created += 1;
                }
                else {
                    $skipped += 1;
                }
            }
            $this->flushClear();
        }

        $langs = $this->getUniqueLanguageNames($import);
        foreach ($langs as $s) {
            $import_for_lang = array_filter($import, fn($h) => $h['language'] == $s && $h['parent'] != '');
            $mappings = array_map(
                fn($h) => [ 'parent' => $h['parent' ], 'child' => $h['term'] ],
                $import_for_lang
            );
            $tms = new TermMappingService($this->term_repo);
            $lang = $this->lang_repo->findOneByName($s);
            $mapstats = $tms->mapParents($lang, $this->lang_repo, $mappings);
            $created += $mapstats['created'];
        }

        $stats = [
            'created' => $created,
            'skipped' => $skipped
        ];

        return $stats;
    }

}