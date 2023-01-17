<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Domain\RomanceLanguageParser;
use App\Domain\JapaneseParser;

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
    private ?string $LgRegexpSplitSentences = '.!?:;';

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
    private string $LgParserType = 'romance';

    #[ORM\OneToMany(targetEntity: 'Text', mappedBy: 'language', fetch: 'EXTRA_LAZY')]
    private Collection $texts;

    #[ORM\OneToMany(targetEntity: 'Term', mappedBy: 'language', fetch: 'EXTRA_LAZY')]
    private Collection $terms;

    public function __construct()
    {
        $this->texts = new ArrayCollection();
        $this->terms = new ArrayCollection();
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

    public function setLgDict2URI(string $LgDict2URI): self
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
     * @return Collection|Text[]
     */
    public function getTexts(): Collection
    {
        return $this->texts;
    }

    public function getActiveTexts(): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('TxArchived', 0));

        // Psalm says that this method isn't defined, but a) the code works,
        // and b) symfony docs say this works.
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        return $this->texts->matching($criteria);
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

    private function getParser()
    {
        switch ($this->LgParserType) {
        case 'romance':
            return new RomanceLanguageParser();
        case 'japanese':
            return new JapaneseParser();
        default:
            throw new \Exception("Unknown parser type {$this->LgParserType} for {$this->getLgName()}");
        }
    }
    
    public function parse(Text $text): void
    {
        $this->getParser()->parse($text);
    }


    /**
     * Language "factories" to create sensible defaults.
     * Returns unsaved entities.
     */

    public static function makeSpanish() {
        $spanish = new Language();
        $spanish
            ->setLgName('Spanish')
            ->setLgDict1URI('https://es.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#es/en/###');
        return $spanish;
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
            ->setLgParserType('japanese');
        return $japanese;
    }

    public static function getPredefined(): array {
        $ret = [
            Language::makeEnglish(),
            Language::makeFrench(),
            Language::makeGerman(),
            Language::makeSpanish(),
        ];

        if (JapaneseParser::MeCab_installed())
            $ret[] = Language::makeJapanese();
        return $ret;
    }

}
