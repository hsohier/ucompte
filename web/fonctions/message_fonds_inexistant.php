<?php
function message_fonds_inexistant($fonds_inexistant)
{
	$message = '';
	$nombre_fonds = count($fonds_inexistant);
	if ($nombre_fonds == 1)
	{
		$message .= "L'ISIN ";
	}
	else
	{
		$message .= "Les ISINs ";
	}
	$i = 1;
	foreach ($fonds_inexistant as $fonds)
	{
		$message .= $fonds;
		if ($i != $nombre_fonds and $i != ($nombre_fonds-1))
		{
			$message .= ", ";
		}
		elseif ($i == ($nombre_fonds-1))
		{
			$message .= " et ";
		}
		else
		{
			$message .= " ";
		}
		$i++;
	}
	if ($nombre_fonds == 1)
	{
		$message .= "n'a pas été enregistré ";
	}
	else
	{
		$message .= "n'ont pas été enregistrés ";
	}
	$message .= "dans la base de données par les membres.";
	
	return $message;
}
?>