<?php
include("nom_actifs.php");
include("nom_regions.php");

function couleurs_graphiques($bdd)
{
	$couleurs = ['#DC3912', '#E67300', '#FF9900', '#8B0707', '#109618', '#3366CC', '#990099', '#0099C6', '#DD4477', '#66AA00', '#3B3EAC', '#B82E2E', '#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC', '#329262', '#5574A6', '#3B3EAC']; // marron brun, rouge, orange foncé, orange, vert, bleu, violet, bleu turquoise, rose, vert clair
								
	$nom_actifs = nom_actifs();
	$couleurs_actifs = array_combine($nom_actifs, array_slice($couleurs, 0, count($nom_actifs)));
	$couleurs_actifs['? indéterminé'] = '#000000';

	$nom_regions = nom_regions($bdd);
	$couleurs_regions = array_combine($nom_regions, array_slice($couleurs, 1, count($nom_regions)));
	$couleurs_regions['? indéterminé'] = '#000000';
	
	return array($couleurs_actifs, $couleurs_regions);
}
?>