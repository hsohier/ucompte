<?php
function message_fonds_manquant($resultat_fonds_manquant)
{
	$message_fonds_manquant_portefeuille = '';
	$nombre_fonds = count($resultat_fonds_manquant);
	
	$message_fonds_manquant_portefeuille = "Les informations relatives ";
	if ($nombre_fonds == 1)
	{
		$message_fonds_manquant_portefeuille .= "à l'ISIN ";
	}
	else
	{
		$message_fonds_manquant_portefeuille .= "aux ISINs ";
	}
	
	$i = 1;
	foreach ($resultat_fonds_manquant as $fonds)
	{
		$message_fonds_manquant_portefeuille .= $fonds;
		if ($i != $nombre_fonds and $i != ($nombre_fonds-1))
		{
			$message_fonds_manquant_portefeuille .= ', ';
		}
		elseif ($i == ($nombre_fonds-1))
		{
			$message_fonds_manquant_portefeuille .= ' et ';
		}
		else
		{
			$message_fonds_manquant_portefeuille .= ' ';
		}
		$i++;
	}
	
	$message_fonds_manquant_portefeuille .= "ne sont pas encore disponibles. Ces informations sont récupérées dans les 60 minutes qui suivent l'ajout d'un ISIN à la base de données.";
	
	return $message_fonds_manquant_portefeuille;
}
?>