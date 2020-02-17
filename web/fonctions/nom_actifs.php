<?php
function nom_actifs()
{
	$noms = ['Actions (grandes)', 	'Actions (moyennes)', 	'Actions (petites)',	'Actions (divers)',	'Obligations',	'Liquidités',	'Autres',	'? indéterminé',	'Matières premières',	'Immobilier'];
	$cles = ['1-0',					'1-1',					'1-2',					'1-3',				'2',			'3',			'4',		'5',				'6',					'7'];
	
	return array_combine($cles, $noms);
}
?>