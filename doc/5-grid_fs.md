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