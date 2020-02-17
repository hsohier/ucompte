<?php
include("fonctions/id_portefeuille.php");
include("fonctions/ucs_pour_image.php");

try
{
	$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
}
catch(Exception $e)
{
	die('Erreur : '.$e->getMessage());
}

if (isset($_GET['portefeuille']) and in_array($_GET['portefeuille'], ['defensif', 'reactif', 'dynamique']))
{
	$text = ucs_pour_image($bdd, $_GET['portefeuille']);
}
else
{
	$text[0] = "Appel de l'image incorrect. Choix possibles :";
	$text[1] = "1) image_top_uc.php?portefeuille=defensif";
	$text[2] = "2) image_top_uc.php?portefeuille=reactif";
	$text[3] = "3) image_top_uc.php?portefeuille=dynamique";
}

date_default_timezone_set('Europe/Paris');
$text2 = 'Image générée le '.date('d\/m\/Y \à G\hi');

$width = 550;
$height = 145;
$font = 'arial.ttf';

$my_img = imagecreate( $width, $height );
$background  = imagecolorallocate( $my_img, 255, 255, 255 );
$text_colour = imagecolorallocate( $my_img, 0, 0, 0 );
$text2_colour = imagecolorallocate( $my_img, 102, 102, 102 );
$line_colour = imagecolorallocate( $my_img, 0, 0, 0 );

foreach ($text as $i => $value)
{
	//imagestring( $my_img, 5, 20, (10+$i*20), utf8_decode($value), $text_colour );
	imagettftext($my_img, 12, 0, 20, (25+$i*20), $text_colour, $font, $value);
}

imagettftext($my_img, 8, 0, $width-190, $height-8, $text2_colour, $font, $text2);

imagesetthickness ( $my_img, 4 );
imageline( $my_img, 0, 0, $width, 0, $line_colour );
imageline( $my_img, $width-1, 0, $width-1, $height-1, $line_colour );
imageline( $my_img, $width-1, $height-1, 0, $height-1, $line_colour );
imageline( $my_img, 0, $height-1, 0, 0, $line_colour );

header( "Content-type: image/png" );
imagepng( $my_img );
imagedestroy( $my_img ); 
?> 