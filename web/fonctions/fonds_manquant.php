<?php
function fonds_manquant($bdd, $id, $portefeuille)
{
	$resultat_fonds_manquant = [];
	
	$req = $bdd->prepare("SELECT uc.isin
	FROM unitesdecompte uc
	INNER JOIN utilisateurs_unitesdecompte utuc
	ON utuc.id_unitedecompte = uc.id
	WHERE utuc.id_utilisateur = :id_utilisateur
	AND utuc.id_portefeuille = :id_portefeuille
	AND uc.date_donnees IS NULL");
	$req->execute(array(
		'id_utilisateur' => $id,
		'id_portefeuille' => id_portefeuille($portefeuille)));
		
	while ($donnees = $req->fetch())
	{
		$resultat_fonds_manquant[] = $donnees['isin'];
	}
	
	return $resultat_fonds_manquant;
}
?>