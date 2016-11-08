# Grid FS Documents

To map a GridFS Document you will need to use `JPC\MongoDB\ODM\Annotations\GridFS` annotations.

## Basic document (Without metadata)

This is a basic document (without metadatas) mapped in PHP object :

```php
<?php

namespace Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\GFS     as GFS;

/**
 * @GFS\Document("gridFSBucketName")
 */
class GridFSDocument {

	/**
     * @ODM\Id
     */
    private $id;
    
    /**
     * @GFS\FileInfos
     */
    private $fileInfos;
    
    /**
     * @GFS\Stream
     */
    private $stream;
    
    /**
     * GETTERS / SETTERS
     */
}
```

Some explanations :

* `@GFS\FileInfos` will contains file infos (uploadDate, filename, etc...) after insertion or on selection.
* `@GFS\Stream` must contain the stream to insert in MongoDB or, on find,  will contain the saved stream.

Here is an example for insertion :

```php
<?php

//...
use Model\GridFSDocument;

$myDoc = new GridFSDocument();
$myDoc->setStream(fopen("/some/file/path", "r"));

$documentManager->persist($myDoc);
$documentManager->flush();
//...
```


## Complex document (With metadata)

To add metadata, you just have to put them in the PHP mapping class :

```php
<?php

namespace Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\GFS     as GFS;

/**
 * @GFS\Document("gridFSBucketName")
 */
class GridFSDocument {

	/**
     * @ODM\Id
     */
    private $id;
    
    /**
     * @GFS\FileInfos
     */
    private $fileInfos;
    
    /**
     * @GFS\Stream
     */
    private $stream;
    
    /**
     * @ODM\Field("first_meta")
     */
    private $myFirstMeta;
    
    /**
     * @ODM\Field("second_meta")
     */
    private $mySecondMeta;
    
    /**
     * GETTERS / SETTERS
     */
}
```

This will be mapped into :

```javascript
{
	_id: "...",
    //...
    metadata: {
    	first_meta : "...",
        second_meta: "..."
    }
}
```
