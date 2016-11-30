[![Build Status](https://api.travis-ci.org/leberknecht/util-bundle.png)](https://travis-ci.org/leberknecht/util-bundle)
[![Coverage Status](https://coveralls.io/repos/leberknecht/util-bundle/badge.png)](https://coveralls.io/r/leberknecht/util-bundle)

## Installation

Require the bundle via composer:

```bash
composer require "tps/util-bundle":"dev-master"
```

Or add to composer.json:

    "require": {
        [...]
        "tps/util-bundle": "dev-master"
    },

Activate in AppKernel.php:

```php
$bundles = [
    [...]
    new Tps\UtilBundle\TpsUtilBundle()
]
```

## Generate unit-tests from service classes
From time to time it happens that a dev is confronted with a brownfield service that has no unit-test, and wants to fix that. If the service has a lot of dependencies in the constructor, preparing the mocks for that can be really annoying (yes, the clean solution is to split the service to smaller parts with nice and easy-to-mock constructor). Anyways, if you are in a situation where you want to add a proper test for a monster-service, this tool can be ver handy.

Lets say you have a simple service like this: 

```php
<?php
namespace Tps\UtilBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;

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

    public function __construct(Form $testForm, TwigEngine $templating, $primitiveParameter)
    {
        $this->testForm = $testForm;
        $this->templating = $templating;
        $this->primitiveParameter = $primitiveParameter;
    }
    [...]
```

To generate a base template for a service test, run the command

    app/console tps:util:generate-service-test 'Tps\UtilBundle\Tests\Fixtures\ExampleClass'

Generated output will be:

```php
<?php

namespace Tps\UtilBundle\Tests\Tests\Fixtures;

use PHPUnit_Framework_MockObject_MockObject;
use Tps\UtilBundle\Tests\Fixtures\ExampleClass;
use Symfony\Component\Form\Form;
use Symfony\Bundle\TwigBundle\TwigEngine;

class ExampleClassTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\Form
    */
    private $formMock;
   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Symfony\Bundle\TwigBundle\TwigEngine
    */
    private $twigEngineMock;
   /**
    * @var int|string|boolean|array
    */
    private $primitiveParameter = null;

   /**
    * @var PHPUnit_Framework_MockObject_MockObject|\Tps\UtilBundle\Tests\Fixtures\ExampleClass
    */
    private $exampleClass;

    public function setUp()
    {
        $this->formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()->getMock();
        $this->twigEngineMock = $this->getMockBuilder(TwigEngine::class)
            ->disableOriginalConstructor()->getMock();

        $this->exampleClass = new ExampleClass(
            $this->formMock,
            $this->twigEngineMock,
            $this->primitiveParameter
        );
    }
}
```
