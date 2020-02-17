<?php
function ucs_pour_image($bdd, $portefeuille)
{
	$tau = 5;
	
	$req = $bdd->prepare('SELECT uc.nom AS nom, uc.isin AS isin, SUM((IFNULL(rec.nbr_recommandations,0)+1)*utuc.pourcentage)/SUM(IFNULL(rec.nbr_recommandations,0)+1) AS pourcentage_avg, SUM(utuc.pourcentage*(1-0.75*LEAST(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(utuc.date), :tau*31536000)/(:tau*31536000))*(1+IFNULL(rec.nbr_recommandations,0))) AS score
	FROM unitesdecompte uc
	INNER JOIN utilisateurs_unitesdecompte utuc
	ON utuc.id_unitedecompte = uc.id
	LEFT JOIN
	(
		SELECT id_recommandation, COUNT(*) AS nbr_recommandations
		FROM recommandations
		GROUP BY id_recommandation
	) rec
	ON rec.id_recommandation = utuc.id_utilisateur
	WHERE utuc.pourcentage > 0
	AND utuc.id_portefeuille = :id_portefeuille
	GROUP BY utuc.id_unitedecompte
	ORDER BY score DESC
	LIMIT 0, 5');
	$req->execute(array(
	'tau' => $tau,
	'id_portefeuille' => id_portefeuille($portefeuille)));

	$lignes[0] = 'Sélection hors fonds euro pour un portefeuille ';
	if ($portefeuille == 'defensif')
	{
		$lignes[0] .= 'défensif :';
	}
	elseif ($portefeuille == 'reactif')
	{
		$lignes[0] .= 'réactif :';
	}
	elseif ($portefeuille == 'dynamique')
	{
		$lignes[0] .= 'dynamique :';
	}
	
	if($req->rowCount())
	{
		$i = 1;
		while ($donnees = $req->fetch())
		{
			$texte = $i.') '.$donnees['isin'].' '.$donnees['nom'];
			$lignes[$i] = substr($texte, 0, 55);
			$i++;
		}

	}
	else
	{
		$lignes[1] = 'Portefeuille vide.';
	}
	
	return $lignes;
}
?>