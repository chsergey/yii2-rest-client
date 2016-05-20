Create you own model by extending abstract class chsergey\rest\Model and configure it with API URL and resource name:

```php
<?php

namespace app\models;

use chsergey\rest\Model;

class Employee extends Model
{
	/**
	 * RESTful API URL
	 * @var string
	 */
	public static $apiUrl = 'http://server/rest/v1/';
	/**
	 * Name of resources collection
	 * @var string
	 */
	public static $resourceName = 'employees';
	/**
	 * Attribute of REST resource date to use as primary key
	 * @var string
	 */
	public static $primaryKey = 'login';
}
```

Use your model as usual AR-like model.

### Work with single resource

```php
$employee = Employee:findOne('employee1');
// $employee is instance of app\models\Employee
```

In this example class chsergey\rest\Query will be executed GET-request to REST resource:
```
Headers:
   Accept:application/json
   
GET http://server/rest/v1/employees/employee1
```

Retrieved response data will be applied as model's attributes.

To perform POST or PUT requests to RESTful API do:

```php
$employee->setAttribute('name', 'John Smith');
$employee->setScenario(Model::SCENARIO_UPDATE); // PUT-request to update
//$employee->setScenario(Model::SCENARIO_CREATE); // POST-request to create
$employee->save();
```

### Work with collection
To get collection:
```php
$employees = Employee::find()->where(['age' => 30])->all();
// $employee is array of instances of app\models\Employee
```

In this usage example class chsergey\rest\Query will be executed GET-request to collection resource with filteration parameter `age`:
```
GET http://server/rest/v1/employees?age=30
```

REST API must return collection of elements. For each element in collection will be created instance of chsergey\rest\Model with own attributes.