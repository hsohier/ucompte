<?php
function remplacer_vide_zero(& $item, $key) 
{
    if ($item == '') 
	{
        $item = '0';
    }
}
?>