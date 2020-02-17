<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/id_portefeuille.php");
include("fonctions/couleurs_graphiques.php");
include("fonctions/afficher_graphiques_actifs.php");
include("fonctions/afficher_graphiques_regions.php");
include("fonctions/afficher_tableau_graphiques.php");
include("fonctions/afficher_fondseuro.php");
include("fonctions/scripts_google.php");

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
		echo '<table class="table table-striped table-hover table-shrink m-0">
			<thead>
			  <tr>
				<th style="width: 15%" class="text-center">Classement</th>
				<th style="width: 20%">ISIN</th>
				<th style="width: 45%">Nom</th>
				<th style="width: 20%">Allocation moyenne*</th>
			  </tr>
			</thead>
			<tbody>';
		$i = 1;
		while ($donnees = $req->fetch())
		{
			echo '<tr>
				<td class="text-center">'.$i.'</td>
				<td class="isin">'.$donnees['isin'].'</td>
				<td>'.htmlentities($donnees['nom']).'</td>
				<td>'.round($donnees['pourcentage_avg']).'%</td>
			</tr>';
			$i++;
		}
		echo '</tbody>
		</table>
		<p class="card-text p-y-1">* Allocation moyenne : Pourcentage moyen (hors fonds euro) de l\'ISIN dans les portefeuilles où il est utilisé<br />
		<a href="code_image.php">Afficher le classement au format image sur un autre site</a></p>';
	}
	else
	{
		echo "Portefeuille vide.";
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
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Synthèse</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript">

	  // Load the Visualization API and the corechart package.
	  google.charts.load('current', {'packages':['corechart']});
		<?php
		try
		{
			$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
		}
		catch(Exception $e)
		{
			die('Erreur : '.$e->getMessage());
		}

		$couleur_graphiques = couleurs_graphiques($bdd);
		$couleurs_actifs = $couleur_graphiques[0];
		$couleurs_regions = $couleur_graphiques[1];

		foreach (['defensif', 'reactif', 'dynamique'] as $portefeuille)
		{
			$actifs_moyennes = valeurs_graphiques_actifs($bdd, $portefeuille);
			$regions_moyennes = valeurs_graphiques_regions($bdd, $portefeuille);
			
			afficher_graphiques_actifs($portefeuille, $actifs_moyennes, $couleurs_actifs);
			afficher_graphiques_regions($portefeuille, $regions_moyennes, $couleurs_regions);
		}
		?>
	</script>
	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('index');

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
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Synthèse</h3>
		  <div class="card mb-3">
            <div class="card-header bg-light">Présentation</div>
            <div class="card-body text-justify">
			L'outil ucompte.com permet de partager vos choix et recommandations de placement sur fonds et ETFs. 
			Cette page fait la synthèse des portefeuilles renseignés : vous y trouverez notamment une sélection de fonds et d'ETFs fréquemment utilisés, ainsi que le contenu moyen des portefeuilles en termes d'actifs (actions, obligations, ...) et de régions (Eurozone, Etats-Unis, ...). 
			Il a été choisi de partager les portefeuilles en trois grandes catégories, chacune associée à un certain risque et horizon de placement (survolez les icones "<img src="images/info.png" class="rounded-circle" width="15em">"). 
			Ne ratez pas le <a href="testeur.php">Testeur et Optimiseur</a>, ainsi que la <a href="aide.php">page d'aide</a> pour plus de précisions.<br/>
			<br/>
			<?php
			$req = $bdd->query('SELECT COUNT(*) FROM utilisateurs');
			$nb_utilisateurs = $req->fetchColumn();
			
			$req2 = $bdd->query('SELECT COUNT(*) FROM (SELECT DISTINCT id_utilisateur, id_portefeuille FROM utilisateurs_unitesdecompte) portefeuilles');
			$nb_portefeuilles = $req2->fetchColumn();
			
			echo "L'outil rassemble aujourd'hui ".$nb_utilisateurs." membres et ".$nb_portefeuilles." portefeuilles hors fonds euros.";
			?>
			</div>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille défensif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille défensif doit permettre des rendements légèrement supérieurs à un fonds euro, avec un risque de perte en capital très faible. Il est constitué en grande partie de fonds euro, ainsi que de fonds et de trackers reconnus pour leur faible volatilité. Il permet un placement relativement court (par exemple de deux ans) et est notamment conseillé pour débuter."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
            </div>
            <div class="card-body pb-0">
              <h5>Part moyenne du fonds euro</h5>
				<?php
				afficher_fondseuro($pourcentage_fondseuro, 'defensif');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body pb-0">
              <h5 class="card-title">Sélection hors fonds euro</h5>
				<?php
				afficher_unitesdecompte($bdd, 'defensif');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body">
              <h5 class="card-title">Actifs et régions détenus en moyenne hors fonds euro</h5>
				<?php
				afficher_tableau_graphiques('defensif');
				?>
            </div>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille réactif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille réactif doit permettre un rendement sensiblement supérieur au portefeuille défensif en présentant un risque de perte en capital modéré à court terme. Il est constitué de fonds et de trackers suivant les marchés à la hausse comme à la baisse, avec une part en fonds euros plus réduite que dans le portefeuille défensif. Il correspond à un placement pour le moyen-terme (d’au moins cinq ans)."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
			</div>
			<div class="card-body pb-0">
              <h5>Part moyenne du fonds euro</h5>
				<?php
				afficher_fondseuro($pourcentage_fondseuro, 'reactif');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body pb-0">
              <h5 class="card-title">Sélection hors fonds euro</h5>
				<?php
				afficher_unitesdecompte($bdd, 'reactif');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body">
              <h5 class="card-title">Actifs et régions détenus en moyenne hors fonds euro</h5>
				<?php
				afficher_tableau_graphiques('reactif');
				?>
            </div>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille dynamique&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille dynamique doit permettre un rendement supérieur au marché en contrepartie d\'une plus grande volatilité, et donc d\'un risque important de perte en capital à court et moyen terme. Il est principalement constitué de fonds et de trackers reposant sur des actions dont une partie peut apparaitre comme fragile (entreprises de petite capitalisation, pays émergents, …). Il correspond à un placement pour le long-terme (d’au moins dix ans) et peut nécessiter une surveillance régulière."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
			</div>
            <div class="card-body pb-0">
              <h5>Part moyenne du fonds euro</h5>
				<?php
				afficher_fondseuro($pourcentage_fondseuro, 'dynamique');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body pb-0">
              <h5 class="card-title">Sélection hors fonds euro</h5>
				<?php
				afficher_unitesdecompte($bdd, 'dynamique');
				?>
            </div>
			<hr class="mb-0" />
            <div class="card-body">
              <h5 class="card-title">Actifs et régions détenus en moyenne hors fonds euro</h5>
				<?php
				afficher_tableau_graphiques('dynamique');
				?>
            </div>
          </div>
        </div>
		<?php
		script_googleadsense();
		?>
	  </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <script type="text/javascript">
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
  </script>
</body>

</html>