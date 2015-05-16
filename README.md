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

## Generate Unit-tests from Services
From time to time it happens that a dev looses the strict tests-first pattern and writes a service
without a proper test, and later on he wants to add a phpunit-test for this service. 
Your service probably has some dependencies in the constructor, and now you have to setup mocks for that.
To generate a base template for a service test, run the command

    app/console tps:util:generate-service-test
