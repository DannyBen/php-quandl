PHP Quandl Unit Tests
=====================

This folder contains PHPUnit unit tests. You do not need it for 
production and can safely delete it if you are not using PHPUnit.

## Run Tests

    $ phpunit --stop-on-failure .

If you run the tests multiple times, you will eventually reach the Quandl call
limit for key-less calls. 

In this case, you can simply set your own key in an environment variable:

    $ export QUANDL_KEY=your_key_here
    $ phpunit --stop-on-failure .

To test a specific method:

  $ phpunit --stop-on-failure --filter PartialMethodName .


## Travis

If many tests are run through Travis CI, they will eventually fail due to 
Quandl key-less API quota. You can define the `QUANDL_KEY` environmnt 
variable in the travis repo settings.


