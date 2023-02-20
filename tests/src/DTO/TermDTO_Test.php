<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Language;
use App\Entity\TermTag;
use App\Entity\Term;
use App\Domain\Dictionary;
use App\DTO\TermDTO;

final class TermDTO_Test extends DatabaseTestBase
{

    private Dictionary $dictionary;

    public function childSetUp(): void
    {
        $this->load_languages();

        $this->dictionary = new Dictionary(
            $this->term_repo
        );
    }

    private function dumpTerm(Term $t): string {
        $tt = [];
        foreach ($t->getTermTags() as $tag) {
            $tt[] = $tag->getText();
        }
        $pt = '<none>';
        if ($t->getParent() != null)
            $pt = $t->getParent()->getText();
        $dict = [
            'id' => $t->getID(),
            'lang' => $t->getLanguage()->getLgName(),
            'text' => $t->getText(),
            'textlc' => $t->getTextLC(),
            'status' => $t->getStatus(),
            'wc' => $t->getWordCount(),
            'tr' => $t->getTranslation(),
            'r' => $t->getRomanization(),
            's' => $t->getSentence(),
            'i' => $t->getCurrentImage(),
            'tt' => implode(', ', $tt),
            'pt' => $pt
        ];
        return json_encode($dict);
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
        $this->assertTrue($dto->ParentText == null, 'null parent');
        $this->assertEquals(count($dto->termTags), 0, 'no tags');

        $loaded = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
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
        $this->dictionary->add($t, true);

        $dto = $t->createTermDTO();
        $loaded = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->assertEquals($t->getID(), $loaded->getID(), "saved item returned");
    }

    public function test_buildTerm_with_new_parent_text_creates_new_parent()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->dictionary->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->ParentText = 'perro';

        $perros = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);

        $parent = $perros->getParent();
        $this->assertTrue($parent != null, 'have parent');
        $this->assertEquals($parent->getText(), 'perro', 'parent of perros is perro');
    }

    /**
     * @group dtoparent
     */
    public function test_buildTerm_with_new_parent_parent_gets_translation_and_image_and_tag()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->dictionary->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->CurrentImage = 'someimage.jpeg';
        $dto->Translation = 'transl';
        $dto->ParentText = 'perro';
        $dto->termTags[] = 'newtag';

        $perros = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->assertEquals($perros->getCurrentImage(), 'someimage.jpeg', 'have img');
        $this->assertEquals($perros->getTranslation(), 'transl', 'c trans');

        $parent = $perros->getParent();
        $this->assertTrue($parent != null, 'have parent');
        $this->assertEquals(count($parent->getTermTags()), 1, 'tag count');
        $this->assertEquals($parent->getTermTags()[0]->getText(), 'newtag');
        $this->assertEquals($parent->getCurrentImage(), 'someimage.jpeg', 'parent have img');
        $this->assertEquals($parent->getTranslation(), 'transl', 'parent trans');
    }

    /**
     * @group dtoparent
     */
    public function test_cannot_set_dto_term_as_its_own_parent()
    {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';
        $dto->ParentText = 'perro';
        $perro = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->assertTrue($perro->getParent() == null, 'no parent');
    }

    /**
     * @group dtoparent
     */
    public function test_add_term_existing_parent_creates_link() {
        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText('perro');
        $this->dictionary->add($p);

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->ParentText = 'perro';

        $perros = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);

        $parent = $perros->getParent();
        $this->assertTrue($parent != null, 'have parent');
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getID(), $p->getID(), 'existing perro found');
    }

    /**
     * @group dtoparent
     */
    public function test_add_term_existing_parent_parent_gets_translation_if_missing() {
        $p = new Term($this->spanish, 'perro');
        $this->dictionary->add($p);

        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perros';
        $dto->Translation = 'translation';
        $dto->ParentText = 'perro';

        $perros = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);

        $parent = $perros->getParent();
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getTranslation(), 'translation', 'translation applied');

        $perrito_dto = new TermDTO();
        $perrito_dto->language = $this->spanish;
        $perrito_dto->Text = 'perrito';
        $perrito_dto->Translation = 'small dog';
        $perrito_dto->ParentText = 'perro';
        $perrito = TermDTO::buildTerm($perrito_dto, $this->dictionary, $this->termtag_repo);

        $parent = $perrito->getParent();
        $this->assertEquals($parent->getText(), 'perro', 'which is perro');
        $this->assertEquals($parent->getTranslation(), 'translation', 'existing transl kept');
    }

    public function test_buildTerm_returns_new_term_if_no_match() {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';

        $perro = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->assertTrue($perro->getID() == null, "new term, no id");
    }

    public function test_buildTerm_returns_tags_as_TermTags() {
        $dto = new TermDTO();
        $dto->language = $this->spanish;
        $dto->Text = 'perro';
        $dto->termTags[] = 'hi';
        $dto->termTags[] = 'there';

        $perro = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $tt = $perro->getTermTags();
        $this->assertEquals(count($tt), 2, '2 tags');
        $this->assertEquals($tt[0]->getText(), 'hi', 'sanity check');

        $this->dictionary->add($perro, true);  // sanity check only.
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

        $perro = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $tt = $perro->getTermTags();
        $this->assertEquals(count($tt), 2, '2 tags');
        $this->assertEquals($tt[0]->getText(), 'hi', 'sanity check');

        $this->dictionary->add($perro, true);  // sanity check only.
        DbHelpers::assertRecordcountEquals("select * from tags", 2, "now 2 after save");

        $reloaded = $perro->createTermDTO();
        $this->assertEquals(implode(', ', $reloaded->termTags), 'hi, there');
    }

    public function test_remove_tags_from_dto_removes_them_from_saved_Term() {
        $t = new Term($this->english, 'Hello');
        $t->addTermTag(TermTag::makeTermTag('a'));
        $this->dictionary->add($t, true);
        $retrieved = $this->dictionary->find('Hello', $this->english);
        $this->assertEquals(count($retrieved->getTermTags()), 1, '1 tag');

        $dto = $t->createTermDTO();
        $this->assertEquals(implode(', ', $dto->termTags), 'a');

        $dto->termTags = array(); // = remove all tags.
        $t = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->assertEquals(count($t->getTermTags()), 0, 'now 0 tags');

        $this->dictionary->add($t, true);
        $retrieved = $this->dictionary->find('Hello', $this->english);
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
        $dto->ParentText = 'perro';
        $dto->termTags[] = 'hi';

        $t = TermDTO::buildTerm($dto, $this->dictionary, $this->termtag_repo);
        $this->dictionary->add($t, true);

        $retrieved = $this->dictionary->find('perro', $this->spanish);
        $this->assertTrue($retrieved != null, 'have term');
        $this->assertEquals(count($retrieved->getTermTags()), 1, '1 tags on perro');
    }

}
