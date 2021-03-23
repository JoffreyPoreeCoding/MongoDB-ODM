# MongoDB-ODM

JPC\MongoDB-ODM is a smart MongoDB-PHP mappers. It lets you to get your
Object in PHP mapped into document in MongoDB. This library support last driver of mongodb available [here](https://pecl.php.net/package/mongodb).

See 'doc' folder for more details.

# Install

## Install With Composer

In your console terminal :

```bash
php composer.phar require jpc/mongodb-odm:^1.3
```

Or in your ```composer.json``` :

```json
{
    "require" : {
        "jpc/mongodb-odm" : "^1.3"
    }
}
```

# Create Mapping Class

## Your class

To work with MongoDB-ODM, you need to see your class like a mongoDb document and define what you need :

 - an id
 - somes fields

You can create a simple object like this

```php
<?php

namespace ACME\Model;

class MyDoc {

	private $id;

	private $field1

	private $field2;

	//GETTERS AND SETTERS
}
```

Ok, you have your class. Now you have to tell to MongoDB-ODM which property match which field, and in which collection document are (and will be) stored.

## Mapping

For this, MongoDB-ODM use annotations. There is basics annotations to make a mapping that are in `JPC\MongoDB\ODM\Annotations\Mapping`:
 - `Document` : define the collection infos
 - `Id` : define the property that will match mongoDb `_id` field
 - `Field` : define a field mapping

 Now, edit your object like this :

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("my_collection")
 */
class MyDoc {

	/**
	 * @ODM\Id
	 */
	private $id;

	/**
	 * @ODM\Field('my_first_field')
	 */
	private $field1

	/**
	 * @ODM\Field('my_second_field')
	 */
	private $field2;

	//GETTERS AND SETTERS
}
```

## Advanced Mapping

Ok, you have a simple document but it still to miss something... Maybe things like embedded documents. Don't worry, MongoDB-ODM support them!

Create your embedded class like the previous one :


```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

class MyEmbedded {

	/**
	 * @ODM\Field('one_field_emb')
	 */
	private $oneFieldEmb

	/**
	 * @ODM\Field('two_field_emb')
	 */
	private $twoFieldEmb;

	//GETTERS AND SETTERS
}
```

You can now use two annotations to add embedded in the previous class :
 - `EmbeddedDocument` : Embed one document of specified type
 - `MultiEmbeddedDocument` : Embed an array of document of specified type

Modify the class `MyDoc` like this :

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("my_collection")
 */
class MyDoc {

	/**
	 * @ODM\Id
	 */
	private $id;

	/**
	 * @ODM\Field('my_first_field')
	 */
	private $field1

	/**
	 * @ODM\Field('my_second_field')
	 */
	private $field2;

	/**
	 * @ODM\Field('my_embedded_document')
	 * @ODM\EmbeddedDocument('ACME\Model\MyEmbedded')
	 */
	private $myEmbedded;

	//GETTERS AND SETTERS
}
```
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
public function findBy($filter, $projections = [], $sorts = [], $options = []);
public function findOneBy($filter = [], $projections = [], $sorts = [], $options = []);
public function findAndModifyOneBy($filter = [], $update = [], $projections = [], $sorts = [], $options = []);
```

Example :

```php
<?php

[...]

$repository = $documentManager->getRepository("ACME\Model\MyDoc");

// NOTE: filter, projections, etc... can be property name or field name
$myDocument = $repository->findOneBy(["field1" => "my_field_value", "myEmbedded.oneFieldEmb" => "another_value"]);
```

# Insert, update or remove a document

## Insert a document

First, you have to create your object and insert data inside it :

```php
$obj = new MyDoc();
$obj->setField1("MyValue");
$obj->setField2("MySecondValue");
```

Good! Now we want to store it in database. For this, you have to tell to MongoDB-ODM that your object have to be managed by him. By `manage` i mean that MongoDB-ODM will see if it has to insert, update or remove your object.

So, to do this, you need to `persist` your object.

Here is the `persist` function of document manager :

```php
public function persist($object, $collection = null);
```

For our object :

```php
$documentManager->persist($obj);
```

Next step is tell to MongoDB-ODM that it has to save all changes of all managed object. For this, there is a function called `flush`, just call it.

```php
$documentManager->flush();
```

Check your mongoDb collection, if it's empty, you probably make a mistake :confused:

## Update a document

Imagine that you have a lot of document in your collection and you want to update some of them.

First, you have to get the document that you want, like this :

```php
$repo = $documentManager->getRepository("ACME\Model\MyDoc");
$docs = $repo->findBy(["field_1" => "value"])
```

> Note : If you have inserted a document with `persist` and `flush`, it is ready for update, you don't need to get it from repository again

Now make some modification on them :

```php
foreach($docs as $index => $doc){
	$doc->setField2($index);
}
```

This is really nice modification :wink:

Now you just have to `flush`.

> Note : Finded object from repositories are already persisted, you don't have to persist again

```php
$documentManager->flush();
```

## Remove a document

Removing is easy like updating

Get your document from repository and just say to document manager that it has to remove them and flush:

```php
foreach($docs as $doc){
	$documentManager->remove($doc);
}

$documentManager->flush();
```

## Additional information

### Advantage of using update with MongoDB-ODM

Advantage of using MongoDB-ODM for update is that it will just update modified field and not the whole object.

### Unpersist

If you make some modification on persisted object in your code and you don't want to store changes at next flush, you can unpersist it with the `unpersist` function :

```php
$documentManager->unpersist($obj);
```

If you want to unpersist all objects, use `clear`

```php
$documentManager->clear();
```

# GridFS Document

## Create mapping for GridFS document

Your class need to inherit class `JPC\MongoDB\ODM\GridFS\Document`. This class define all default field of a gridFS document like `md5`, `filename`, etc... See this class for more details.

To add metadata to your doc, you need to use the `Metadata` annotations.

Here is an example :

```php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\GridFS\Annotations\Mapping as GFS;
use JPC\MongoDB\ODM\GridFS\Document;

/**
 * @GFS\Document("my_gridfs_bucket")
 */
class MyGridFSDoc extends Document {

    /**
     * @ODM\Field('my_meta_1')
     * @GFS\Metadata
     */
    private $meta1;

}
```

> Note that class `Document` annotation is provided from `GFS` and not by `ODM` namespace

## Insert your document

Insert a document in gridFS is like insert a document in basic mongoDB collection.

Here's a full example :

```php
$doc = new MyGridFSDoc();
$doc->setId("my_super_id")
$doc->setFilename("my_file.txt");
$doc->setContentType("text/plain");
$doc->setMeta1("my_value");
$doc->setStream(fopen("my_file.txt", 'r'));

$documentManager->persist($doc);
$documentManager->flush();
```

# Collections Options

## Collections options

When you create a model, you can specify more information on the `Document` annotion :

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document(
 *      "my_collection",
 *      "repositoryClass"="MyCustomRepositoryClass",
 *      "hydratorClass"="MyCustomHydratorClass",
 *      "capped"=true,
 *      "size"=536900000,
 *      "max"=1000
 * )
 */
class MyDoc {
    //...
}
```

And you can set ReadConcern, ReadPreference, TypeMap and WriteConcern with the `Option` annotation (Don't forget the use) :

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\CollectionOption as CO;

/**
 * @ODM\Document(//...)
 * @ODM\Option(
 *      readConcern=@CO\ReadConcern("local"),
 *      readPreference=@CO\ReadPreference(
 *          \MongoDB\Driver\ReadPreference::RP_PRIMARY_PREFERRED,
 *          tagset={{"dc"="ny"}}
 *      ),
 *      typeMap={
 *          "root"="array",
 *          "array"="array",
 *          "document"="array"
 *      },
 *      writeConcern=@CO\WriteConcern(
 *          w="majority",
 *          timeout=500,
 *          journal=false
 *      )
 * )
 */
class MyDoc {
    //...
}
```

# Events

## Availabe events

|Name|Description|
|----|-----------|
|PostLoad|Called after the document was loaded from database|
|PrePersist|Called before `persist` executed|
|PostPersist|Called after `persist` executed|
|PreFlush|Called before `flush` executed (Even if document has not changed)|
|PostFlush|Called after `flush` executed (Even if document has not changed)|
|PreInsert|Called before document is inserted in database|
|PostInsert|Called after document is inserted in database|
|PreUpdate|Called before document is updated in database|
|PostUpdate|Called after document is updated in database|
|PreDelete|Called after document is updated in database|
|PostDelete|Called after document is updated in database|

## How to use

To add event on your model class, you need to tell ODM that you want to use some event with `HasLifecycleCallbacks` annotation

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\Event;

/**
 * @ODM\Document("my_collection")
 * @Event\HasLifecycleCallbacks
 */
class MyDoc {
    //...
}
```

You can now add some methods with events annotations

```php
<?php

namespace ACME\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\Event;

/**
 * @ODM\Document("my_collection")
 * @Event\HasLifecycleCallbacks
 */
class MyDoc {
    //...

    /**
     * @Event\PreFlush
     */
    public function updateTimestamp(){
        $this->date = time();
    }
}
```

