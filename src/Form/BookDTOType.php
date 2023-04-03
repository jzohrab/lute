<?php

namespace App\Form;

use App\DTO\BookDTO;
use App\Entity\Language;
use App\Form\DataTransformer\TextTagsCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LanguageRepository;

class BookDTOType extends AbstractType
{

    private EntityManagerInterface $manager;
    private LanguageRepository $langrepo;

    public function __construct(EntityManagerInterface $manager, LanguageRepository $langrepo)
    {
        $this->manager = $manager;
        $this->langrepo = $langrepo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('language',
                  EntityType::class,
                  [ 'class' => Language::class,
                    'placeholder' => $options['lang_count'] == 1 ? false : '(Select language)',
                    'choice_label' => 'lgName'
                  ]
            )
            ->add('Title',
                  TextType::class,
                  [ 'label' => 'Title',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('Text',
                  TextareaType::class,
                  [ 'label' => 'Text',
                    'help' => 'Use for short texts, e.g. up to a few thousand words.  For longer texts, use the "Text File" below.',
                    'attr' => [
                        'class' => 'form-largetextarea'
                    ],
                    'required' => false
                  ]
            )
            ->add('TextFile', FileType::class,
                  [ 'label' => 'Text file',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '1024k',
                            'mimeTypes' => [
                                'text/plain'
                            ],
                            'mimeTypesMessage' => 'Please upload a valid text document',
                        ])
                    ]
                  ]
                )
            ->add('SourceURI',
                  TextType::class,
                  [ 'label' => 'Source URI',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('bookTags',
                   CollectionType::class,
                  [ 'label' => 'Tags',
                    'entry_type' => TextTagType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'required' => false
                  ])
        ;

        // Data Transformer
        $builder
            ->get('bookTags')
            ->addModelTransformer(new TextTagsCollection($this->manager));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $langcount = count($this->langrepo->findAll());
        $resolver->setDefaults([
            'data_class' => BookDTO::class,
            'lang_count' => $langcount,
        ]);
    }
}
