<?php

// Ref https://south634.com/using-a-data-transformer-in-symfony-to-handle-duplicate-tags/

namespace App\Form\DataTransformer;
 
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
 
class TermTagsCollection implements DataTransformerInterface
{
    private $manager;
 
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }
 
    public function transform($value)
    {
        return $value;
    }
 
    public function reverseTransform($value)
    {
        $coll = new ArrayCollection();
 
        $repo = $this->manager->getRepository(\App\Entity\TermTag::class);
 
        foreach ($value as $tag) {
 
            $tagInRepo = $repo->findByText($tag->getText());
 
            if ($tagInRepo !== null) {
                // Add tag from repository if found
                $coll->add($tagInRepo);
            }
            else {
                // Otherwise add new tag
                $coll->add($tag);
            }
        }
 
        return $coll;
    }
 
}
