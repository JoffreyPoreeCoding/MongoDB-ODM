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

Now make some modification and them :

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