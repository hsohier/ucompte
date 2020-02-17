<?php
function nom_regions($bdd)
{
	$nom_regions = array();
	
	$req = $bdd->query('SELECT id, nom FROM bdd_regions ORDER BY nom ASC');
	while ($donnees = $req->fetch())
	{
		$nom_regions[$donnees['id']] = $donnees['nom'];
	}
	
	return $nom_regions;
}
?>