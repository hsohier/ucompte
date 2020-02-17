<?php
function valeurs_graphiques_actifs($bdd, $id, $portefeuille)
{
	$req = $bdd->prepare("SELECT pourcentage_final, nom_actifs_complet
	FROM
	(
		SELECT SUM(utuc.pourcentage/100*uc_actifs.pourcentage) AS pourcentage_final, actifs.nom AS nom_actifs_complet
		FROM utilisateurs_unitesdecompte utuc
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_unitedecompte = utuc.id_unitedecompte
		INNER JOIN bdd_actifs actifs
		ON actifs.id = uc_actifs.id_actif
		WHERE utuc.id_utilisateur = :id_utilisateur
		AND utuc.id_portefeuille = :id_portefeuille
		AND actifs.nom <> 'Actions'
		GROUP BY actifs.nom
		
		UNION ALL
		
		SELECT SUM(utuc.pourcentage/100*uc_actifs.pourcentage) AS pourcentage_final, CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3)) AS nom_actifs_complet
		FROM utilisateurs_unitesdecompte utuc
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_unitedecompte = utuc.id_unitedecompte
		INNER JOIN bdd_actifs actifs
		ON actifs.id = uc_actifs.id_actif
		INNER JOIN bdd_unitesdecompte_actions uc_actions
		ON uc_actions.id_unitedecompte = utuc.id_unitedecompte
		WHERE utuc.id_utilisateur = :id_utilisateur
		AND utuc.id_portefeuille = :id_portefeuille
		AND actifs.nom = 'Actions'
		GROUP BY CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3))
	)resultat");
	$req->execute(array(
		'id_utilisateur' => $id,
		'id_portefeuille' => id_portefeuille($portefeuille)));
		
	while ($donnees = $req->fetch())
	{
		if (round($donnees['pourcentage_final']) > 0)
		{
			if ($donnees['nom_actifs_complet'] == 'Actions0')
			{
				$nom_actif = 'Actions (grandes)';
			}
			elseif ($donnees['nom_actifs_complet'] == 'Actions1')
			{
				$nom_actif = 'Actions (moyennes)';
			}
			elseif ($donnees['nom_actifs_complet'] == 'Actions2')
			{
				$nom_actif = 'Actions (petites)';
			}
			elseif ($donnees['nom_actifs_complet'] == 'Actions3')
			{
				$nom_actif = 'Actions';
			}
			else
			{
				$nom_actif = $donnees['nom_actifs_complet'];
			}
			
			$actifs_moyennes[$nom_actif] = round($donnees['pourcentage_final']);
		}
	}

	arsort($actifs_moyennes);
	
	return $actifs_moyennes;
}
?>