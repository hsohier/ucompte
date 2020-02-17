<?php
function valeurs_graphiques_regions($bdd, $id, $portefeuille)
{
	$req2 = $bdd->prepare("SELECT SUM(utuc.pourcentage/100*uc_regions.pourcentage) AS pourcentage_final, regions.nom AS nom_regions_complet
	FROM utilisateurs_unitesdecompte utuc
	INNER JOIN bdd_unitesdecompte_regions uc_regions
	ON uc_regions.id_unitedecompte = utuc.id_unitedecompte
	INNER JOIN bdd_regions regions
	ON regions.id = uc_regions.id_region
	WHERE utuc.id_utilisateur = :id_utilisateur
	AND utuc.id_portefeuille = :id_portefeuille
	GROUP BY regions.nom");
	$req2->execute(array(
		'id_utilisateur' => $id,
		'id_portefeuille' => id_portefeuille($portefeuille)));
		
	while ($donnees2 = $req2->fetch())
	{
		if (round($donnees2['pourcentage_final']) > 0)
		{
			$regions_moyennes[$donnees2['nom_regions_complet']] = round($donnees2['pourcentage_final']);
		}
	}

	arsort($regions_moyennes);
	
	return $regions_moyennes;
}
?>