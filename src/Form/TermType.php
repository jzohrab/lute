<?php

namespace App\Form;

use App\Entity\Term;
use App\Entity\Language;
use App\Form\DataTransformer\TermParentTransformer;
use App\Form\DataTransformer\TermTagsCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\ORM\EntityManagerInterface;

class TermType extends AbstractType
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
            ->add('Text',
                  TextType::class,
                  [ 'label' => 'Text',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('ParentText',
                  TextType::class,
                  [ 'label' => 'Parent',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('Romanization',
                  TextType::class,
                  [ 'label' => 'Roman.',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('Translation',
                  TextareaType::class,
                  [ 'label' => 'Translation',
                    'attr' => [ 'class' => 'textarea' ],
                    'required' => false
                  ]
            )
            ->add('Status',
                  ChoiceType::class,
                  [ 'choices'  => [
                      '1' => 1,
                      '2' => 2,
                      '3' => 3,
                      '4' => 4,
                      '5' => 5,
                      'Wkn' => 99,
                      'Ign' => 98
                  ],
                    'label' => 'Status',
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true
                  ]
            )
            ->add('Sentence',
                  TextType::class,
                  [ 'label' => 'Sentence',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('termTags',
                  CollectionType::class,
                  [
                    'entry_type' => TermTagType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'required' => false
                  ])
        ;

        // The term being used in the form is available as follows
        // ref https://symfonycasts.com/screencast/symfony-forms/form-options-data.
        // We need the term to help set some things in the parent.
        $term = $options['data'];

        // Data Transformers
        $builder
            ->get('ParentText')
            ->addModelTransformer(new TermParentTransformer($this->manager, $term));
        $builder
            ->get('termTags')
            ->addModelTransformer(new TermTagsCollection($this->manager));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Term::class,
        ]);
    }
}
