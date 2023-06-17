<?php

namespace App\Form;

use App\Entity\TermTag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TermTagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Text',
                  TextType::class,
                  [ 'label' => 'Tag',
                    'attr' => [ 'class' => 'form-text' ],
                    'required' => true
                  ]
            )
            ->add('Comment',
                  TextareaType::class,
                  [ 'label' => 'Comment',
                    'attr' => [
                        'class' => 'form-largetextarea'
                    ],
                    'required' => false
                  ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TermTag::class,
        ]);
    }
}
