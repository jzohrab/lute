<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Repository/DataTablesMySqlQuery.php';

use PHPUnit\Framework\TestCase;
use App\Repository\DataTablesMySqlQuery;


final class DataTablesMySqlQuery_Test extends TestCase
{

    private string $basesql;
    private array $columns;
    private array $parameters;

    public function setUp(): void
    {
        $this->basesql = "select CatID, Color, Food from Cats";
        $this->columns = [
            0 => [
                "data" => "0",
                "name" => "CatID",
                "searchable" => "false",
                "orderable" => "false"
            ],
            1 => [
                "data" => "1",
                "name" => "Color",
                "searchable" => "true",
                "orderable" => "true"
            ],
            2 => [
                "data" => "2",
                "name" => "Food",
                "searchable" => "true",
                "orderable" => "true"
            ]
        ];

        // The $params sent by DataTables is tedious to set up ...
        $this->parameters = [
            "draw" => "1",
            "columns" => $this->columns,
            "order" => [
                0 => [
                    "column" => "1",
                    "dir" => "asc"
                ]
            ],
            "start" => "10",
            "length" => "50",
            "search" => [
                "value" => "",
                "regex" => "false"
            ]
        ];
    }


    private function assertHashesEqual($actual, $expected) {
        $flds = [ 'recordsTotal', 'recordsFiltered', 'data', 'params' ];
        foreach ($flds as $f)
            $this->assertEquals($actual[$f], $expected[$f], $f);
    }
    
    public function test_smoke_test()
    {
        $actual = DataTablesMySqlQuery::getSql($this->basesql, $this->parameters);
        $expected = [
            'recordsTotal' => 'select count(*) from (select CatID, Color, Food from Cats) realbase',
            'recordsFiltered' => 'select count(*) from (select CatID, Color, Food from Cats) realbase ',
            'data' => 'SELECT CatID, Color, Food FROM (select * from (select CatID, Color, Food from Cats) realbase  ORDER BY Color asc, Color, Food LIMIT 10, 50) src ORDER BY Color asc, Color, Food',
            'params' => []
        ];
        $this->assertHashesEqual($actual, $expected);
    }


    public function test_sorting()
    {
        $this->parameters["order"][0]["column"] = "2";
        $this->parameters["order"][0]["dir"] = "desc";

        $actual = DataTablesMySqlQuery::getSql($this->basesql, $this->parameters);
        $expected = [
            'recordsTotal' => 'select count(*) from (select CatID, Color, Food from Cats) realbase',
            'recordsFiltered' => "select count(*) from (select CatID, Color, Food from Cats) realbase ",
            'data' => "SELECT CatID, Color, Food FROM (select * from (select CatID, Color, Food from Cats) realbase  ORDER BY Food desc, Color, Food LIMIT 10, 50) src ORDER BY Food desc, Color, Food",
            'params' => []
        ];
        $this->assertHashesEqual($actual, $expected);
    }

    public function test_single_search()
    {
        $this->parameters["search"]["value"] = "XXX";

        $actual = DataTablesMySqlQuery::getSql($this->basesql, $this->parameters);

        $expected = [
            "recordsTotal" => "select count(*) from (select CatID, Color, Food from Cats) realbase",
            "recordsFiltered" => "select count(*) from (select CatID, Color, Food from Cats) realbase WHERE (Color LIKE CONCAT('%', :s0, '%') OR Food LIKE CONCAT('%', :s0, '%'))",
            "data" => "SELECT CatID, Color, Food FROM (select * from (select CatID, Color, Food from Cats) realbase WHERE (Color LIKE CONCAT('%', :s0, '%') OR Food LIKE CONCAT('%', :s0, '%')) ORDER BY Color asc, Color, Food LIMIT 10, 50) src ORDER BY Color asc, Color, Food",
            'params' => [ 's0' => 'XXX' ]
        ];
        $this->assertHashesEqual($actual, $expected);
    }

    public function test_multiple_search_terms()
    {
        $this->parameters["search"]["value"] = "XXX YYY";

        $actual = DataTablesMySqlQuery::getSql($this->basesql, $this->parameters);

        $expected = [
            "recordsTotal" => "select count(*) from (select CatID, Color, Food from Cats) realbase",
            "recordsFiltered" => "select count(*) from (select CatID, Color, Food from Cats) realbase WHERE (Color LIKE CONCAT('%', :s0, '%') OR Food LIKE CONCAT('%', :s0, '%')) AND (Color LIKE CONCAT('%', :s1, '%') OR Food LIKE CONCAT('%', :s1, '%'))",
            "data" => "SELECT CatID, Color, Food FROM (select * from (select CatID, Color, Food from Cats) realbase WHERE (Color LIKE CONCAT('%', :s0, '%') OR Food LIKE CONCAT('%', :s0, '%')) AND (Color LIKE CONCAT('%', :s1, '%') OR Food LIKE CONCAT('%', :s1, '%')) ORDER BY Color asc, Color, Food LIMIT 10, 50) src ORDER BY Color asc, Color, Food",
            'params' => [ 's0' => 'XXX', 's1' => 'YYY' ]
        ];
        $this->assertHashesEqual($actual, $expected);
    }


    private function assertWhereEquals($searchString, $expected) {
        $this->parameters["search"]["value"] = $searchString;
        $actual = DataTablesMySqlQuery::getSql($this->basesql, $this->parameters);
        $filtered = $actual["recordsFiltered"];
        $where = preg_replace('/.* WHERE /', '', $filtered);
        $this->assertEquals($expected, $where, $searchString);
    }

    public function test_search_regex_markers()
    {
        $this->assertWhereEquals('XXX', "(Color LIKE CONCAT('%', :s0, '%') OR Food LIKE CONCAT('%', :s0, '%'))");
        $this->assertWhereEquals('^XXX', "(Color LIKE CONCAT('', :s0, '%') OR Food LIKE CONCAT('', :s0, '%'))");
        $this->assertWhereEquals('XXX$', "(Color LIKE CONCAT('%', :s0, '') OR Food LIKE CONCAT('%', :s0, ''))");
        $this->assertWhereEquals('^XXX$', "(Color LIKE CONCAT('', :s0, '') OR Food LIKE CONCAT('', :s0, ''))");

        $this->assertWhereEquals('^XXX YYY$', "(Color LIKE CONCAT('', :s0, '%') OR Food LIKE CONCAT('', :s0, '%')) AND (Color LIKE CONCAT('%', :s1, '') OR Food LIKE CONCAT('%', :s1, ''))");
    }

}
