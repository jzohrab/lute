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
                  [ 'label' => 'Dictionary 1 URI',
                    'help' => 'e.g., https://es.thefreedictionary.com/###.  "###" is replaced by the term.',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgDict2URI',
                  TextType::class,
                  [ 'label' => 'Dictionary 2 URI',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('LgGoogleTranslateURI',
                  TextType::class,
                  [ 'label' => 'Sentence translation URI',
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
                  [ 'label' => 'Regex split sentences',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('LgExceptionsSplitSentences',
                  TextType::class,
                  [ 'label' => 'Regex split sentence regex exceptions',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
        ]);
    }
}
