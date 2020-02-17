<?php
Header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>
<sauvegarde>
	<date>'.date('d/m/Y').'</date>
';

include("fonctions/id_portefeuille.php");

function afficher_unitesdecompte($bdd, $portefeuille)
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
	LIMIT 0, 10');
	$req->execute(array(
	'tau' => $tau,
	'id_portefeuille' => id_portefeuille($portefeuille)));

	if($req->rowCount())
	{
		$i = 1;
		while ($donnees = $req->fetch())
		{
			echo '			<isinselection>
				<classement>'.$i.'</classement>
				<isin>'.$donnees['isin'].'</isin>
				<nom>'.htmlspecialchars($donnees['nom'], ENT_XML1, 'UTF-8').'</nom>
				<moyenne>'.round($donnees['pourcentage_avg']).'</moyenne>
			</isinselection>';
			$i++;
		}
	}
	else
	{
		echo "			<erreur>Portefeuille vide.</erreur>";
	}
}

function valeurs_graphiques_actifs($bdd, $portefeuille)
{
	$req = $bdd->prepare("SELECT SUM(IFNULL(nbr_recommandations,0)+1)
	FROM
	(
		SELECT utilisateurs_unitesdecompte.id_utilisateur AS id, SUM(utilisateurs_unitesdecompte.pourcentage) AS pourcentage
		FROM utilisateurs_unitesdecompte
		INNER JOIN unitesdecompte
		ON unitesdecompte.id = utilisateurs_unitesdecompte.id_unitedecompte
		WHERE utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
		AND unitesdecompte.date_donnees IS NOT NULL
		GROUP BY utilisateurs_unitesdecompte.id_utilisateur
	) utuc
	LEFT JOIN
	(
		SELECT id_recommandation AS id, COUNT(*) AS nbr_recommandations
		FROM recommandations
		GROUP BY id_recommandation
	) rec
	ON rec.id = utuc.id
	WHERE utuc.pourcentage = 100");
	$req->execute(array('id_portefeuille' => id_portefeuille($portefeuille)));
	
	$somme_poids = $req->fetchColumn();
	
	$req2 = $bdd->prepare("SELECT nom_actifs_complet, somme_pourcentage
	FROM
	(
		SELECT actifs.nom AS nom_actifs_complet, SUM(uc_actifs.pourcentage*utuc.pourcentage/100*(1+IFNULL(rec.nbr_recommandations,0))) AS somme_pourcentage
		FROM bdd_actifs actifs
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_actif = actifs.id
		INNER JOIN utilisateurs_unitesdecompte utuc
		ON utuc.id_unitedecompte = uc_actifs.id_unitedecompte
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = utuc.id_utilisateur
		INNER JOIN
		(
			SELECT utilisateurs_unitesdecompte.id_utilisateur AS id, SUM(utilisateurs_unitesdecompte.pourcentage) AS pourcentage
			FROM unitesdecompte
			INNER JOIN utilisateurs_unitesdecompte
			ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
			WHERE utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
			AND unitesdecompte.date_donnees IS NOT NULL
			GROUP BY utilisateurs_unitesdecompte.id_utilisateur
		) verif
		ON verif.id = utuc.id_utilisateur
		WHERE utuc.id_portefeuille = :id_portefeuille
		AND utuc.pourcentage > 0
		AND verif.pourcentage = 100
		AND actifs.nom <> 'Actions'
		GROUP BY actifs.nom
		
		UNION ALL
		
		SELECT CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3)) AS nom_actifs_complet, SUM(uc_actifs.pourcentage*utuc.pourcentage/100*(1+IFNULL(rec.nbr_recommandations,0))) AS somme_pourcentage
		FROM bdd_actifs actifs
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_actif = actifs.id
		INNER JOIN bdd_unitesdecompte_actions uc_actions
		ON uc_actions.id_unitedecompte = uc_actifs.id_unitedecompte
		INNER JOIN utilisateurs_unitesdecompte utuc
		ON utuc.id_unitedecompte = uc_actifs.id_unitedecompte
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = utuc.id_utilisateur
		INNER JOIN
		(
			SELECT utilisateurs_unitesdecompte.id_utilisateur AS id, SUM(utilisateurs_unitesdecompte.pourcentage) AS pourcentage
			FROM unitesdecompte
			INNER JOIN utilisateurs_unitesdecompte
			ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
			WHERE utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
			AND unitesdecompte.date_donnees IS NOT NULL
			GROUP BY utilisateurs_unitesdecompte.id_utilisateur
		) verif
		ON verif.id = utuc.id_utilisateur
		WHERE utuc.id_portefeuille = :id_portefeuille
		AND utuc.pourcentage > 0
		AND verif.pourcentage = 100
		AND actifs.nom = 'Actions'
		AND uc_actions.id_actions <> '0'
		GROUP BY CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3))
	) resultat");
	$req2->execute(array('id_portefeuille' => id_portefeuille($portefeuille)));
		
	while ($donnees2 = $req2->fetch())
	{
		if ($donnees2['nom_actifs_complet'] == 'Actions0')
		{
			$nom_actif = 'Actions (grandes)';
		}
		elseif ($donnees2['nom_actifs_complet'] == 'Actions1')
		{
			$nom_actif = 'Actions (moyennes)';
		}
		elseif ($donnees2['nom_actifs_complet'] == 'Actions2')
		{
			$nom_actif = 'Actions (petites)';
		}
		elseif ($donnees2['nom_actifs_complet'] == 'Actions3')
		{
			$nom_actif = 'Actions (divers)';
		}
		else
		{
			$nom_actif = $donnees2['nom_actifs_complet'];
		}
		
		$actifs_moyennes[$nom_actif] = round($donnees2['somme_pourcentage']/$somme_poids);
	}
	
	arsort($actifs_moyennes);
	
	return $actifs_moyennes;
}
function valeurs_graphiques_regions($bdd, $portefeuille)
{
	$req = $bdd->prepare("SELECT SUM(IFNULL(nbr_recommandations,0)+1)
	FROM
	(
		SELECT utilisateurs_unitesdecompte.id_utilisateur AS id, SUM(utilisateurs_unitesdecompte.pourcentage) AS pourcentage
		FROM utilisateurs_unitesdecompte
		INNER JOIN unitesdecompte
		ON unitesdecompte.id = utilisateurs_unitesdecompte.id_unitedecompte
		WHERE utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
		AND unitesdecompte.date_donnees IS NOT NULL
		GROUP BY utilisateurs_unitesdecompte.id_utilisateur
	) utuc
	LEFT JOIN
	(
		SELECT id_recommandation AS id, COUNT(*) AS nbr_recommandations
		FROM recommandations
		GROUP BY id_recommandation
	) rec
	ON rec.id = utuc.id
	WHERE utuc.pourcentage = 100");
	$req->execute(array('id_portefeuille' => id_portefeuille($portefeuille)));

	$somme_poids = $req->fetchColumn();
	
	$req3 = $bdd->prepare("SELECT regions.nom AS nom_regions_complet, SUM(uc_regions.pourcentage*utuc.pourcentage/100*(1+IFNULL(rec.nbr_recommandations,0))) AS somme_pourcentage
	FROM bdd_regions regions
	INNER JOIN bdd_unitesdecompte_regions uc_regions
	ON uc_regions.id_region = regions.id
	INNER JOIN utilisateurs_unitesdecompte utuc
	ON utuc.id_unitedecompte = uc_regions.id_unitedecompte
	LEFT JOIN
	(
		SELECT id_recommandation, COUNT(*) AS nbr_recommandations
		FROM recommandations
		GROUP BY id_recommandation
	) rec
	ON rec.id_recommandation = utuc.id_utilisateur
	INNER JOIN
	(
		SELECT utilisateurs_unitesdecompte.id_utilisateur AS id, SUM(utilisateurs_unitesdecompte.pourcentage) AS pourcentage
		FROM unitesdecompte
		INNER JOIN utilisateurs_unitesdecompte
		ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
		WHERE utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
		AND unitesdecompte.date_donnees IS NOT NULL
		GROUP BY utilisateurs_unitesdecompte.id_utilisateur
	) verif
	ON verif.id = utuc.id_utilisateur
	WHERE utuc.id_portefeuille = :id_portefeuille
	AND utuc.pourcentage > 0
	AND verif.pourcentage = 100
	GROUP BY regions.nom");
	$req3->execute(array('id_portefeuille' => id_portefeuille($portefeuille)));
		
	while ($donnees3 = $req3->fetch())
	{
		$regions_moyennes[$donnees3['nom_regions_complet']] = round($donnees3['somme_pourcentage']/$somme_poids);
	}

	arsort($regions_moyennes);
	
	return $regions_moyennes;
}

try
{
	$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
}
catch(Exception $e)
{
	die('Erreur : '.$e->getMessage());
}

$pourcentage_fondseuro = array();
$req = $bdd->query('SELECT fe.id_portefeuille, SUM((IFNULL(rec.nbr_recommandations,0)+1)*fe.pourcentage)/SUM(IFNULL(rec.nbr_recommandations,0)+1) AS pourcentage_avg
FROM fondseuro fe
LEFT JOIN
(
	SELECT id_recommandation, COUNT(*) AS nbr_recommandations
	FROM recommandations
	GROUP BY id_recommandation
) rec
ON rec.id_recommandation = fe.id_utilisateur
GROUP BY fe.id_portefeuille');
while ($donnees = $req->fetch())
{
	$pourcentage_fondseuro[$donnees['id_portefeuille']] = round($donnees['pourcentage_avg']);
}


foreach (['defensif' => 'Défensif', 'reactif' => 'Réactif', 'dynamique' => 'Dynamique'] as $portefeuille => $type_portefeuille)
{
	echo "	<portefeuille>
		<type>".$type_portefeuille."</type>
		<fondseuro>".$pourcentage_fondseuro[id_portefeuille($portefeuille)]."</fondseuro>
		<selection>
";
	afficher_unitesdecompte($bdd, 'defensif');
	echo "		</selection>
		<actifs>
";
	$actifs_moyennes = valeurs_graphiques_actifs($bdd, $portefeuille);
	foreach ($actifs_moyennes as $key => $value)
	{
		echo "			<actif>
				<nom>".$key."</nom>
				<pourcentage>".$value."</pourcentage>
			</actif>";
	}
	echo"		</actifs>
		<regions>
";
	$regions_moyennes = valeurs_graphiques_regions($bdd, $portefeuille);
	foreach ($regions_moyennes as $key => $value)
	{
		echo "			<region>
				<nom>".$key."</nom>
				<pourcentage>".$value."</pourcentage>
			</region>";
	}
	echo "		</regions>
	</portefeuille>
";
}
echo "</sauvegarde>"
?>