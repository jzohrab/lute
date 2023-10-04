<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Domain\SpaceDelimitedParser;
use App\Domain\JapaneseParser;
use App\Domain\ClassicalChineseParser;
use App\Domain\TurkishParser;
use App\Domain\ParsedTokenSaver;
use Symfony\Component\Yaml\Yaml;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: 'languages')]
class Language
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'LgID', type: Types::SMALLINT)]
    private ?int $LgID = null;

    #[ORM\Column(name: 'LgName', length: 40)]
    private ?string $LgName = null;

    #[ORM\Column(name: 'LgDict1URI', length: 200)]
    #[Assert\Regex('/###/', message: 'Please specify the term placeholder with ###')]
    private ?string $LgDict1URI = null;

    #[ORM\Column(name: 'LgDict2URI', length: 200)]
    #[Assert\Regex('/###/', message: 'Please specify the term placeholder with ###')]
    private ?string $LgDict2URI = null;

    #[ORM\Column(name: 'LgGoogleTranslateURI', length: 200)]
    #[Assert\Regex('/###/', message: 'Please specify the term placeholder with ###')]
    private ?string $LgGoogleTranslateURI = null;

    #[ORM\Column(name: 'LgCharacterSubstitutions', length: 500)]
    private ?string $LgCharacterSubstitutions = "´='|`='|’='|‘='|...=…|..=‥";

    #[ORM\Column(name: 'LgRegexpSplitSentences', length: 500)]
    private ?string $LgRegexpSplitSentences = '.!?';

    #[ORM\Column(name: 'LgExceptionsSplitSentences', length: 500)]
    private ?string $LgExceptionsSplitSentences = 'Mr.|Mrs.|Dr.|[A-Z].|Vd.|Vds.';

    #[ORM\Column(name: 'LgRegexpWordCharacters', length: 500)]
    private ?string $LgRegexpWordCharacters = 'a-zA-ZÀ-ÖØ-öø-ȳáéíóúÁÉÍÓÚñÑ';

    #[ORM\Column(name: 'LgRemoveSpaces')]
    private ?bool $LgRemoveSpaces = false;

    #[ORM\Column(name: 'LgSplitEachChar')]
    private ?bool $LgSplitEachChar = false;

    #[ORM\Column(name: 'LgRightToLeft')]
    private ?bool $LgRightToLeft = false;

    #[ORM\Column(name: 'LgShowRomanization')]
    private bool $LgShowRomanization = false;

    #[ORM\Column(name: 'LgParserType', length: 20)]
    private string $LgParserType = 'spacedel';

    #[ORM\OneToMany(targetEntity: 'Book', mappedBy: 'Language', fetch: 'EXTRA_LAZY')]
    private Collection $books;

    #[ORM\OneToMany(targetEntity: 'Term', mappedBy: 'language', fetch: 'EXTRA_LAZY')]
    private Collection $terms;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->terms = new ArrayCollection();
    }

    public function __toString(): string {
        return $this->LgID . ': ' . $this->LgName;
    }

    public function getLgID(): ?int
    {
        return $this->LgID;
    }

    public function setLgID(int $LgID): self
    {
        $this->LgID = $LgID;

        return $this;
    }

    public function getLgName(): ?string
    {
        return $this->LgName;
    }

    public function setLgName(string $LgName): self
    {
        $this->LgName = $LgName;

        return $this;
    }

    public function getLgDict1URI(): ?string
    {
        return $this->LgDict1URI;
    }

    public function setLgDict1URI(string $LgDict1URI): self
    {
        $this->LgDict1URI = $LgDict1URI;

        return $this;
    }

    public function getLgDict2URI(): ?string
    {
        return $this->LgDict2URI;
    }

    public function setLgDict2URI(?string $LgDict2URI): self
    {
        $this->LgDict2URI = $LgDict2URI;

        return $this;
    }

    public function getLgGoogleTranslateURI(): ?string
    {
        return $this->LgGoogleTranslateURI;
    }

    public function setLgGoogleTranslateURI(string $LgGoogleTranslateURI): self
    {
        $this->LgGoogleTranslateURI = $LgGoogleTranslateURI;

        return $this;
    }

    public function getLgCharacterSubstitutions(): ?string
    {
        return $this->LgCharacterSubstitutions;
    }

    public function setLgCharacterSubstitutions(string $LgCharacterSubstitutions): self
    {
        $this->LgCharacterSubstitutions = $LgCharacterSubstitutions;

        return $this;
    }

    public function getLgRegexpSplitSentences(): ?string
    {
        return $this->LgRegexpSplitSentences;
    }

    public function setLgRegexpSplitSentences(string $LgRegexpSplitSentences): self
    {
        $this->LgRegexpSplitSentences = $LgRegexpSplitSentences;

        return $this;
    }

    public function getLgExceptionsSplitSentences(): ?string
    {
        return $this->LgExceptionsSplitSentences;
    }

    public function setLgExceptionsSplitSentences(string $LgExceptionsSplitSentences): self
    {
        $this->LgExceptionsSplitSentences = $LgExceptionsSplitSentences;

        return $this;
    }

    public function getLgRegexpWordCharacters(): ?string
    {
        return $this->LgRegexpWordCharacters;
    }

    public function setLgRegexpWordCharacters(string $LgRegexpWordCharacters): self
    {
        $this->LgRegexpWordCharacters = $LgRegexpWordCharacters;

        return $this;
    }

    public function isLgRemoveSpaces(): ?bool
    {
        return $this->LgRemoveSpaces;
    }

    public function setLgRemoveSpaces(bool $LgRemoveSpaces): self
    {
        $this->LgRemoveSpaces = $LgRemoveSpaces;

        return $this;
    }

    public function isLgSplitEachChar(): ?bool
    {
        return $this->LgSplitEachChar;
    }

    public function setLgSplitEachChar(bool $LgSplitEachChar): self
    {
        $this->LgSplitEachChar = $LgSplitEachChar;

        return $this;
    }

    public function isLgRightToLeft(): ?bool
    {
        return $this->LgRightToLeft;
    }

    public function setLgRightToLeft(bool $LgRightToLeft): self
    {
        $this->LgRightToLeft = $LgRightToLeft;

        return $this;
    }

    public function getLgShowRomanization(): bool
    {
        return $this->LgShowRomanization;
    }

    public function setLgShowRomanization(bool $b): self
    {
        $this->LgShowRomanization = $b;
        return $this;
    }

    /**
     * @return Collection|Book[]
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function getActiveBooks(): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('Archived', 0));

        // Psalm says that this method isn't defined, but a) the code works,
        // and b) symfony docs say this works.
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        return $this->books->matching($criteria);
    }

    /**
     * @return Collection|Term[]
     */
    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function setLgParserType(string $s): self
    {
        $this->LgParserType = $s;
        return $this;
    }

    public function getLgParserType(): string
    {
        return $this->LgParserType;
    }

    public function getParser()
    {
        switch ($this->LgParserType) {
        case 'spacedel':
            return new SpaceDelimitedParser();
        case 'classicalchinese':
            return new ClassicalChineseParser();
        case 'japanese':
            return new JapaneseParser();
        case 'turkish':
            return new TurkishParser();
        default:
            throw new \Exception("Unknown parser type {$this->LgParserType} for {$this->getLgName()}");
        }
    }
    
    public function parse($texts): void
    {
        $p = $this->getParser();
        $persister = new ParsedTokenSaver($p);
        $persister->parse($texts);
    }

    public function getParsedTokens(string $s): array
    {
        return $this->getParser()->getParsedTokens($s, $this);
    }

    /** Convenience method only. */
    public function getLowercase(string $s): string
    {
        return $this->getParser()->getLowercase($s);
    }

    /**
     * Language "factories" to create sensible defaults.
     * Returns unsaved entities.
     */

    /**
     * Demo files are stored in root/demo/*.yaml.
     * Hardcoding the path :-)
     */
    public static function fromYaml($filename): Language {
        $d = Yaml::parseFile($filename);

        $lang = new Language();

        $load = function($key, $method) use ($d, $lang) {
            if (! array_key_exists($key, $d))
                return;

            $val = $d[$key];

            // Have to put "\*" for external dicts in yaml,
            // but those should be saved as "*" only.
            // if (str_starts_with($val, '\*'))

            // Bools are special
            if (strtolower($val) == 'true')
                $val = true;
            if (strtolower($val) == 'false')
                $val = false;

            $lang->{$method}($d[$key]);
        };

        # Input fields on the user form, e.g.
        # http://localhost:9999/language/1/edit
        $mappings = [
            'name' => 'setLgName',
            'dict_1' => 'setLgDict1URI',
            'dict_2' => 'setLgDict2URI',
            'sentence_translation' => 'setLgGoogleTranslateURI',
            'show_romanization' => 'setLgShowRomanization',
            'right_to_left' => 'setLgRightToLeft',

            # Parsing
            'parser_type' => 'setLgParserType',
            'character_substitutions' => 'setLgCharacterSubstitutions',
            'split_sentences' => 'setLgRegexpSplitSentences',
            'split_sentence_exceptions' => 'setLgExceptionsSplitSentences',
            'word_chars' => 'setLgRegexpWordCharacters',

            # Ignore these!
            'stories' => '',
            'sample_terms' => '',
        ];

        foreach (array_keys($d) as $key) {
            $funcname = $mappings[$key];
            if ($funcname != '') {
                $load($key, $funcname);
            }
        }

        return $lang;
    }
    
    public static function makeSpanish() {
        $spanish = new Language();
        $spanish
            ->setLgName('Spanish')
            ->setLgDict1URI('https://es.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#es/en/###');
        return $spanish;
    }

    public static function makeGreek() {
        $greek = new Language();
        $greek
            ->setLgName('Greek')
            ->setLgRegexpWordCharacters('a-zA-ZÀ-ÖØ-öø-ȳͰ-Ͽἀ-ῼ')
            ->setLgShowRomanization(true)
            ->setLgDict1URI('https://www.wordreference.com/gren/###')
            ->setLgDict2URI('https://en.wiktionary.org/wiki/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#el/en/###');
        return $greek;
    }

    public static function makeFrench() {
        $french = new Language();
        $french
            ->setLgName('French')
            ->setLgDict1URI('https://fr.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#fr/en/###');
        return $french;
    }

    public static function makeGerman() {
        $german = new Language();
        $german
            ->setLgName('German')
            ->setLgDict1URI('https://de.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.wordreference.com/deen/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#de/en/###');
        return $german;
    }

    public static function makeEnglish() {
        $english = new Language();
        $english
            ->setLgName('English')
            ->setLgDict1URI('https://en.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#en/en/###');
        return $english;
    }

    public static function makeJapanese() {
        if (!JapaneseParser::MeCab_installed())
            throw new \Exception("MeCab not installed.");
        $japanese = new Language();
        $japanese
            ->setLgName('Japanese')
            ->setLgDict1URI('https://jisho.org/search/###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#jp/en/###')
            // Ref https://stackoverflow.com/questions/5797505/php-regex-expression-involving-japanese
            ->setLgRegexpWordCharacters('\p{Han}\p{Katakana}\p{Hiragana}')
            ->setLgRemoveSpaces(true)
            ->setLgShowRomanization(true)
            ->setLgRegexpSplitSentences('.!?。？！')
            ->setLgParserType('japanese');
        return $japanese;
    }

    public static function makeClassicalChinese() {
        $lang = new Language();
        $lang
            ->setLgName('Classical Chinese')
            ->setLgDict1URI('https://ctext.org/dictionary.pl?if=en&char=###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#ch/en/###')
            ->setLgRegexpWordCharacters('一-龥')
            ->setLgRegexpSplitSentences('.!?。！？')
            ->setLgRemoveSpaces(true)
            ->setLgShowRomanization(true)
            ->setLgParserType('classicalchinese');
        return $lang;
    }

    public static function makeArabic() {
        $arabic = new Language();
        $arabic
            ->setLgName('Arabic')
            ->setLgDict1URI('https://www.arabicstudentsdictionary.com/search?q=###')
            ->setLgDict2URI('*https://translate.google.com/?hl=es&sl=ar&tl=en&text=###&op=translate')
            ->setLgRegexpWordCharacters('\x{0600}-\x{06FF}\x{FE70}-\x{FEFC}')
            ->setLgRegexpSplitSentences('.!?؟۔‎')
            ->setLgRightToLeft(true)
            ->setLgGoogleTranslateURI('*https://translate.google.com/?hl=es&sl=ar&tl=en&text=###');
        return $arabic;
    }

    public static function makeTurkish() {
        $turkish = new Language();
        $turkish
            ->setLgName('Turkish')
            ->setLgRegexpWordCharacters('a-zA-ZÀ-ÖØ-öø-ȳáéíóúÁÉÍÓÚñÑğĞıİöÖüÜşŞçÇ')
            ->setLgShowRomanization(true)
            ->setLgDict1URI('https://www.wordreference.com/tren/###')
            ->setLgDict2URI('https://tr.wiktionary.org/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#tr/en/###')
            ->setLgParserType('turkish');
        return $turkish;
    }

    public static function getPredefined(): array {
        $ret = [
            Language::makeEnglish(),
            Language::makeFrench(),
            Language::makeGerman(),
            Language::makeGreek(),
            Language::makeSpanish(),
            Language::makeTurkish(),
            Language::makeClassicalChinese(),
            Language::makeArabic(),
        ];

        if (JapaneseParser::MeCab_installed())
            $ret[] = Language::makeJapanese();
        return $ret;
    }

}
