# ob-compress
Php compress Output Buffer In Gzip

This class can compress the output buffer of a web page or web request.

It can start capturing the output buffer of the current HTTP request and compress using the gzip compression method or none. The compressed output will be send with the necessary headers back to user browser and optimized.

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/ob-compress
```


```php 
<?php 
$obcompress = new Peterujah\NanoBlock\OBCompress();
ob_start('Peterujah\NanoBlock\OBCompress::OBStrip');
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
<?php $obcompress->html(ob_get_contents());
```
