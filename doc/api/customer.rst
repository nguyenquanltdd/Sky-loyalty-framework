Customer API
============

These endpoints will allow you to easily manage customers.

.. note::

    Each role in the Open Loyalty has individual endpoints to manage customers.

Create a new customer
---------------------

To create a new customer you will need to call the ``/api/customer/register`` endpoint with the ``POST`` method.

.. note::

    This endpoint allows to set more customer parameters than ``/api/customer/self_register`` and is used when creating
    a new customer in the admin cockpit or pos cockpit. Self register endpoint is used in the client cockpit for registration
    and has some limitations.

Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/customer/register

+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| Parameter                          | Parameter type |  Description                                                                                  |
+====================================+================+===============================================================================================+
| Authorization                      | header         |  Token received during authentication                                                         |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[firstName]                | request        |  First name                                                                                   |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[lastName]                 | request        |  Last name                                                                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[gender]                   | request        |  *(optional)* Gender. Possible values ``male``, ``female``                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[email]                    | request        |  *(unique)* E-mail address                                                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[phone]                    | request        |  *(optional)* A phone number *(unique)*                                                       |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[birthDate]                | request        |  *(optional)* Birth date in format YYYY-MM-DD HH:mm, for example ``2017-10-05``               |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[createdAt]                | request        |  *(optional)* Created at in format YYYY-MM-DD HH:mm:ss, for example ``2017-01-01 14:15:16``.  |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][street]          | request        |  *(optional)* Street name                                                                     |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][address1]        | request        |  *(optional)* Building number                                                                 |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][address2]        | request        |  *(optional)* Flat/Unit name                                                                  |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][postal]          | request        |  *(optional)* Post code                                                                       |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][city]            | request        |  *(optional)* City name                                                                       |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][province]        | request        |  *(optional)* Province name                                                                   |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[address][country]         | request        |  *(optional)* Country name                                                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[company][name]            | request        |  *(optional)* Company name                                                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[company][nip]             | request        |  *(optional)* Tax ID                                                                          |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[loyaltyCardNumber]        | request        |  *(optional)* Loyalty card number *(unique)*                                                  |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[agreement1]               | request        |  First agreement. Set 1 if true, otherwise 0                                                  |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[agreement2]               | request        |  *(optional)* Second agreement. Set 1 if true, otherwise 0                                    |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[agreement3]               | request        |  *(optional)* Third agreement. Set 1 if true, otherwise 0                                     |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+
| customer[referral_customer_email]  | request        |  *(optional)* Referral customer e-mail address.                                               |
+------------------------------------+----------------+-----------------------------------------------------------------------------------------------+

Example
^^^^^^^

.. code-block:: bash

    curl http://localhost:8181/api/customer/register \
        -X "POST" \
        -H "Accept: application/json" \
        -H "Content-type: application/x-www-form-urlencoded" \
        -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..." \
        -d "customer[firstName]=John" \
        -d "customer[lastName]=Kowalski" \
        -d "customer[email]=john4@example.com" \
        -d "customer[phone]=000000005000" \
        -d "customer[agreement1]=1"

.. note::

    The *eyJhbGciOiJSUzI1NiIsInR5cCI6...* authorization token is an exemplary value.
    Your value can be different. Read more about :doc:`Authorization in the </authorization>`.

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
      "customerId": "e0eb0355-8aaa-4fb1-8159-f58e81b7a25c",
      "email": "john4@example.com"
    }

Example
^^^^^^^

.. code-block:: bash

    curl http://localhost:8181/api/customer/register \
        -X "POST" \
        -H "Accept: application/json" \
        -H "Content-type: application/x-www-form-urlencoded" \
        -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..." \
        -d "customer[firstName]=John" \
        -d "customer[lastName]=Kowalski" \
        -d "customer[email]=john3@example.com" \
        -d "customer[phone]=000000004000" \
        -d "customer[birthDate]=1990-01-01" \
        -d "customer[address][street]=Street" \
        -d "customer[address][postal]=00-000" \
        -d "customer[address][city]=Wroclaw" \
        -d "customer[address][province]=Dolnoslaskie" \
        -d "customer[address][country]=Poland" \
        -d "customer[company][nip]=111-222-33-44" \
        -d "customer[company][name]=Company+name" \
        -d "customer[loyaltyCardNumber]=00000000000000002" \
        -d "customer[agreement1]=1" \
        -d "customer[agreement2]=1" \
        -d "customer[agreement3]=1"

.. note::

    The *eyJhbGciOiJSUzI1NiIsInR5cCI6...* authorization token is an exemplary value.
    Your value can be different. Read more about :doc:`Authorization in the </authorization>`.

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
      "customerId": "e0eb0355-8aaa-4fb1-8159-f58e81b7a25c",
      "email": "john3@example.com"
    }

Example
^^^^^^^

.. code-block:: bash

    curl http://localhost:8181/api/customer/register \
        -X "POST" \
        -H "Accept: application/json" \
        -H "Content-type: application/x-www-form-urlencoded" \
        -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."

.. note::

    The *eyJhbGciOiJSUzI1NiIsInR5cCI6...* authorization token is an exemplary value.
    Your value can be different. Read more about :doc:`Authorization in the </authorization>`.

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 400 Bad Request

.. code-block:: json

    {
      "form": {
        "children": {
          "firstName": {},
          "lastName": {},
          "gender": {},
          "email": {},
          "phone": {},
          "birthDate": {},
          "createdAt": {},
          "address": {
            "children": {
              "street": {},
              "address1": {},
              "address2": {},
              "postal": {},
              "city": {},
              "province": {},
              "country": {}
            }
          },
          "company": {
            "children": {
              "name": {},
              "nip": {}
            }
          },
          "loyaltyCardNumber": {},
          "agreement1": {},
          "agreement2": {},
          "agreement3": {},
          "referral_customer_email": {},
          "levelId": {},
          "posId": {},
          "sellerId": {}
        }
      },
      "errors": []
    }
