Segment API
===========

These endpoints will allow you to see the list of segments taken in the Open Loyalty.

Get segments list
-----------------

To retrieve a paginated list of segments you will need to call the ``/api/segment`` endpoint with the ``GET`` method.


Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/segment


+----------------------+----------------+--------------------------------------------------------+
| Parameter            | Parameter type |  Description                                           |
+======================+================+========================================================+
| Authorization        | header         | Token received during authentication                   |
+----------------------+----------------+--------------------------------------------------------+
| page                 | query          | *(optional)* Start from page, by default 1             |
+----------------------+----------------+--------------------------------------------------------+
| perPage              | query          | *(optional)* Number of items to display per page,      |
|                      |                | by default = 10                                        |
+----------------------+----------------+--------------------------------------------------------+
| sort                 | query          | *(optional)* Sort by column name,                      |
|                      |                | by default = name                                      |
+----------------------+----------------+--------------------------------------------------------+
| direction            | query          | *(optional)* Direction of sorting [ASC, DESC],         |
|                      |                | by default = ASC                                       |
+----------------------+----------------+--------------------------------------------------------+

Example
^^^^^^^

.. code-block:: bash

	curl http://localhost:8181/api/segment \
	    -X "GET" \
	    -H "Accept: application/json" \
	    -H "Content-type: application/x-www-form-urlencoded" \
	    -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

	{
	  "segments": [
		{
		  "segmentId": "00000000-0000-0000-0000-000000000005",
		  "name": "transaction amount 10-50",
		  "description": "desc",
		  "active": false,
		  "parts": [
			{
			  "segmentPartId": "00000000-0000-0000-0000-000000000055",
			  "criteria": [
				{
				  "criterionId": "00000000-0000-0000-0000-000000000055",
				  "fromAmount": 10,
				  "toAmount": 50,
				  "type": "transaction_amount"
				}
			  ]
			}
		  ],
		  "createdAt": "2018-02-19T09:45:06+0100",
		  "customersCount": 0
		},
		{
		  "segmentId": "00000000-0000-0000-0000-000000000000",
		  "name": "test",
		  "description": "desc",
		  "active": false,
		  "parts": [
			{
			  "segmentPartId": "00000000-0000-0000-0000-000000000000",
			  "criteria": [
				{
				  "criterionId": "00000000-0000-0000-0000-000000000002",
				  "min": 10,
				  "max": 20,
				  "type": "transaction_count"
				},
				{
				  "criterionId": "00000000-0000-0000-0000-000000000001",
				  "fromAmount": 1,
				  "toAmount": 10000,
				  "type": "average_transaction_amount"
				},
				{
				  "criterionId": "00000000-0000-0000-0000-000000000000",
				  "posIds": [
					"00000000-0000-474c-1111-b0dd880c07e2"
				  ],
				  "type": "bought_in_pos"
				}
			  ]
			}
		  ],
		  "createdAt": "2018-02-19T09:45:06+0100",
		  "customersCount": 0
		}
	  ],
	  "total": 2
	}
	
Create new segment
------------------

To create a new segment you will need to call the ``/api/segment`` endpoint with the ``POST`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/segment

+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| Parameter                                      | Parameter type |  Description                                                               |
+================================================+================+============================================================================+
| Authorization                                  | header         | Token received during authentication                                       |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[name]                                  | request        |  Segment name                                                              |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[active]                                | request        |  *(optional)* Set 1 if active, otherwise 0                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[description]                           | request        |  *(optional)* A short description                                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][type]           | request        |  Criteria type for segment parts                                           |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][days]           | request        |  Days for Anniversary Type                                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][anniversaryType]| request        |  Type for Anniversary Type                                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromAmount]     | request        |  Minimum value for Type Average transaction value                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toAmount]       | request        |  Maximum value for Type Average transaction value                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][posIds][0]      | request        |  Choose POS for Type Bought in specific POS                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][makers][0]      | request        |  Brands for Type Bought specific brands                                    |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][skuIds][0]      | request        |  SKUs for Type Bought specific SKU                                         |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][days]           | request        |  Days for Type Last purchase was n days ago                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromDate]       | request        |  Date from for Type Purchase period                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toDate]         | request        |  Days to for Type Purchase period                                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][min]            | request        |  Minimum for Type Transaction count                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][max]            | request        |  Maximum for Type Transaction count                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][percent]        | request        |  Percent for Type Transaction percent in POS                               |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][posId]          | request        |  POS for Type Transaction percent in POS                                   |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromAmount]     | request        |  Minimum value for Type Transaction value                                  |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toAmount]       | request        |  Maximum value for Type Transaction value                                  |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
	
Example
^^^^^^^

.. code-block:: bash	


	curl http://localhost:8181/api/segment/00000000-0000-0000-0000-000000000002` \
	    -X "POST" \
	    -H "Accept: application/json" \
	    -H "Content-type: application/x-www-form-urlencoded" \
	    -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."
        -d "segment[name]=testsm" \
		-d "segment[active]=1" \
		-d "segment[description]=testsmdescription" \
		-d "segment[parts][0][criteria][0][type]=anniversary" \
		-d "segment[parts][0][criteria][0][days]=2" \
		-d "segment[parts][0][criteria][0][anniversaryType]=registration"
		
.. note::

    You could add or condition by clicking "ADD OR CONDITION"
	You could add and condition by clicking "ADD AND CONDITION"

		
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json
	
	{
	  "segmentId": "17347292-0aaf-4c25-9118-17eb2c55b58b"
	}	

	
Delete segment 	
--------------	

To delete segment you will need to call the ``/api/segment/<segment>`` endpoint with the ``DELETE`` method.
	
Definition
^^^^^^^^^^

.. code-block:: text

    DELETE /api/segment/<segment>
	

+----------------------+----------------+--------------------------------------------------------+
| Parameter            | Parameter type |  Description                                           |
+======================+================+========================================================+
| Authorization        | header         | Token received during authentication                   |
+----------------------+----------------+--------------------------------------------------------+
| <segment>            | query          | Segment ID                                             |
+----------------------+----------------+--------------------------------------------------------+


Example
^^^^^^^

.. code-block:: bash

    curl http://localhost:8181/api/segment/f9a64320-0e93-42b9-882c-43cd477156cf \
	    -X "DELETE" \
	    -H "Accept: application/json" \
	    -H "Content-type: application/x-www-form-urlencoded" \
	    -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."
		
.. note::

    The *f9a64320-0e93-42b9-882c-43cd477156cf* segment ID is an exemplary value.
    Your value can be different. Check in the list of all segments if you are not sure which id should be used.
			
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 204 OK

.. code-block:: json

	No Content


Get segment details
-------------------

To retrieve segment details you will need to call the ``/api/segment/<segment>`` endpoint with the ``GET`` method.
	
Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/segment/<segment>
	
	
+----------------------+----------------+--------------------------------------------------------+
| Parameter            | Parameter type |  Description                                           |
+======================+================+========================================================+
| Authorization        | header         | Token received during authentication                   |
+----------------------+----------------+--------------------------------------------------------+
| <segment>            | query          | Segment ID                                             |
+----------------------+----------------+--------------------------------------------------------+

Example
^^^^^^^

To see the details of the customer user with ``segment = 00000000-0000-0000-0000-000000000002`` use the below method:

.. code-block:: bash	


	curl http://localhost:8181/api/segment/00000000-0000-0000-0000-000000000002` \
	    -X "GET" \
	    -H "Accept: application/json" \
	    -H "Content-type: application/x-www-form-urlencoded" \
	    -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."

		
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

	{
	  "segmentId": "00000000-0000-0000-0000-000000000002",
	  "name": "anniversary",
	  "description": "desc",
	  "active": false,
	  "parts": [
		{
		  "segmentPartId": "00000000-0000-0000-0000-000000000001",
		  "criteria": [
			{
			  "criterionId": "00000000-0000-0000-0000-000000000011",
			  "anniversaryType": "birthday",
			  "days": 10,
			  "type": "anniversary"
			}
		  ]
		}
	  ],
	  "createdAt": "2018-02-19T09:45:06+0100",
	  "customersCount": 0
	}
	
	
	
Update segment data
-------------------

To fully update segment data for user you will need to call the ``/api/segment/<segment>`` endpoint with the ``PUT`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    PUT /api/segment/<segment>	
	
	
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| Parameter                                      | Parameter type |  Description                                                               |
+================================================+================+============================================================================+
| Authorization                                  | header         | Token received during authentication                                       |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| <segment>                                      | query          |  Segment ID                                                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[name]                                  | request        |  Segment name                                                              |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[active]                                | request        |  *(optional)* Set 1 if active, otherwise 0                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[description]                           | request        |  *(optional)* A short description                                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][type]           | request        |  Criteria type for segment parts                                           |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][days]           | request        |  Days for Anniversary Type                                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][anniversaryType]| request        |  Type for Anniversary Type                                                 |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromAmount]     | request        |  Minimum value for Type Average transaction value                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toAmount]       | request        |  Maximum value for Type Average transaction value                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][posIds][0]      | request        |  Choose POS for Type Bought in specific POS                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][makers][0]      | request        |  Brands for Type Bought specific brands                                    |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][skuIds][0]      | request        |  SKUs for Type Bought specific SKU                                         |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][days]           | request        |  Days for Type Last purchase was n days ago                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromDate]       | request        |  Date from for Type Purchase period                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toDate]         | request        |  Days to for Type Purchase period                                          |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][min]            | request        |  Minimum for Type Transaction count                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][max]            | request        |  Maximum for Type Transaction count                                        |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][percent]        | request        |  Percent for Type Transaction percent in POS                               |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| segment[parts][0][criteria][0][posId]          | request        |  POS for Type Transaction percent in POS                                   |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][fromAmount]     | request        |  Minimum value for Type Transaction value                                  |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+	
| segment[parts][0][criteria][0][toAmount]       | request        |  Maximum value for Type Transaction value                                  |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+

Example
^^^^^^^
To see the details of the admin user with ``level = 17347292-0aaf-4c25-9118-17eb2c55b58b`` use the below method:

.. code-block:: bash

	curl http://localhost:8181/api/segment/17347292-0aaf-4c25-9118-17eb2c55b58b \
	    -X "POST" \
		-H "Accept:\ application/json" \ 
		-H "Content-type:\ application/x-www-form-urlencoded" \
		-H "Authorization:\ Bearer\ eyJhbGciOiJSUzI1NiIsInR5cCI6..." \
		-d "segment[name]=tests" \
		-d "segment[active]=0" \
		-d "segment[description]=tests" \
		-d "segment[parts][0][criteria][0][type]=anniversary" \
		-d "segment[parts][0][criteria][0][days]=2" \
		-d "segment[parts][0][criteria][0][anniversaryType]=birthday"

.. note::

    You could add or condition by clicking "ADD OR CONDITION"
	You could add and condition by clicking "ADD AND CONDITION"	
		
		
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

	{
	  "segmentId": "17347292-0aaf-4c25-9118-17eb2c55b58b"
	}


Activate level	
--------------

To activate level you will need to call the ``/api/segment/<segment>/activate`` endpoint with the ``POST`` method.
	
Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/segment/<segment>/activate
	

+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| Parameter                                      | Parameter type |  Description                                                               |
+================================================+================+============================================================================+
| Authorization                                  | header         | Token received during authentication                                       |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| <segment>                                      | query          |  Segment ID                                                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+

Example
^^^^^^^

.. code-block:: bash

	curl http://localhost:8181/api/segment/63afec60-5e74-43fc-a5e1-81bbc03421ca/activate \
		-X "POST" \
		-H "Accept:\ application/json" \ 
		-H "Content-type:\ application/x-www-form-urlencoded" \
		-H "Authorization:\ Bearer\ eyJhbGciOiJSUzI1NiIsInR5cCI6..." \
	
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 204 OK

.. code-block:: json
	
	No Content
	
	
Get customers assigned to specific segment
------------------------------------------

To retrieve a paginated list of customers assigned to specific segment you will need to call the ``/api/segment/<segment>/customers`` endpoint with the ``GET`` method.


Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/segment/<segment>/customers

+----------------------+----------------+--------------------------------------------------------+
| Parameter            | Parameter type |  Description                                           |
+======================+================+========================================================+
| Authorization        | header         | Token received during authentication                   |
+----------------------+----------------+--------------------------------------------------------+
| firstName            | query          | *(optional)* First Name                                |
+----------------------+----------------+--------------------------------------------------------+
| lastName             | query          | *(optional)* Last Name                                 |
+----------------------+----------------+--------------------------------------------------------+
| phone                | query          | *(optional)* Phone                                     |
+----------------------+----------------+--------------------------------------------------------+
| email                | query          | *(optional)* E-mail                                    |
+----------------------+----------------+--------------------------------------------------------+
| page                 | query          | *(optional)* Start from page, by default 1             |
+----------------------+----------------+--------------------------------------------------------+
| perPage              | query          | *(optional)* Number of items to display per page,      |
|                      |                | by default = 10                                        |
+----------------------+----------------+--------------------------------------------------------+
| sort                 | query          | *(optional)* Sort by column name,                      |
|                      |                | by default = name                                      |
+----------------------+----------------+--------------------------------------------------------+
| direction            | query          | *(optional)* Direction of sorting [ASC, DESC],         |
|                      |                | by default = ASC                                       |
+----------------------+----------------+--------------------------------------------------------+
	
Example
^^^^^^^

.. code-block:: bash

	curl http://localhost:8181/api/segment/63afec60-5e74-43fc-a5e1-81bbc03421ca/customers \
	    -X "GET" \
	    -H "Accept: application/json" \
	    -H "Content-type: application/x-www-form-urlencoded" \
	    -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6..."

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json
	
	{
	  "customers": [
		{
		  "segmentId": "63afec60-5e74-43fc-a5e1-81bbc03421ca",
		  "customerId": "57524216-c059-405a-b951-3ab5c49bae14",
		  "segmentName": "test123",
		  "firstName": "Tomasz",
		  "lastName": "Test80",
		  "email": "tomasztest80@wp.pl",
		  "active": true,
		  "address": [],
		  "createdAt": "2018-02-20T08:22:11+0100",
		  "levelId": "000096cf-32a3-43bd-9034-4df343e5fd94",
		  "manuallyAssignedLevelId": {
			"levelId": "000096cf-32a3-43bd-9034-4df343e5fd94"
		  },
		  "agreement1": true,
		  "agreement2": false,
		  "agreement3": false,
		  "status": {
			"availableTypes": [
			  "new",
			  "active",
			  "blocked",
			  "deleted"
			],
			"availableStates": [
			  "no-card",
			  "card-sent",
			  "with-card"
			],
			"type": "active",
			"state": "no-card"
		  },
		  "updatedAt": "2018-02-20T08:22:12+0100",
		  "campaignPurchases": [],
		  "transactionsCount": 1,
		  "transactionsAmount": 44.97,
		  "transactionsAmountWithoutDeliveryCosts": 44.97,
		  "amountExcludedForLevel": 0,
		  "averageTransactionAmount": 44.97,
		  "lastTransactionDate": "2018-02-20T07:24:19+0100",
		  "currency": "eur",
		  "levelPercent": "20.00%"
		}
	  ],
	  "total": 1
	}

	
Deactivate level	
----------------

To deactivate level you will need to call the ``/api/segment/<segment>/deactivate`` endpoint with the ``POST`` method.
	
Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/segment/<segment>/deactivate
	

+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| Parameter                                      | Parameter type |  Description                                                               |
+================================================+================+============================================================================+
| Authorization                                  | header         | Token received during authentication                                       |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+
| <segment>                                      | query          |  Segment ID                                                                |
+------------------------------------------------+----------------+----------------------------------------------------------------------------+

Example
^^^^^^^

.. code-block:: bash

	curl http://localhost:8181/api/segment/63afec60-5e74-43fc-a5e1-81bbc03421ca/deactivate \
		-X "POST" \
		-H "Accept:\ application/json" \ 
		-H "Content-type:\ application/x-www-form-urlencoded" \
		-H "Authorization:\ Bearer\ eyJhbGciOiJSUzI1NiIsInR5cCI6..." \
	
Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 204 OK

.. code-block:: json
	
	No Content