Open Loyalty Documentation
==========================

`Open Loyalty`_ is technology for loyalty solutions for starting new loyalty projects
based on the `Symfony Framework`_.

.. note::

    This documentation assumes you have a working knowledge of the Symfony
    Framework. If you're not familiar with Symfony, please start with
    reading the `Quick Tour`_ from the Symfony documentation.

Requirements
------------
This project has full support for running in `Docker <https://www.docker.com/>`_.

Best way to run it is to execute the following command

  docker-compose up
(docker must be installed on your machine).

There is also an option to run this without docker, but in such case following a set of the tool will be needed:

* php7
* MySql or PosstgreSql
* Elasticsearch 2.2

Installation
------------
Simply clone this repository, run

  composer install

and fill up all required parameters.
Then use Phing to setup database, elastcsearch and load some demo data


  phing setup

(if you are using docker, remember to run those `command inside container <./run_command_inside_docker.rst>`_)

Architecture
------------
This project is based on CQRS, DDD and event sourcing, whole code is split into components and bundles. More info about each component and bundle can be found in `Architecture <./architecture/index.rst>`_.

Customization
-------------
There is some possibility to customize whole app for personal needs.
Complete guide can be found in `customization <./customization.rst>`_ section of this documentation.


Cron tasks
----------
There are two tasks that should be run periodically.

1. Segmenting customers

    bin/console oloy:segment:recreate

2. Expiring points

    ol:points:transfers:expire


The REST API Reference
----------------------

:doc:`The API guide </api/index>` covers the REST API of Open Loyalty platform.

.. toctree::
    :hidden:

       api/index

.. include:: /api/map.rst.inc

.. _OL: http://openloyalty.io
.. _`Open Loyalty`: http://openloyalty.io
.. _`Symfony Framework`: http://symfony.com
.. _`Quick Tour`: http://symfony.com/doc/current/quick_tour
