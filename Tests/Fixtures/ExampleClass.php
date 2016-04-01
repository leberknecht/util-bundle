<?php


namespace Tps\UtilBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;

class ExampleClass {
    /**
     * @var EntityManager
     */
    private $testForm;
    /**
     * @var TwigEngine
     */
    private $templating;
    /**
     * @var string
     */
    private $primitiveParameter;

    public function __construct(
        Form $testForm,
        TwigEngine $templating,
        $primitiveParameter
    )
    {
        $this->testForm = $testForm;
        $this->templating = $templating;
        $this->primitiveParameter = $primitiveParameter;
    }
}