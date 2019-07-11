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

