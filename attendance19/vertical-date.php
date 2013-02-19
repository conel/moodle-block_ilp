<?php
	header ("Content-type: image/png"); 

    $date = $_GET['text'];
    $date = substr($date, 0, 5);
    //$date = 'Wk. ' . $date;
    // imagecreate (x width, y width)
    $img_handle = @imagecreatetruecolor (10, 45) or die ("Cannot Create image"); 

    // ImageColorAllocate (image, red, green, blue)
    $bg_colour = ImageColorAllocate($img_handle, 248, 249, 250); 
    $text_colour = ImageColorAllocate($img_handle, 0, 0, 0); 
    imagefilledrectangle($img_handle, 0, 0, 10, 45, $bg_colour);
    imagettftext($img_handle, 8, 90, 9, 37, $text_colour, 'verdana', $date);
    $img_handle = imagerotate($img_handle, 180, 0);
    ImagePng($img_handle); 
    ImageDestroy($img_handle)
?>
