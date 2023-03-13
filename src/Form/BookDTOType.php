<?php

namespace App\Form;

use App\DTO\BookDTO;
use App\Entity\Language;
use App\Form\DataTransformer\TextTagsCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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


class BookDTOType extends AbstractType
{

    private EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('language',
                  EntityType::class,
                  [ 'class' => Language::class,
                    'placeholder' => '(Select language)',
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
                    'attr' => [ 'class' => 'form-largetextarea' ],
                    'required' => true
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
        $resolver->setDefaults([
            'data_class' => BookDTO::class
        ]);
    }
}
