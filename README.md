# ob-compress
Php compress Output Buffer In Gzip

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
