<?php

namespace App\Controller;

use App\Utils\Connection;
use App\Utils\SqliteHelper;
use App\Parse\JapaneseParser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Information/hack methods for use during acceptance testing.
 *
 * THESE ARE DANGEROUS METHODS, and are not included in the release,
 * or should not be!
 */
#[Route('/dangerous')]
class DevelopmentController extends AbstractController
{

    /**
     * These methods should only be run on the test db.
     */
    private function ensure_is_test_db() {
        $dbf = SqliteHelper::DbFilename();
        if (!str_contains($dbf, 'test_lute')) {
            throw new \Exception("Dangerous method, only possible on test_lute database.");
        }
    }

    #[Route('/create_test_db', name: 'app_danger_createdb', methods: ['GET'])]
    public function create_test_db(): Response
    {
        $this->ensure_is_test_db();
        SqliteHelper::CreateDb();
        $this->addFlash('notice', 'TEST DB CREATED');
        return $this->redirectToRoute('app_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/delete_all_terms', name: 'app_danger_delete_terms', methods: ['GET'])]
    public function delete_all_terms(): Response
    {
        $this->ensure_is_test_db();
        $sql = "delete from words";
        $conn = Connection::getFromEnvironment();
        $conn->query($sql);

        $this->addFlash('notice', 'ALL TERMS DELETED');
        return $this->redirectToRoute('app_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Return 'yes' or 'no' text response only */
    #[Route('/mecab_installed', name: 'app_danger_mecab_check', methods: ['GET'])]
    public function mecab_installed(): Response
    {
        $ret = 'no';
        if (JapaneseParser::MeCab_installed()) {
            $ret = 'yes';
        }
        $ret = "<html><body><h1>Result</h1><p>" . $ret . "</p></body></html>";
        return new Response($ret);
    }

    /** Get SQL result as text. */
    #[Route('/sqlresult/{sql}', name: 'app_danger_sqlresult', methods: ['GET'])]
    public function sql_result(string $sql): Response
    {
        $this->ensure_is_test_db();
        $conn = Connection::getFromEnvironment();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $content = [];
        while($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $rowvals = array_values($row);
            $null_to_NULL = function($v) {
                if ($v === null)
                    return 'NULL';
                $zws = mb_chr(0x200B);
                if (is_string($v) && str_contains($v, $zws))
                    return str_replace($zws, '/', $v);
                return $v;
            };
            $content[] = implode('; ', array_map($null_to_NULL, $rowvals));
        }

        $ret = "<html><body><h1>Result</h1><p>" . implode("</p><p>", $content) . "</p></body></html>";
        return new Response($ret);
    }

}
