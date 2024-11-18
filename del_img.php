<?php

$imgName=$_GET['file'];
echo $imgName;
unlink("./files/$imgName");
header("location:manage.php");

?>