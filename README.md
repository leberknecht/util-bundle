## Instalation

    "tps/util-bundle": "dev-master"
    
## Generate Unit-tests from Services
From time to time it happens that a dev looses the strict tests-first pattern and writes a service
without a proper test, and later on he wants to add a phpunit-test for this service. 
Your service probably has some dependencies in the constructor, and now you have to setup mocks for that.
To generate a base template for a service test, run the command

    app/console tps:util:generate-service-test