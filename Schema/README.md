To run all morpheus migrations:

    vendor/bin/doctrine-migrations migrations:migrate
    
To revert all morpheus migrations up to a specific version:

    vendor/bin/doctrine-migrations migrations:migrate {version number} --down
   
To generate new morpheus migration file:

    vendor/bin/doctrine-migrations migrations:generate

To generate migrations for plotter

    vendor/bin/doctrine-migrations migrations:migrate --configuration=plotter.migrations.yml --db-configuration=plotter-migrations-db.php
  
To run migration for plotter  

    vendor/bin/doctrine-migrations migrations:generate --configuration=plotter.migrations.yml --db-configuration=plotter-migrations-db.php


NOTE: When a new migration is created, make sure the class extends AbstractReachtelMigration instead of AbstractMigration
Morphues migrations get generated in the namespace Morpheus\Schema\Migrations and Plotter migrations get generated in Morpheus\Schema\Plotter\Migrations
