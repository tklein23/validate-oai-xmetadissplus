validate-oai-xmetadissplus
==========================

Validating metadataPrefix=xMetaDissPlus from OAI-PMH

usage
=====

First time:
```
$ mkdir cache
$ ./fill-cache-from-catalog.sh xmetadissplus-catalog.xml
```

Validation:
```
$ XML_CATALOG_FILES=./xmetadissplus-catalog.xml php ./validate-oai-xml.php examples/list-records-155-156-xmetadissplus.xml
```
