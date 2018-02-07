PHP Quandl Unit Tests
==================================================


This folder contains PHPUnit unit tests. You do not need it for 
production and can safely delete it if you are not using PHPUnit.

Run Tests
--------------------------------------------------

    $ phpunit --stop-on-failure .

If you run the tests multiple times, you will eventually reach the Quandl call
limit for key-less calls. 

In this case, you can simply set your own key in an environment variable:

    $ export QUANDL_KEY=your_key_here
    $ phpunit --stop-on-failure .

To test a specific method:

    $ phpunit --stop-on-failure --filter PartialMethodName .


Testing Bulk Downloads with a Premium Database
--------------------------------------------------

The bulk download methods require a premium database, therefore, the tests
for bulk downloads are skipped by default. 

To enable them, set the environment variable `QUANDL_PREMIUM` to a premium
database you are subscribed to, prior to running the tests. For example:

    $ export QUANDL_KEY=your_key_here
    $ export QUANDL_PREMIUM=EOD
    $ phpunit --stop-on-failure .


Travis
--------------------------------------------------

If many tests are executed through Travis CI, they will eventually fail due 
to Quandl key-less API quota. You can define the `QUANDL_KEY` environmnt 
variable in the travis repo settings.

