<?php
function id_portefeuille($nom)
{
   if ($nom == 'defensif')
   {
	   $id = 1;
   }
   elseif ($nom == 'reactif')
   {
	   $id = 2;
   }
   elseif ($nom == 'dynamique')
   {
	   $id = 3;
   }
   else
   {
	   $id = 0;
   }
   return $id;
}
?>