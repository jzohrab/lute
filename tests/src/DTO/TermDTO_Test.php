<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Language;
use App\Entity\TermTag;
use App\Entity\Term;
use App\Domain\TermService;
use App\DTO\TermDTO;

final class TermDTO_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function dumpTerm(Term $t): string {
        $tt = [];
        foreach ($t->getTermTags() as $tag) {
            $tt[] = $tag->getText();
        }
        $pt = '<none>';
        if (count($t->getParents()) > 0) {
            $arr = [];
            foreach ($t->getParents() as $p)
                $arr = $p->getText();
            $pt = implode(', ', $arr);
        }
        $term_svc = [
            'id' => $t->getID(),
            'lang' => $t->getLanguage()->getLgName(),
            'text' => $t->getText(),
            'textlc' => $t->getTextLC(),
            'status' => $t->getStatus(),
            'tc' => $t->getTokenCount(),
            'tr' => $t->getTranslation(),
            'r' => $t->getRomanization(),
            'i' => $t->getCurrentImage(),
            'tt' => implode(', ', $tt),
            'pt' => $pt
        ];
        return json_encode($term_svc);
    }

    private function assertTermsEqual(Term $a, Term $b) {
        $da = $this->dumpTerm($a);
        $db = $this->dumpTerm($b);
        $this->assertEquals($da, $db, "terms are the same");
    }

    public function test_smoke_test_simple_dto() {
        $english = new Language();
        $english->setLgName('English');

        $t = new Term($english, 'Hello');
        $t->setCurrentImage('hello.png');

        $dto = $t->createTermDTO();

        $this->assertEquals($dto->id, $t->getID(), 'id the same');
        $this->assertEquals($dto->Text, $t->getText(), 'text');
        $this->assertEquals(count($dto->termParents), 0, 'no parents');
        $this->assertEquals(count($dto->termTags), 0, 'no tags');

        $loaded = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertTermsEqual($t, $loaded);
    }

    public function test_dto_gets_tags_as_array()
    {
        $english = new Language();
        $english->setLgName('English');

        $t = new Term($english, 'Hello');
        $t->addTermTag(TermTag::makeTermTag('a'));
        $t->addTermTag(TermTag::makeTermTag('b'));

        $dto = $t->createTermDTO();
        $this->assertEquals(implode(', ', $dto->termTags), 'a, b');
    }

    public function test_buildTerm_returns_existing_term_in_language()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('Hola');
        $this->term_service->add($t, true);

        $dto = $t->createTermDTO();
        $loaded = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertEquals($t->getID(), $loaded->getID(), "saved item returned");
    }

    public function test_buildTerm_with_new_parent_text_creates_new_parent()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->termParents = ['perro'];

        $perros = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);

        $parents = $perros->getParents();
        $this->assertEquals(count($parents), 1, 'have parent');
        $this->assertEquals($parents[0]->getText(), 'perro', 'parent of perros is perro');
    }

    /**
     * @group dtoparent
     */
    public function test_buildTerm_with_new_parent_parent_gets_translation_and_image_and_tag()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->CurrentImage = 'someimage.jpeg';
        $dto->Translation = 'transl';
        $dto->termParents = ['perro'];
        $dto->termTags[] = 'newtag';

        $perros = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertEquals($perros->getCurrentImage(), 'someimage', 'have img, but WITHOUT jpeg extension');
        $this->assertEquals($perros->getTranslation(), 'transl', 'c trans');

        $parents = $perros->getParents();
        $this->assertEquals(count($parents), 1, 'have parent');
        $parent = $parents[0];
        $this->assertEquals(count($parent->getTermTags()), 1, 'tag count');
        $this->assertEquals($parent->getTermTags()[0]->getText(), 'newtag');
        $this->assertEquals($parent->getCurrentImage(), 'someimage', 'parent have img no jpeg ext');
        $this->assertEquals($parent->getTranslation(), 'transl', 'parent trans');
    }

    /** Parent translations **********/

    private function assert_translations($parent_exists, $existing_parent_translation, $childtrans, $parenttrans) {
        if ($parent_exists) {
            $p = new Term($this->spanish, 'parent');
            $p->setTranslation($existing_parent_translation);
            $this->term_repo->save($p, true);
        }

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'child';
        $dto->Translation = 'childX';
        $dto->termParents = ['parent'];
        $child = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $parents = $child->getParents();
        $this->assertEquals(count($parents), 1, 'have parent');

        $parent = $parents[0];
        $this->assertEquals($child->getTranslation() ?? 'NULL', $childtrans ?? 'NULL', 'child trans');
        $parent = $parents[0];
        $this->assertEquals($parent->getTranslation() ?? 'NULL', $parenttrans ?? 'NULL', 'parent trans');
    }

    /**
     * @group dtoparent_translation
     */
    public function test_new_term_new_parent()
    {
        $this->assert_translations(false, null, null, 'childX');
    }

    /**
     * @group dtoparent_translation
     */
    public function test_new_term_existing_parent_with_translation() {
        // existing parent translation stays as-is
        $this->assert_translations(true, 'parent trans', 'childX', 'parent trans');
    }

    /**
     * @group dtoparent_translation
     */
    public function test_new_term_existing_parent_no_translation() {
        // existing parent translation replaced
        $this->assert_translations(true, null, null, 'childX');  // new state state
    }


    /**
     * @group dtoparent
     */
    public function test_cannot_set_dto_term_as_its_own_parent()
    {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';
        $dto->termParents = ['perro'];
        $perro = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertEquals(count($perro->getParents()), 0, 'no parent');
    }

    /**
     * @group dtoparent
     */
    public function test_add_term_existing_parent_creates_link() {
        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText('perro');
        $this->term_service->add($p);

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->termParents = ['perro'];

        $perros = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);

        $parent = $perros->getParents()[0];
        $this->assertTrue($parent != null, 'have parent');
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getID(), $p->getID(), 'existing perro found');
    }

    /**
     * @group dtoparent
     */
    public function test_add_term_existing_parent_parent_gets_translation_if_missing() {
        $p = new Term($this->spanish, 'perro');
        $this->term_service->add($p);

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->Translation = 'translation';
        $dto->termParents = ['perro'];

        $perros = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);

        $parent = $perros->getParents()[0];
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getTranslation(), 'translation', 'translation applied');

        $perrito_dto = new TermDTO();
        $perrito_dto->language = $this->spanish;
        $perrito_dto->Text = 'perrito';
        $perrito_dto->Translation = 'small dog';
        $perrito_dto->termParents = ['perro'];
        $perrito = TermDTO::buildTerm($perrito_dto, $this->term_service, $this->termtag_repo);

        $parent = $perrito->getParents()[0];
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getTranslation(), 'translation', 'existing transl kept');
    }

    public function test_buildTerm_returns_new_term_if_no_match() {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';

        $perro = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertTrue($perro->getID() == null, "new term, no id");
    }

    public function test_buildTerm_returns_tags_as_TermTags() {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';
        $dto->termTags[] = 'hi';
        $dto->termTags[] = 'there';

        $perro = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $tt = $perro->getTermTags();
        $this->assertEquals(count($tt), 2, '2 tags');
        $this->assertEquals($tt[0]->getText(), 'hi', 'sanity check');

        $this->term_service->add($perro, true);  // sanity check only.
    }

    // works even if tags already exist in repo.
    public function test_buildTerm_returns_tags_as_existing_TermTags() {
        $there = $this->termtag_repo->findOrCreateByText('there');
        $this->termtag_repo->save($there, true);

        DbHelpers::assertRecordcountEquals("select * from tags", 1, "1 tag");

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';
        $dto->termTags[] = 'hi';
        $dto->termTags[] = 'there';

        $perro = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $tt = $perro->getTermTags();
        $this->assertEquals(count($tt), 2, '2 tags');
        $this->assertEquals($tt[0]->getText(), 'hi', 'sanity check');

        $this->term_service->add($perro, true);  // sanity check only.
        DbHelpers::assertRecordcountEquals("select * from tags", 2, "now 2 after save");

        $reloaded = $perro->createTermDTO();
        $this->assertEquals(implode(', ', $reloaded->termTags), 'hi, there');
    }

    public function test_remove_tags_from_dto_removes_them_from_saved_Term() {
        $t = new Term($this->english, 'Hello');
        $t->addTermTag(TermTag::makeTermTag('a'));
        $this->term_service->add($t, true);
        $retrieved = $this->term_service->find('Hello', $this->english);
        $this->assertEquals(count($retrieved->getTermTags()), 1, '1 tag');

        $dto = $t->createTermDTO();
        $this->assertEquals(implode(', ', $dto->termTags), 'a');

        $dto->termTags = array(); // = remove all tags.
        $t = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->assertEquals(count($t->getTermTags()), 0, 'now 0 tags');

        $this->term_service->add($t, true);
        $retrieved = $this->term_service->find('Hello', $this->english);
        $this->assertTrue($retrieved != null, 'have term');
        $this->assertEquals(count($retrieved->getTermTags()), 0, '0 tags saved in db');
    }

    // "composer dev:data:load" and clicking on a term, adding a new
    // parent, and adding a tag to the term, then saving, was causing
    // problems.
    public function test_creating_save_term_with_new_parent_and_tags_works()
    {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->termParents = ['perro'];
        $dto->termTags[] = 'hi';

        $t = TermDTO::buildTerm($dto, $this->term_service, $this->termtag_repo);
        $this->term_service->add($t, true);

        $retrieved = $this->term_service->find('perro', $this->spanish);
        $this->assertTrue($retrieved != null, 'have term');
        $this->assertEquals(count($retrieved->getTermTags()), 1, '1 tags on perro');
    }

}
