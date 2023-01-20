<?php

namespace App\Form;

use App\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('LgName',
                  TextType::class,
                  [ 'label' => 'Language',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgDict1URI',
                  TextType::class,
                  [ 'label' => 'Dictionary 1',
                    'help' => 'e.g., https://es.thefreedictionary.com/###.  "###" is replaced by the term.',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgDict2URI',
                  TextType::class,
                  [ 'label' => 'Dictionary 2',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('LgGoogleTranslateURI',
                  TextType::class,
                  [ 'label' => 'Sentence translation',
                    'help' => 'e.g., *https://www.deepl.com/translator#es/en/###.',
                    'help_html' => true,
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgCharacterSubstitutions',
                  TextType::class,
                  [ 'label' => 'Character substitutions',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgRegexpSplitSentences',
                  TextType::class,
                  [ 'label' => 'Split sentences regex',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgExceptionsSplitSentences',
                  TextType::class,
                  [ 'label' => 'Split sentences regex exceptions',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgRegexpWordCharacters',
                  TextType::class,
                  [ 'label' => 'Regex word characters',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgShowRomanization',
                  ChoiceType::class,
                  [ 'choices'  => [ 'No' => 0, 'Yes' => 1 ],
                    'label' => 'Show Romanization field',
                    'required' => true
                  ]
            )
            ->add('LgParserType',
                  ChoiceType::class,
                  [ 'choices'  => [
                      'Romance Language' => 'romance',
                      'Japanese Language (MeCab)' => 'japanese'
                     ],
                    'label' => 'Parse as',
                    'required' => true
                  ]
            )

            /*
            // Currently disabling the below as Lute doesn't support these languages.
            ->add('LgSplitEachChar',
                  ChoiceType::class,
                  [ 'choices'  => [ 'No' => 0, 'Yes' => 1 ],
                    'label' => 'Each character is a word',
                    'help' => 'e.g., for Chinese, Japanese, etc.',
                    'required' => true
                  ]
            )
            ->add('LgRemoveSpaces',
                  ChoiceType::class,
                  [ 'choices'  => [ 'No' => 0, 'Yes' => 1 ],
                    'label' => 'Remove spaces',
                    'help' => 'e.g., for Chinese, Japanese, etc.',
                    'required' => true
                  ]
            )
            ->add('LgRightToLeft',
                  ChoiceType::class,
                  [ 'choices'  => [ 'No' => 0, 'Yes' => 1 ],
                    'label' => 'Right-To-Left Script',
                    'help' => 'e.g., for Arabic, Hebrew, Farsi, Urdu, etc.',
                    'required' => true
                  ]
            )
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
        ]);
    }
}
