# Get document

## Get a document manager

You can easily create a document manager with the factory available in the library.

The factory has a method `createDocumentManager` that take mongo uri in first argument, and database name in second.

Now, create a document manager : 

```php
<?php

use JPC\MongoDB\ODM\Factory\DocumentManagerFactory;

$factory = new DocumentManagerFactory();
$factory->createDocumentManager("mongodb://user:password@myserver.com:27017/authdb", "my_db");

```

## Get the repository

Now, you need to get the repository. Repository will allow you to get document from MongoDB.

To get Repository use the function `getRepository` method of document manager. 

This is the method :
```php 
public function getRepository($modelName, $collection = null)
```

If `$collection` is null, MongoDB-ODM take the collection setted in the `Document` annotation of the model

In our case (With model configured in `2-create-mapping-class`) :

```php 
<?php

[...]

$repository = $documentManager->getRepository("ACME\Model\MyDoc");

```

You can now simply find a document by using one of this functions of repository (See method declaration for more infos about methods):

```php
public function find($id, $projections = [], $options = []);
public function findAll($projections = [], $sorts = [], $options = []);
public function findBy($filters, $projections = [], $sorts = [], $options = []);
public function findOneBy($filters = [], $projections = [], $sorts = [], $options = []);
public function findAndModifyOneBy($filters = [], $update = [], $projections = [], $sorts = [], $options = []);
```

Example :

```php 
<?php

[...]

$repository = $documentManager->getRepository("ACME\Model\MyDoc");

// NOTE: filters, projections, etc... can be property name or field name
$myDocument = $repository->findOneBy(["field1" => "my_field_value", "myEmbedded.oneFieldEmb" => "another_value"]);
```


