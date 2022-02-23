# ob-compress
Php compress Output Buffer In Gzip


```php 
<?php 
$obcompress = new OBCompress();
ob_start('OBCompress::OBStrip');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Text OB Compress</title>
</head>
  <body>
    
  </body>
</html>
<?php $obcompress->html(ob_get_contents());
```
