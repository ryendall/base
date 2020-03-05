# Commodity API

This application uses Slim PHP framework and is designed for PHP 7.2+ environments. The purpose is to allow CRUD operations via API calls on four SQL database tables: commodity, commodity_class, commodity_family, commodity_segment

These have one-to-many relationships as follows: A commodity is associated with one class, which is associated with one family, which is associated with one segment

## Install the Application

After cloning the repository, run "composer install" from the project root

In a non-Docker environment, you will need to prepopulate the database using scripts/dbInit.sql

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

## API documentation

API documentation is at https://documenter.getpostman.com/view/1540194/SzKVSJvg?version=latest#d58475db-54ec-4e85-bc92-a3bb7267a8aa

## Notes

This repository is based on https://github.com/slimphp/Slim-Skeleton. All code under src/classes is my own.

The key script is `src/classes/im/model/Base.php` - you create a child class of this for each SQL table you wish to manage, and a single definition of the data model is maintained in this child class. See top of this script for further explanation.

In addition to classes for the "commodity" tables listed above, this folder contains several examples from other projects.