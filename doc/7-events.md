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