<?php

// Ref https://south634.com/using-a-data-transformer-in-symfony-to-handle-duplicate-tags/

namespace App\Form\DataTransformer;

use App\Entity\Term;
use App\Entity\Language;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManagerInterface;
 
class TermParentTransformer implements DataTransformerInterface
{
    private EntityManagerInterface $manager;
    private Term $term;
    
    public function __construct(EntityManagerInterface $manager, Term $term)
    {
        $this->manager = $manager;
        $this->term = $term;
    }
 
    public function transform($value)
    {
        if ($this->term->getParent() == null)
            return null;
        return $this->term->getParent()->getText();
    }

    
    public function reverseTransform($value) {
        return $value;
    }
 
}
