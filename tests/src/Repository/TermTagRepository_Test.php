<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;

final class TermTagRepository_Test extends DatabaseTestBase
{

    private TermTag $t;

    public function childSetUp() {
        $this->t = new TermTag();
        $this->t->setText("Hola");
        $this->t->setComment("Hola comment");
        $this->termtag_repo->save($this->t, true);
    }

    public function test_save()  // V3-port: DONE tests/unit/models/test_TermTag.py
    {
        $sql = "select TgID, TgText, TgComment from tags";
        $expected = [ "1; Hola; Hola comment" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_new_dup_tag_text_fails()  // V3-port: DONE tests/unit/models/test_TermTag.py
    {
        $t = new TermTag();
        $t->setText("Hola");
        $t->setComment("Hola 2 comment");

        $this->expectException(Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->termtag_repo->save($t, true);
    }

    public function test_get_by_text()  // V3-port: DONE tests/unit/models/test_TermTag.py
    {
        $retrieved = $this->termtag_repo->findByText("Hola");
        $this->assertEquals($this->t->getId(), $retrieved->getId(), 'same item returned');
    }

    public function test_get_by_text_returns_null_if_not_exact_match()  // V3-port: DONE tests/unit/models/test_TermTag.py
    {
        $retrieved = $this->termtag_repo->findByText("hola");
        $this->assertNull($retrieved, 'not exact text = no match');
    }

    /**
     * @group ttdt
     */
    public function test_smoke_datatables_query()  // V3-port: DONE test unit termtags
    {
        $columns = [
            0 => [
                "data" => "0",
                "name" => "TgID",
                "searchable" => "false",
                "orderable" => "false"
            ],
            1 => [
                "data" => "1",
                "name" => "TgText",
                "searchable" => "true",
                "orderable" => "true"
            ],
        ];
        $params = [
            "draw" => "1",
            "columns" => $columns,
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

        $this->termtag_repo->getDataTablesList($params);
        $this->assertEquals(1, 1, 'smoke');
    }
}
