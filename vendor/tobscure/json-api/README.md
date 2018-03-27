# PHP JSON-API

[![Build Status](https://img.shields.io/travis/tobscure/json-api/master.svg?style=flat)](https://travis-ci.org/tobscure/json-api)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/tobscure/json-api.svg?style=flat)](https://scrutinizer-ci.com/g/tobscure/json-api/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/tobscure/json-api.svg?style=flat)](https://scrutinizer-ci.com/g/tobscure/json-api)
[![Pre Release](https://img.shields.io/packagist/vpre/tobscure/json-api.svg?style=flat)](https://github.com/tobscure/json-api/releases)
[![License](https://img.shields.io/packagist/l/tobscure/json-api.svg?style=flat)](https://packagist.org/packages/tobscure/json-api)

[JSON-API](http://jsonapi.org) responses in PHP.

Works with version 1.0 of the spec.

## Install

via Composer:

```bash
composer require tobscure/json-api
```

## Usage

```php
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Collection;

// Create a new collection of posts, and specify relationships to be included.
$collection = (new Collection($posts, new PostSerializer))
    ->with(['author', 'comments']);

// Create a new JSON-API document with that collection as the data.
$document = new Document($collection);

// Add metadata and links.
$document->addMeta('total', count($posts));
$document->addLink('self', 'http://example.com/api/posts');

// Output the document as JSON.
echo json_encode($document);
```

### Elements

The JSON-API spec describes *resource objects* as objects containing information about a single resource, and *collection objects* as objects containing information about many resources. In this package:

- `Tobscure\JsonApi\Resource` represents a *resource object*
- `Tobscure\JsonApi\Collection` represents a *collection object*

Both Resources and Collections are termed as *Elements*. In conceptually the same way that the JSON-API spec describes, a Resource may have **relationships** with any number of other Elements (Resource for has-one relationships, Collection for has-many). Similarly, a Collection may contain many Resources.

A JSON-API Document may contain one primary Element. The primary Element will be recursively parsed for relationships with other Elements; these Elements will be added to the Document as **included** resources.

#### Sparse Fieldsets

You can specify which fields (attributes and relationships) to be included on an Element using the `fields` method. You must provide a multidimensional array organized by resource type:

```php
$collection->fields(['posts' => ['title', 'date']]);
```

### Serializers

A Serializer is responsible for building attributes and relationships for a certain resource type. Serializers must implement `Tobscure\JsonApi\SerializerInterface`. An `AbstractSerializer` is provided with some basic functionality. At a minimum, a serializer must specify its **type** and provide a method to transform **attributes**:

```php
use Tobscure\JsonApi\AbstractSerializer;

class PostSerializer extends AbstractSerializer
{
    protected $type = 'posts';

    public function getAttributes($post, array $fields = null)
    {
        return [
            'title' => $post->title,
            'body'  => $post->body,
            'date'  => $post->date
        ];
    }
}
```

By default, a Resource object's **id** attribute will be set as the `id` property on the model. A serializer can provide a method to override this:

```php
public function getId($post)
{
    return $post->someOtherKey;
}
```

#### Relationships 

The `AbstractSerializer` allows you to define a public method for each relationship that exists for a resource. A relationship method should return a `Tobscure\JsonApi\Relationship` instance.

```php
public function comments($post)
{
    $element = new Collection($post->comments, new CommentSerializer);

    return new Relationship($element);
}
```

### Meta & Links

The `Document`, `Resource`, and `Relationship` classes allow you to add meta information:

```php
$document = new Document;
$document->addMeta('key', 'value');
$document->setMeta(['key' => 'value']);
```

They also allow you to add links in a similar way:

```php
$resource = new Resource($data, $serializer);
$resource->addLink('self', 'url');
$resource->setLinks(['key' => 'value']);
```

You can also easily add pagination links:

```php
$document->addPaginationLinks(
    'url', // The base URL for the links
    [],    // The query params provided in the request
    40,    // The current offset
    20,    // The current limit
    100    // The total number of results
);
```
Serializers can provide links and/or meta data as well:

```php
use Tobscure\JsonApi\AbstractSerializer;

class PostSerializer extends AbstractSerializer
{
    // ...
    
    public function getLinks($post) {
        return ['self' => '/posts/' . $post->id];
    }

    public function getMeta($post) {
        return ['some' => 'metadata for ' . $post->id];
    }
}
```

**Note:** Links and metadata of the resource overrule ones with the same key from the serializer!

### Parameters

The `Tobscure\JsonApi\Parameters` class allows you to easily parse and validate query parameters in accordance with the specification.

```php
use Tobscure\JsonApi\Parameters;

$parameters = new Parameters($_GET);
```

#### getInclude

Get the relationships requested for inclusion. Provide an array of available relationship paths; if anything else is present, an `InvalidParameterException` will be thrown.

```php
// GET /api?include=author,comments
$include = $parameters->getInclude(['author', 'comments', 'comments.author']); // ['author', 'comments']
```

#### getFields

Get the fields requested for inclusion, keyed by resource type.

```php
// GET /api?fields[articles]=title,body
$fields = $parameters->getFields(); // ['articles' => ['title', 'body']]
```

#### getSort

Get the requested sort criteria. Provide an array of available fields that can be sorted by; if anything else is present, an `InvalidParameterException` will be thrown.

```php
// GET /api?sort=-created,title
$sort = $parameters->getSort(['title', 'created']); // ['created' => 'desc', 'title' => 'asc']
```

#### getLimit and getOffset

Get the offset number and the number of resources to display using a page- or offset-based strategy. `getLimit` accepts an optional maximum. If the calculated offset is below zero, an `InvalidParameterException` will be thrown.

```php
// GET /api?page[number]=5&page[size]=20
$limit = $parameters->getLimit(100); // 20
$offset = $parameters->getOffset($limit); // 80

// GET /api?page[offset]=20&page[limit]=200
$limit = $parameters->getLimit(100); // 100
$offset = $parameters->getOffset(); // 20
```

### Error Handling

You can transform caught exceptions into JSON-API error documents using the `Tobscure\JsonApi\ErrorHandler` class. You must register the appropriate `Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface` instances.

```php
try {
    // API handling code
} catch (Exception $e) {
    $errors = new ErrorHandler;

    $errors->registerHandler(new InvalidParameterExceptionHandler);
    $errors->registerHandler(new FallbackExceptionHandler);

    $response = $errors->handle($e);

    $document = new Document;
    $document->setErrors($response->getErrors());

    return new JsonResponse($document, $response->getStatus());
}
```

## Contributing

Feel free to send pull requests or create issues if you come across problems or have great ideas. Any input is appreciated!

### Running Tests

```bash
$ phpunit
```

## License

This code is published under the [The MIT License](LICENSE). This means you can do almost anything with it, as long as the copyright notice and the accompanying license file is left intact.

