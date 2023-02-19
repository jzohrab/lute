<?php

namespace App\Form;

use App\DTO\TermDTO;
use App\Entity\Language;
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


class TermDTOType extends AbstractType
{

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
            ->add('ParentID',
                  HiddenType::class,
                  [ 'label' => 'ParentID',
                    'required' => false
                  ]
            )
            ->add('ParentText',
                  TextType::class,
                  [ 'label' => 'Parent',
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
                  $options['hide_sentences'] ? HiddenType::class : TextType::class,
                  [ 'label' => 'Sentence',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
            ->add('termTags',
                   CollectionType::class,
                  [ 'label' => 'Term tags',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'required' => false
                  ])
            ->add('CurrentImage',
                  HiddenType::class,
                  [ 'label' => 'Image',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => false
                  ]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $termdto = $event->getData();
            $form = $event->getForm();

            $romanization_field_type = HiddenType::class;
            if ($termdto->language == null || $termdto->language->getLgShowRomanization()) {
                $romanization_field_type = TextType::class;
            }

            $form->add(
                'Romanization', $romanization_field_type,
                [ 'label' => 'Roman.',
                  'attr' => [ 'class' => 'form-text' ],
                  'required' => false ]
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TermDTO::class,
            'hide_sentences' => false,
        ]);
    }
}
