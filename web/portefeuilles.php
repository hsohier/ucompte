<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/id_portefeuille.php");
include("fonctions/afficher_tableau_graphiques.php");
include("fonctions/valeurs_graphiques_actifs.php");
include("fonctions/valeurs_graphiques_regions.php");
include("fonctions/fonds_manquant.php");
include("fonctions/message_fonds_manquant.php");
include("fonctions/couleurs_graphiques.php");
include("fonctions/afficher_graphiques_actifs.php");
include("fonctions/afficher_graphiques_regions.php");
include("fonctions/remplacer_vide_zero.php");
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
		echo  '<div class="card-body pb-0">
		<h5 class="card-title">Choix hors fonds euro</h5>
		<form action="portefeuilles.php" method="post" id="formulaire_'.$portefeuille.'">
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
				<td><label for="pourcentage_'.$portefeuille.'_'.$donnees['isin'].'" class="col-form-label py-0">'.htmlentities($donnees['nom']).'</label></td>
				<td><div class="col-3 p-0"><div class="input-group"><input type="text" name="pourcentage[]" id="pourcentage_'.$portefeuille.'_'.$donnees['isin'].'" autocomplete="off" value="'.$donnees['pourcentage'].'" maxlength="3" onkeyup="this.value=this.value.replace(/[^\d]+/,\'\'); somme_pourcentages(\'formulaire_'.$portefeuille.'\')" class="form-control form-control-sm"><span class="input-group-addon">%</span></div></div>
				<input type="hidden" name="isin[]" value="'.$donnees['isin'].'"></td>
			</tr>';
			$somme += $donnees['pourcentage'];
			$pct_testeur[$donnees['isin']] = $donnees['pourcentage'];
			$i++;
		}
		
		echo '<tr>
		<td class="cell-first">Somme :</td>
		<td></td>
		<td><div class="col-3 p-0"><div class="input-group"><input type="text" name="somme" maxlength="3" value="'.$somme.'" class="form-control form-control-sm ';
		if ($somme != 100 and $somme != 0) {echo 'form_incorrect';}
		echo '" disabled><span class="input-group-addon">%</span></div></div></td>
		</tr>
		</tbody>
		</table>
		<input type="hidden" name="portefeuille" value="'.$portefeuille.'">
		<input type="hidden" name="action" value="repartition">
		<p class="mb-0">
		[<a href="testeur.php?';
		$j = 1;
		foreach ($pct_testeur as $key => $value)
		{
			echo 'pct['.$key.']='.$value;
			if ($j != ($i-1))
			{
				echo '&';
			}
			$j++;
		}
		echo '" class="sans_decoration">Transmettre la dernière répartition enregistrée au testeur et optimiseur</a>] [<a onclick="repartition_egale(\'formulaire_'.$portefeuille.'\')" style="cursor: pointer; cursor: hand;">Répartir de manière égale</a>] 
		<span id="formulaire_'.$portefeuille.'_avert" class="important" style="display: none">Pensez à mettre à jour pour confirmer.</span></p>';
		if ($somme == 100) {echo 'Recommandations basées sur des portefeuilles similaires : <span id="recommandations_'.$portefeuille.'">Chargement...</span><br />';}
		echo '<p class="small text-muted mb-0">Une ligne mise à zéro est supprimée du portefeuille.<br />
		La somme des pourcentages doit être égale à 100 ou 0 pour mettre à jour le portefeuille.</p>
		<button type="submit" class="btn btn-primary btn-sm">Mettre à jour</button>
		</form>
		</div>
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
  <title>Vos portefeuilles</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css?v=2" type="text/css">
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript">
	// Load the Visualization API and the corechart package.
	google.charts.load('current', {'packages':['corechart']});
	<?php
		if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
		{
			try
			{
				$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
			}
			catch(Exception $e)
			{
				die('Erreur : '.$e->getMessage());
			}
			
			$id = $_SESSION['id'];
			
			$message = "";
			$message_type = "success";
			$message_portefeuille = "";
			$message_fonds_manquant = array();
			$message_header = "";

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				if ($_POST['action'] == 'selection')
				{
					if(in_array($_POST['portefeuille'], ['defensif', 'reactif', 'dynamique']))
					{
						if (preg_match("#^[A-Z0-9]{12}$#", $_POST['isin']))
						{
							$req = $bdd->prepare('SELECT * FROM unitesdecompte WHERE isin = :isin');
							$req->execute(array('isin' => $_POST['isin']));

							if($req->rowCount())
							{
								$resultat = $req->fetch();
								
								$req2 = $bdd->prepare('SELECT * FROM utilisateurs_unitesdecompte
								WHERE id_utilisateur = :id_utilisateur AND id_portefeuille = :id_portefeuille AND id_unitedecompte = :id_unitedecompte');
								$req2->execute(array(
									'id_utilisateur' => $id,
									'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
									'id_unitedecompte' => $resultat['id']));
								
								if($req2->rowCount())
								{
									$message = "Vous avez déjà ajouté cet ISIN à ce portefeuille.";
									$message_type = "danger";
								}
								else
								{
									$req3 = $bdd->prepare('INSERT INTO utilisateurs_unitesdecompte(id_utilisateur, id_portefeuille, id_unitedecompte, pourcentage, date) VALUES(:id_utilisateur, :id_portefeuille, :id_unitedecompte, 0, CURDATE())');
									$req3->execute(array(
										'id_utilisateur' => $id,
										'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
										'id_unitedecompte' => $resultat['id']));
										
									$message_portefeuille = "L'ISIN ".htmlentities($_POST['isin'])." a bien été ajoutée à votre portefeuille.";
									$message_type = "success";
								}
							}
							else
							{
								$message_header = '<script src=\'https://www.google.com/recaptcha/api.js\'></script>
								<script type="text/javascript">
								  var onSubmit = function(response) {
									document.getElementById("form_ajout").submit();
								  };
								</script>';
								
								$message ='<p>Cet ISIN n\'est pas encore enregistré dans notre base de données, merci de lui associer un nom. 
								Préférez les formes courtes et synthétiques, en évitant le tout majuscule.<br />
								S\'il s\'agit d\'un ETF, commencez par "ETF | ".</p>
								<form action="portefeuilles.php" method="post" id="form_ajout" class="form-inline mb-1">
								<label for="nom" class="mr-1">Nom :</label>
								<input type="text" class="form-control form-control-sm mr-1" id="nom" name="nom" maxlength="100" required>
								<input type="hidden" name="isin" value="'.$_POST['isin'].'">
								<input type="hidden" name="portefeuille" value="'.$_POST['portefeuille'].'">
								<input type="hidden" name="action" value="ajout">
								<button
								class="g-recaptcha btn btn-light btn-sm"
								data-sitekey="6LdSGjkUAAAAAFIyLKjvCkgt3pz6UblNM5jPnsfE"
								data-callback="onSubmit">
								Ajouter
								</button>
								</form>';
								$message_type = "success";
							}
						}
						else
						{
							$message = "Le format de l'ISIN n'est pas correct.";
							$message_type = "danger";
						}
					}
					else
					{
						$message = "Portefeuille incorrect.";
						$message_type = "danger";
					}
				}
				elseif ($_POST['action'] == 'ajout')
				{
					require('autoload.php');
					$secret = "*****";
					$recaptcha = new \ReCaptcha\ReCaptcha($secret);
					$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
					
					if ($resp->isSuccess())
					{
						if(in_array($_POST['portefeuille'], ['defensif', 'reactif', 'dynamique']))
						{
							if (preg_match("#^[A-Z0-9]{12}$#", $_POST['isin']))
							{
								if(strlen($_POST['nom']) <= 100)
								{
									$req = $bdd->prepare('SELECT * FROM unitesdecompte WHERE isin = :isin');
									$req->execute(array('isin' => $_POST['isin']));
									
									if($req->rowCount())
									{
										$message = "Cet ISIN existe déjà.";
										$message_type = "danger";
									}
									else
									{
										$req2 = $bdd->prepare('INSERT INTO unitesdecompte(isin, nom) VALUES(:isin, :nom)');
										$req2->execute(array(
											'isin' => $_POST['isin'],
											'nom' => $_POST['nom']));
											
										$req3 = $bdd->prepare('SELECT * FROM unitesdecompte WHERE isin = :isin');
										$req3->execute(array('isin' => $_POST['isin']));
										$resultat3 = $req3->fetch();

										$req4 = $bdd->prepare('INSERT INTO utilisateurs_unitesdecompte(id_utilisateur, id_portefeuille, id_unitedecompte, pourcentage, date)
										VALUES(:id_utilisateur, :id_portefeuille, :id_unitedecompte, 0, CURDATE())');
										$req4->execute(array(
											'id_utilisateur' => $id,
											'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
											'id_unitedecompte' => $resultat3['id']));
											
										$message_portefeuille = "L'ISIN ".$_POST['isin']." a été enregistrée et ajoutée à votre portefeuille.";
										$message_type = "success";
									}
								}
								else
								{
									$message = "Le nom est trop long.";
									$message_type = "danger";
								}
							}
							else
							{
								$message = "Le format de l'ISIN n'est pas correct.";
								$message_type = "danger";
							}
						}
						else
						{
							$message = "Portefeuille incorrect.";
							$message_type = "danger";
						}
					}
					else
					{
						$message = 'Erreur CAPTCHA : ';
						foreach ($resp->getErrorCodes() as $code) {
							$message .= $code.' ';
						}
						$message_type = "danger";
					}
				}
				elseif ($_POST['action'] == 'repartition')
				{
					if(in_array($_POST['portefeuille'], ['defensif', 'reactif', 'dynamique']))
					{
						$post_pourcentage = $_POST['pourcentage'];
						array_walk_recursive($post_pourcentage, 'remplacer_vide_zero');
						$verif_entiers = ($post_pourcentage == array_filter($post_pourcentage, 'ctype_digit'));

						if ($verif_entiers)
						{
							if (array_sum($post_pourcentage) == 100 or array_sum($post_pourcentage) == 0)
							{
								foreach ($post_pourcentage as $key=>$value)
								{
									if ($value == 0)
									{
										$req = $bdd->prepare('DELETE utilisateurs_unitesdecompte
										FROM utilisateurs_unitesdecompte
										INNER JOIN unitesdecompte
										ON unitesdecompte.id = utilisateurs_unitesdecompte.id_unitedecompte 
										WHERE utilisateurs_unitesdecompte.id_utilisateur = :id_utilisateur
										AND utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
										AND unitesdecompte.isin = :isin');
										$req->execute(array(
											'id_utilisateur' => $id,
											'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
											'isin' => $_POST['isin'][$key]));
									}
									else
									{
										$req = $bdd->prepare('UPDATE utilisateurs_unitesdecompte
										INNER JOIN unitesdecompte
										ON unitesdecompte.id = utilisateurs_unitesdecompte.id_unitedecompte 
										SET utilisateurs_unitesdecompte.pourcentage = :pourcentage, date = CURDATE() 
										WHERE utilisateurs_unitesdecompte.id_utilisateur = :id_utilisateur
										AND utilisateurs_unitesdecompte.id_portefeuille = :id_portefeuille
										AND unitesdecompte.isin = :isin');
										$req->execute(array(
											'pourcentage' => $value,
											'id_utilisateur' => $id,
											'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
											'isin' => $_POST['isin'][$key]));
									}
									$message_portefeuille = "Votre nouvelle répartition a bien été enregistrée.";
									$message_type = "success";
								}
							}
							else
							{
								$message = "La somme des pourcentages doit être égale à 100 ou 0.";
								$message_type = "danger";
							}
						}
						else
						{
							$message = "Les pourcentages doivent être des entiers.";
							$message_type = "danger";
						}
					}
					else
					{
						$message = "Portefeuille incorrect.";
						$message_type = "danger";
					}
				}
				elseif ($_POST['action'] == 'fondseuro')
				{
					if(in_array($_POST['portefeuille'], ['defensif', 'reactif', 'dynamique']))
					{
						if (isset($_POST['effacer_fondseuro']))
						{
							$req = $bdd->prepare('SELECT * FROM fondseuro
							WHERE id_utilisateur = :id_utilisateur
							AND id_portefeuille = :id_portefeuille');
							$req->execute(array(
								'id_utilisateur' => $id,
								'id_portefeuille' => id_portefeuille($_POST['portefeuille'])));
							
							if($req->rowCount())
							{
								$req2 = $bdd->prepare('DELETE FROM fondseuro
								WHERE id_utilisateur = :id_utilisateur
								AND id_portefeuille = :id_portefeuille');
								$req2->execute(array(
									'id_utilisateur' => $id,
									'id_portefeuille' => id_portefeuille($_POST['portefeuille'])));
									
								$message_portefeuille = "Le pourcentage en fonds euro a bien été effacé.";
								$message_type = "success";
							}
							else
							{
								$message_portefeuille = "Vous n'avez pas encore entré le pourcentage en fonds euro de ce portefeuille.";
								$message_type = "danger";
							}
						}
						elseif ($_POST['pourcentage_fondseuro'] != '' and ctype_digit($_POST['pourcentage_fondseuro']) and intval($_POST['pourcentage_fondseuro']) <= 100)
						{
							$req = $bdd->prepare('INSERT INTO fondseuro (id_utilisateur, id_portefeuille, pourcentage) VALUES(:id_utilisateur, :id_portefeuille, :pourcentage_fondseuro)
							ON DUPLICATE KEY UPDATE pourcentage=:pourcentage_fondseuro');
							$req->execute(array(
								'id_utilisateur' => $id,
								'id_portefeuille' => id_portefeuille($_POST['portefeuille']),
								'pourcentage_fondseuro' => $_POST['pourcentage_fondseuro']));
							
							$message_portefeuille = "Le pourcentage en fonds euro a bien été mis à jour.";
							$message_type = "success";
						}
						else
						{
							$message = "Le pourcentage en fonds euro doit être un entier inférieur ou égal à 100.";
							$message_type = "danger";
						}
					}
					else
					{
						$message = "Portefeuille incorrect.";
						$message_type = "danger";
					}
				}
			}
			
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
					'id_utilisateur' => $id,
					'id_portefeuille' => id_portefeuille($portefeuille)));
					
				$somme = $req->fetchColumn();
				
				if ($somme == 100)
				{
					$resultat_fonds_manquant = fonds_manquant($bdd, $id, $portefeuille);
					
					if (!empty($resultat_fonds_manquant))
					{
						$message_fonds_manquant[$portefeuille] = message_fonds_manquant($resultat_fonds_manquant);
					}
					else
					{
						$actifs_moyennes = valeurs_graphiques_actifs($bdd, $id, $portefeuille);
						afficher_graphiques_actifs($portefeuille, $actifs_moyennes, $couleurs_actifs);
						
						$regions_moyennes = valeurs_graphiques_regions($bdd, $id, $portefeuille);
						afficher_graphiques_regions($portefeuille, $regions_moyennes, $couleurs_regions);
					}
				}
			}
		}
	?>
	</script>
	<?php
	if (isset($_SESSION['id']) AND isset($_SESSION['nom']) AND $message_header != "")
	{
		echo $message_header;
	}
	script_googleanalytics();
	?>
</head>

<body class="bg-light">
<?php
afficher_navbar('portefeuilles');
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
			<?php
			if (isset($_SESSION['id']) and isset($_SESSION['nom']))
			{
				echo '<div class="boite-droite">
					<a class="btn btn-primary btn-sm" href="/membres/'.urlencode($_SESSION['nom']).'">Voir votre fiche publique </a>
				</div>';
			}
			?>
          <h3 class="text-center">Vos portefeuilles</h3>
			<?php
			if (!(isset($_SESSION['id']) and isset($_SESSION['nom'])))
			{
				echo '<p class="bg-danger p-2">Vous n\'êtes pas connecté.</p>';
			}
			else
			{
			?>
          <div class="row">
            <div class="col-6 pb-2">
              <div class="card mb-3">
                <div class="card-header bg-light">Pourcentage en fonds euro</div>               
				<?php
				if (($message != "") and $_POST['action'] == 'fondseuro')
				{
					echo '<div class="card-body pb-0">
					<div class="bg-'.$message_type.' mb-0 p-2">'.$message.'</div>
					</div>';
				}
				?>
				<div class="card-body">
					<form action="portefeuilles.php" method="post" id="form_fondseuro">
					  <div class="form-group row my-0">
					  <div class="col-7 pr-0">
						<label for="pourcentage_fondseuro" class="col-form-label py-0">Nouveau pourcentage en fonds euro :</label>
						</div>
						<div class="col-2 pl-0">
						<div class="input-group">
						  <input type="text" class="form-control form-control-sm" id="pourcentage_fondseuro" name="pourcentage_fondseuro" required>
						  <span class="input-group-addon">%</span>
						</div>
						</div>
					  </div>
					  <div class="form-group row my-0">
					  <div class="col-7 pr-0">
						<label for="effacer_fondseuro" class="col-form-label py-0">Seulement effacer la valeur actuelle :</label>
						</div>
						<div class="col-2 pl-0">
						  <div class="form-check">
							<input class="form-check-input" type="checkbox" id="effacer_fondseuro" name="effacer_fondseuro" onclick="disable_text(this.checked)">
						  </div>
						</div>
					  </div>
					  <div class="form-group row my-0">
					    <div class="col-7 pr-0">
							<label for="portefeuille_fondseuro" class="col-form-label">Portefeuille :</label>
						</div>
						<div class="col-3 pl-0">
							<select class="form-control form-control-sm" id="portefeuille_fondseuro" name="portefeuille">
								<option value="defensif">Défensif</option>
								<option value="reactif">Réactif</option>
								<option value="dynamique">Dynamique</option>
							</select>
						</div>
					  </div>
					  <input type="hidden" name="action" value="fondseuro">
					  <div class="form-group row my-0">
					  <div class="col-7 pr-0"></div>
						<div class="col-4 pl-0">
						  <button type="submit" class="btn btn-light btn-sm">Mettre à jour</button>
						</div>
					  </div>
					</form>
                </div>
              </div>
            </div>
            <div class="col-6 pb-2">
              <div class="card mb-3">
                <div class="card-header bg-light">Sélectionner un ISIN</div>
				<?php
				if (($message != "") and ($_POST['action'] == 'selection' or $_POST['action'] == 'ajout'))
				{
					echo '<div class="card-body pb-0">
					<div class="bg-'.$message_type.' mb-0 p-2">'.$message.'</div>
					</div>';
				}
				?>
				<div class="card-body">
                  <form action="portefeuilles.php" method="post">
					  <div class="form-group row my-0">
					  <div class="col-3 pr-0">
						<label for="isin" class="col-form-label pt-0">ISIN :</label>
						</div>
						<div class="col-4 pl-0 pb-1">
						  <input type="text" class="form-control form-control-sm" id="isin" name="isin" maxlength="12" placeholder="FR0123456789" onkeyup="this.value=this.value.replace(/[^\dA-Z]+/,\'\')" required>
						</div>
					  </div>

					  <div class="form-group row my-0">
					    <div class="col-3 pr-0">
							<label for="portefeuille" class="col-form-label pt-0">Portefeuille :</label>
						</div>
						<div class="col-3 pl-0 pb-1">
							<select class="form-control form-control-sm" id="portefeuille" name="portefeuille">
								<option value="defensif">Défensif</option>
								<option value="reactif">Réactif</option>
								<option value="dynamique">Dynamique</option>
							</select>
						</div>
					  </div>
					  <input type="hidden" name="action" value="selection">
					  <div class="form-group row my-0">
					  <div class="col-3 pr-0"></div>
						<div class="col-4 pl-0">
						  <button type="submit" class="btn btn-light btn-sm">Sélectionner</button>
						</div>
					  </div>
					</form>
                </div>
              </div>
            </div>
          </div>
		  <?php
		  $pourcentage_fondseuro = valeurs_fondseuro($bdd, $id);
		  ?>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille défensif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille défensif doit permettre des rendements légèrement supérieurs à un fonds euro, avec un risque de perte en capital très faible. Il est constitué en grande partie de fonds euro, ainsi que de fonds et de trackers reconnus pour leur faible volatilité. Il permet un placement relativement court (par exemple de deux ans) et est notamment conseillé pour débuter."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
				<span class="comparer" id="comparer_defensif"><a onclick="comparer('defensif')" style="cursor: pointer; cursor: hand;">Se comparer</a> <a data-toggle="tooltip" title="Vous pouvez comparer la composition de votre portefeuille à celle des portefeuilles défensifs des autres membres (du point de vue du fonds euro, des actifs, des régions et des SRRI)" data-container="body"><img src="images/info.png" class="rounded-circle" width="15em"></a></span>
            </div>
			<?php
			if (((($message != "") and ($_POST['action'] == 'repartition')) or ($message_portefeuille != "")) and ($_POST['portefeuille'] == 'defensif'))
			{
				echo '<div class="card-body pb-0">
				<div class="bg-'.$message_type.' mb-0 p-2">'.$message.$message_portefeuille.'</div>
				</div>';
			}
			
			afficher_portefeuille($bdd, $id, 'defensif', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille réactif&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille réactif doit permettre un rendement sensiblement supérieur au portefeuille défensif en présentant un risque de perte en capital modéré à court terme. Il est constitué de fonds et de trackers suivant les marchés à la hausse comme à la baisse, avec une part en fonds euros plus réduite que dans le portefeuille défensif. Il correspond à un placement pour le moyen-terme (d’au moins cinq ans)."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
				<span class="comparer" id="comparer_reactif"><a onclick="comparer('reactif')" style="cursor: pointer; cursor: hand;">Se comparer</a> <a data-toggle="tooltip" title="Vous pouvez comparer la composition de votre portefeuille à celle des portefeuilles réactifs des autres membres (du point de vue du fonds euro, des actifs, des régions, des SRRI)" data-container="body"><img src="images/info.png" class="rounded-circle" width="15em"></a></span>
            </div>
			<?php
			if (((($message != "") and ($_POST['action'] == 'repartition')) or ($message_portefeuille != "")) and ($_POST['portefeuille'] == 'reactif'))
			{
				echo '<div class="card-body pb-0">
				<div class="bg-'.$message_type.' mb-0 p-2">'.$message.$message_portefeuille.'</div>
				</div>';
			}
			
			afficher_portefeuille($bdd, $id, 'reactif', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Portefeuille dynamique&nbsp;
              <a data-toggle="tooltip" title="Le portefeuille dynamique doit permettre un rendement supérieur au marché en contrepartie d\'une plus grande volatilité, et donc d\'un risque important de perte en capital à court et moyen terme. Il est principalement constitué de fonds et de trackers reposant sur des actions dont une partie peut apparaitre comme fragile (entreprises de petite capitalisation, pays émergents, …). Il correspond à un placement pour le long-terme (d’au moins dix ans) et peut nécessiter une surveillance régulière."
                data-container="body">
                <img src="images/info.png" class="rounded-circle" width="15em"> </a>
				<span class="comparer" id="comparer_dynamique"><a onclick="comparer('dynamique')" style="cursor: pointer; cursor: hand;">Se comparer</a> <a data-toggle="tooltip" title="Vous pouvez comparer la composition de votre portefeuille à celle des portefeuilles dynamiques des autres membres (du point de vue du fonds euro, des actifs, des régions, des SRRI)" data-container="body"><img src="images/info.png" class="rounded-circle" width="15em"></a></span>
            </div>
			<?php
			if (((($message != "") and ($_POST['action'] == 'repartition')) or ($message_portefeuille != "")) and ($_POST['portefeuille'] == 'dynamique'))
			{
				echo '<div class="card-body pb-0">
				<div class="bg-'.$message_type.' mb-0 p-2">'.$message.$message_portefeuille.'</div>
				</div>';
			}
			
			afficher_portefeuille($bdd, $id, 'dynamique', $message_fonds_manquant, $pourcentage_fondseuro);
			?>
          </div>
			<?php
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
	if (isset($_SESSION['id']) and isset($_SESSION['nom']))
	{
  ?>
  <script type="text/javascript">
  	function somme_pourcentages(id_form)
	{
		var tab = document.querySelectorAll('#' + id_form + ' [name="pourcentage[]"]');
		var tot = 0;

		for(var i=0;i<tab.length;i++)
		{
			if(parseInt(tab[i].value))
				tot += parseInt(tab[i].value);
		}

		document.querySelector('#' + id_form + ' [name="somme"]').value = tot;
		if(tot == 100 || tot == 0)
		{
			document.querySelector('#' + id_form + ' [name="somme"]').className  = "form-control form-control-sm";
		}
		else
		{
			document.querySelector('#' + id_form + ' [name="somme"]').className  = "form-control form-control-sm form_incorrect";
		}		
	}
	function repartition_egale(id_form)
	{
		var tab = document.querySelectorAll('#' + id_form + ' [name="pourcentage[]"]');
		var frac = ~~(100 / tab.length);
		var rest = 100 % tab.length;
		
		for(var i=0;i<tab.length;i++)
		{
			if(i+1<=rest)
			{
				var val = frac + 1;
			}
			else
			{
				var val = frac;
			}
			document.querySelectorAll('#' + id_form + ' [name="pourcentage[]"]')[i].value = val;
		}
		
		document.querySelector('#' + id_form + ' [name="somme"]').value = 100;
		document.querySelector('#' + id_form + ' [name="somme"]').className  = "form-control form-control-sm";
		document.getElementById(id_form + '_avert').style.display = 'inline';
	}
	function disable_text(status)
	{
		document.getElementById("form_fondseuro").pourcentage_fondseuro.disabled = status;
	}
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
		
		var portefeuilles = ["defensif", "reactif", "dynamique"];
		var indices_existants = [];
		for (var i = 0; i < portefeuilles.length; i++)
		{
			if (document.getElementById("recommandations_"+portefeuilles[i]))
			{
				indices_existants.push(i);
			}
		}
		
		$.ajax({
			data: "id=<?php echo $id ?>&port="+indices_existants.join("|"),
			type: "GET",
			url: "reco.py",
			success: function(response) {
				recommandations = JSON.parse(response);
				for (var i = 0; i < indices_existants.length; i++)
				{
					$("#recommandations_"+portefeuilles[indices_existants[i]]).html(recommandations[i]);
				}
				$('[data-toggle="popover"]').popover();
			}
		});
    });
	function comparer(portefeuille)
	{		
		$("#comparer_"+portefeuille).html("Chargement...");
		$.ajax({
			data: "id=<?php echo $id ?>&port="+portefeuille,
			type: "GET",
			url: "predict.py",
			success: function(response) {
				$("#comparer_"+portefeuille).html(response);
				$('[data-toggle="tooltip"]').tooltip();
			}
		});
		
	}
  </script>
  <?php
	}
  ?>
</body>

</html>