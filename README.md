# ob-compress
Php OBCompress, compresses the Output Buffer In Gzip

This class can compress the output buffer of a web page or web request.

It can start capturing the output buffer of the current HTTP request and compress using the gzip compression method or none based on specified options. The compressed output will be send with the necessary headers back to user browser and optimized. It can process the responses of requests and output data in JSON, HTML, or plain text format faster than regular request output.

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/ob-compress
```

To compress webpage output, see the below example code

```php 
<?php 
use Peterujah\NanoBlock\OBCompress;
ob_start('OBCompress::ob_strip');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Text OB Compress</title>
</head>
  <body>
    This will be compress and optimized
  </body>
</html>
<?php (new OBCompress())->html(ob_get_contents());
```

To retrieve data from server it can be done like below
```php
use Peterujah\NanoBlock\OBCompress;
$response = array(
  "foo" => "Foo",
  "bar" => "Bar"
);
(new OBCompress())->json($response);
```

Available Response methods

```php 
$compress->json($data);
```

```php 
$compress->text($data);
```

```php 
$compress->html($data);
```

```php 
$compress->run($data, $contentType);
```

```php 
$compress->start();
```

```php 
$compress->end($contentType);
```

```php 
$compress->with($data, $statusCode, $contentType);
```

```php 
$compress::ob_strip($content);
```
