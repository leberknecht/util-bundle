<?php

namespace tps\UtilBundle\Tests\Stubs;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;

class ExampleClass {
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TwigEngine
     */
    private $templating;

    public function __construct(
        EntityManager $entityManager,
        TwigEngine $templating)
    {
        $this->entityManager = $entityManager;
        $this->templating = $templating;
    }
}