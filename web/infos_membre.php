<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/id_portefeuille.php");
include("fonctions/afficher_tableau_graphiques.php");
include("fonctions/valeurs_graphiques_actifs.php");
include("fonctions/valeurs_graphiques_regions.php");
include("fonctions/fonds_manquant.php");
include("fonctions/couleurs_graphiques.php");
include("fonctions/afficher_graphiques_actifs.php");
include("fonctions/afficher_graphiques_regions.php");
include("fonctions/message_fonds_manquant.php");
include("fonctions/afficher_fondseuro.php");
include("fonctions/valeurs_fondseuro.php");
include("fonctions/scripts_google.php");

function afficher_portefeuille($bdd, $id, $portefeuille, $message_fonds_manquant, $pourcentage_fondseuro)
{
	if (array_key_exists(id_portefeuille($portefeuille), $pourcentage_fondseuro))
	{
		echo '<div class="card-body pb-0">
		<h5>Part du fonds euro</h5>';
		afficher_fondseuro($pourcentage_fondseuro, $portefeuille);
		echo '</div>
		<hr class="mb-0" />';
	}
	
	$req = $bdd->prepare('SELECT * FROM unitesdecompte
	INNER JOIN utilisateurs_unitesdecompte
	ON utilisateurs_unitesdecompte.id_unitedecompte = unitesdecompte.id
	WHERE utilisateurs_unitesdecompte.id_utilisateur = :id_utilisateur
	AND utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
	ORDER BY utilisateurs_unitesdecompte.pourcentage DESC');
	$req->execute(array(
		'id_utilisateur' => $id,
		'id_portefeuille' => id_portefeuille($portefeuille)));
	
	if($req->rowCount())
	{		
		echo '<div class="card-body pb-0">
		<h5 class="card-title">Choix hors fonds euro</h5>
		<table class="table table-striped table-hover table-shrink m-0">
			<thead>
			  <tr>
				<th style="width: 20%" class="cell-first">ISIN</th>
				<th style="width: 50%">Nom</th>
				<th style="width: 30%">Allocation</th>
			  </tr>
			</thead>
			<tbody>';
			
		$somme = 0;
		$i = 1;
		while ($donnees = $req->fetch())
		{
			echo '<tr>
				<td class="isin cell-first">'.$donnees['isin'].'</td>
				<td>'.htmlentities($donnees['nom']).'</td>
				<td>'.$donnees['pourcentage'].'%</td>
			</tr>';
			$somme += $donnees['pourcentage'];
			$i++;
		}
		echo '</tbody>
		</table>';
		
		if ($somme == 100)
		{
			echo '<span id="correl_'.$portefeuille.'">Chargement...</span>';
		}
		
		echo '</div>
		<hr class="mb-0" />
		<div class="card-body">
		<h5 class="card-title">Actifs et régions hors fonds euro</h5>';
		
		if ($somme == 100)
		{
			if (array_key_exists($portefeuille, $message_fonds_manquant))
			{
				echo '<p class="mb-0">'.$message_fonds_manquant[$portefeuille].'</p>';
			}
			else
			{
				afficher_tableau_graphiques($portefeuille);
			}
		}
		else
		{
			echo '<p class="mb-0">Les actifs et les régions pourront être affichés lorsque le portefeuille sera finalisé (somme égale à 100%).</p>';
		}
		
		echo '</div>';
	}
	else
	{
		echo '<div class="card-body">';
		if (array_key_exists(id_portefeuille($portefeuille), $pourcentage_fondseuro))
		{
			echo '<h5 class="card-title">Choix hors fonds euro</h5>';
		}
		echo '<p class="mb-0">Portefeuille vide.</p>
		</div>';
	}
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
	<?php
	if (isset($_GET['nom']) and $_GET['nom'] != "")
	{
		$get_nom = urldecode($_GET['nom']);
		echo 'Membre "'.htmlentities($get_nom).'"';
	}
	else
	{
		echo 'Membre';
	}
	?>
  </title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="/theme.css" type="text/css">
	<!--Load the AJAX API-->
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
	
	$message = '';
	$message_fonds_manquant = array();
	
	if (isset($_GET['nom']) and $_GET['nom'] != "")
	{

		$req = $bdd->prepare('SELECT ut.id, ut.nom, IFNULL(rec.nbr_recommandations,0) AS nbr_recommandations 
		FROM utilisateurs ut
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = ut.id 
		WHERE ut.nom = :nom');
		$req->execute(array('nom' => $get_nom));
		
		if ($req->rowCount())
		{
			while ($donnees = $req->fetch())
			{
				$id_membre = $donnees['id'];
				$nom_membre = $donnees['nom'];
				$nbr_recommandations_membre = $donnees['nbr_recommandations'];
			}
			
			$req2 = $bdd->prepare('SELECT COUNT(*) AS nombre_portefeuilles
			FROM 
			(
				SELECT SUM(pourcentage) AS somme
				FROM utilisateurs_unitesdecompte
				WHERE id_utilisateur = :id
				GROUP BY id_portefeuille
			) utuc
			WHERE utuc.somme = 100');
			$req2->execute(array('id' => $id_membre));
			
			$nombre_portefeuilles = $req2->fetchColumn();
			
			$couleur_graphiques = couleurs_graphiques($bdd);
			$couleurs_actifs = $couleur_graphiques[0];
			$couleurs_regions = $couleur_graphiques[1];
			
			foreach (['defensif', 'reactif', 'dynamique'] as $portefeuille)
			{
				$req = $bdd->prepare('SELECT SUM(pourcentage) 
				FROM utilisateurs_unitesdecompte
				WHERE id_utilisateur = :id_utilisateur
				AND id_portefeuille = :id_portefeuille');
				$req->execute(array(
					'id_utilisateur' => $id_membre,
					'id_portefeuille' => id_portefeuille($portefeuille)));
					
				$somme = $req->fetchColumn();
				
				if ($somme == 100)
				{
					$resultat_fonds_manquant = fonds_manquant($bdd, $id_membre, $portefeuille);

					if (!empty($resultat_fonds_manquant))
					{
						$message_fonds_manquant[$portefeuille] = message_fonds_manquant($resultat_fonds_manquant);
					}
					else
					{
						$actifs_moyennes = valeurs_graphiques_actifs($bdd, $id_membre, $portefeuille);
						afficher_graphiques_actifs($portefeuille, $actifs_moyennes, $couleurs_actifs);
						
						$regions_moyennes = valeurs_graphiques_regions($bdd, $id_membre, $portefeuille);
						afficher_graphiques_regions($portefeuille, $regions_moyennes, $couleurs_regions);
					}
				}
			}
		}
		else
		{
			$message = 'Membre non-inscrit.';
		}
	}
	?>
	</script>
	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('membres');
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
		<?php
		if (isset($_GET['nom']) and $_GET['nom'] != "")
		{
			if ($message == '')
			{
				echo '<div class="boite-droite">';
				if ($nbr_recommandations_membre > 0)
				{
					echo $nbr_recommandations_membre.' reco.';
				}
				if ((isset($_SESSION['id']) and isset($_SESSION['nom'])) and $_SESSION['id'] != $id_membre)
				{
					$id = $_SESSION['id'];
		
					$req = $bdd->prepare('SELECT *
					FROM recommandations
					WHERE id_emetteur = :id_emetteur
					AND id_recommandation = :id_recommandation');
					$req->execute(array(
						'id_emetteur' => $id,
						'id_recommandation' => $id_membre));
					
					if(!($req->rowCount()))
					{
						echo '<a class="btn btn-primary btn-sm ml-1" href="membres.php?recommander='.urlencode($nom_membre).'">Recommander ce membre </a>';
					}
				}
				echo '</div>';
			}
			
			echo '<h3 class="text-center">Membre "'.htmlentities($get_nom).'"</h3>';
			
			if ($message == '')
			{
				$pourcentage_fondseuro = valeurs_fondseuro($bdd, $id_membre);
        ?>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille défensif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille défensif doit permettre des rendements légèrement supérieurs à un fonds euro, avec un risque de perte en capital très faible. Il est constitué en grande partie de fonds euro, ainsi que de fonds et de trackers reconnus pour leur faible volatilité. Il permet un placement relativement court (par exemple de deux ans) et est notamment conseillé pour débuter."
                data-container="body">
                <img src="/images/info.png" class="rounded-circle" width="15em"> </a>
            </div>
			<?php
			afficher_portefeuille($bdd, $id_membre, 'defensif', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille réactif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille réactif doit permettre un rendement sensiblement supérieur au portefeuille défensif en présentant un risque de perte en capital modéré à court terme. Il est constitué de fonds et de trackers suivant les marchés à la hausse comme à la baisse, avec une part en fonds euros plus réduite que dans le portefeuille défensif. Il correspond à un placement pour le moyen-terme (d’au moins cinq ans)."
                data-container="body">
                <img src="/images/info.png" class="rounded-circle" width="15em"> </a>
			</div>
			<?php
			afficher_portefeuille($bdd, $id_membre, 'reactif', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille dynamique&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille dynamique doit permettre un rendement supérieur au marché en contrepartie d\'une plus grande volatilité, et donc d\'un risque important de perte en capital à court et moyen terme. Il est principalement constitué de fonds et de trackers reposant sur des actions dont une partie peut apparaitre comme fragile (entreprises de petite capitalisation, pays émergents, …). Il correspond à un placement pour le long-terme (d’au moins dix ans) et peut nécessiter une surveillance régulière."
                data-container="body">
                <img src="/images/info.png" class="rounded-circle" width="15em"> </a>
			</div>
            <?php
			afficher_portefeuille($bdd, $id_membre, 'dynamique', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
		<?php
			}
			else
			{
				echo '<p class="bg-danger p-1">'.$message.'</p>';
			}
		}
		else
		{
			echo '<h3 class="text-center">Membre</h3>
			<p class="bg-danger p-1">Aucun nom de membre renseigné.</p>';
		}
		?>
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
  <?php
  if (isset($_GET['nom']) and $_GET['nom'] != "" and $message == '')
  {
	  echo '
		<script type="text/javascript">
		$(\'[data-toggle="tooltip"]\').tooltip();
		
		$(document).ready(function(){
			var portefeuilles = ["defensif", "reactif", "dynamique"];
			var indices_existants = [];
			for (var i = 0; i < portefeuilles.length; i++)
			{
				if (document.getElementById("correl_"+portefeuilles[i]))
				{
					indices_existants.push(i);
				}
			}
			
			$.ajax({
				data: "id='.$id_membre.'&port="+indices_existants.join("|"),
				type: "GET",
				url: "/correl.py",
				success: function(response) {
					correlations = JSON.parse(response);
					for (var i = 0; i < indices_existants.length; i++)
					{
						$("#correl_"+portefeuilles[indices_existants[i]]).html(correlations[i]);
					}
				}
			});
		});
	  </script>';
  }
  ?>
</body>

</html>