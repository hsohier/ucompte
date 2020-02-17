<?php
function valeurs_fondseuro($bdd, $id)
{
	$pourcentage_fondseuro = array();
	$req = $bdd->prepare('SELECT id_portefeuille, pourcentage FROM fondseuro
	WHERE id_utilisateur = :id_utilisateur');
	$req->execute(array('id_utilisateur' => $id));
	while ($donnees = $req->fetch())
	{
		$pourcentage_fondseuro[$donnees['id_portefeuille']] = $donnees['pourcentage'];
	}
	
	return $pourcentage_fondseuro;
}
?>