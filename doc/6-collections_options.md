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