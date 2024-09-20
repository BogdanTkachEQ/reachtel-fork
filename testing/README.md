# Morpheus PHPUnit Testing
Tests for Morpheus are written with PHPUnit and located in the ``` ./testing ``` directory.


<br/>
## Requirements

### <u>PHP extensions</u>

PHP PECL extensions required:

 - soap
 - mysqli
 - runkit
 - mcrypt
 - openssl
 - zip
 - imap


<br/>
### <u>Runkit</u>

The [Runkit extension](http://php.net/manual/en/book.runkit.php) provides means to modify constants, user-defined functions,
and user-defined classes.

**Installation**

To install Runkit, please refer to 
http://php.net/manual/en/runkit.installation.php

NOTE: Enable Runkit PHP configuration `runkit.internal_override`

<br/>
### <u>PHP CodeSniffer (PHPCS)</u>

[PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) version **2.9** is required.
tokenizes PHP, JavaScript and CSS files and detects violations
of a defined set of coding standards.

**Installation**

@TODO



<br/>
### <u>Sound eXchange (SoX)</u>

**SoX** is a cross-platform audio editing software.

To install SoX, please refer to 
http://sox.sourceforge.net/



<br/><br/>
## Database

### Create test database and user
The Morpheus test database needs specific **database and user names**.

 - Create a new specific mysql **user** account called `morpheus_phpunit`
 - Set **user password** to `morpheus_phpunit`
 - Create a new specific mysql **database** called `morpheus_phpunit`
 - Give **ALL PRIVILEGES** to user `morpheus_phpunit` to access database `morpheus_phpunit`
 - Import the **[morpheus_phpunit.sql](./sql/morpheus_phpunit.sql)** file in  database `morpheus_phpunit`

<br/>
### Setup test MySQL connection

See **[./header.php.example](./../header.php.example)** file to use the test database.


<br/><br/>
## Resoures directory

Resoures are stored in a sperate directory. Paths are saved in the database (`key_store` table).

Default base path is `/mnt/morpheus`. Create sub folders as follow:

```
# You might need to sudo that
PATH_LOCATION=/mnt/morpheus
mkdir -p ${PATH_LOCATION}/assets
mkdir ${PATH_LOCATION}/audio
mkdir ${PATH_LOCATION}/dialplans
mkdir ${PATH_LOCATION}/emailbody
mkdir ${PATH_LOCATION}/emailtemplates
mkdir ${PATH_LOCATION}/iax
mkdir ${PATH_LOCATION}/invoices;
mkdir ${PATH_LOCATION}/lists;
mkdir ${PATH_LOCATION}/emailattachments;
mkdir ${PATH_LOCATION}/sip;
mkdir ${PATH_LOCATION}/smsscripts;
# You might need to set your own permissions below
chmod -R 777 ${PATH_LOCATION};
unset PATH_LOCATION

```






<br/><br/>
## YAML Configutation file

Some important test config in file [config.yml](./phpunit/config.yml):

```
# Database config
database:
  auto_reload: false # true/false. If true, reload database for each test run
  hash: 6e264586621fbace2c4bfc324173224a # tables hash

### PHPCS ###
phpcs:
  phpcs_bin_path: '/path/to/phpcs'
  standards_path: '/path/to/ReachTEL/phpcscustom/standards'
```




<br/><br/>
## Check your configuration

To check you have all the requirements to run PHPUnit tests, try to run the app config test:<br/>
```
./vendor/bin/phpunit -ctesting/phpunit testing/phpunit/unit/AppConfigTest.php
```
then try running the [health tests](#health_tests)



<br/><br/>
## Where is PHPUnit ?

PHPUnit 4 is located in the testing bin directory: `testing/lib/bin/phpunit`



<br/><br/>
## Run test tests

Tests are splitted by test suites:

<a name="health_tests"></a>
```
#!/bin/bash

# Health tests
./vendor/bin/phpunit -ctesting/phpunit --testsuite=health

# Coding coding standards
./vendor/bin/phpunit -ctesting/phpunit --testsuite=phpcs

# Unit tests
./vendor/bin/phpunit -ctesting/phpunit --testsuite=unit

# Module tests
./vendor/bin/phpunit -ctesting/phpunit --testsuite=module

# Helpers tests
./vendor/bin/phpunit -ctesting/phpunit --testsuite=helper

# Code coverage tests
# !!! DO NOT EXECUTE IN BAMBOO - LOCAL TEST ONLY !!!
./vendor/bin/phpunit -ctesting/phpunit --testsuite=coverage
```


## Run test tests in one go

You can use the `mortest` alias defined in the **reachtel_docker** repo:
https://bitbucket.corp.dmz/projects/REA/repos/reachtel_docker/browse/aliases.sh#1
