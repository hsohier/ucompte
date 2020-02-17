<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/nom_actifs.php");
include("fonctions/nom_regions.php");
include("fonctions/scripts_google.php");
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Membres</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
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
          <h3 class="text-center">Membres</h3>
		  <?php
			try
			{
				$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
			}
			catch(Exception $e)
			{
				die('Erreur : '.$e->getMessage());
			}
			
			if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
			{
				$id = $_SESSION['id'];
			}
			
			if (isset($_GET['recommander']))
			{
				$get_recommander = urldecode($_GET['recommander']);
				
				if (!(isset($_SESSION['id']) AND isset($_SESSION['nom'])))
				{
					echo '<div class="bg-danger mb-3 p-2">Vous devez être connecté pour recommander un membre.</div>';
				}
				else
				{
					if ($get_recommander != "")
					{
						$req = $bdd->prepare('SELECT id FROM utilisateurs WHERE nom = :nom');
						$req->execute(array('nom' => $get_recommander));
						$id_recommandation = $req->fetchColumn();
						
						if ($id_recommandation)
						{
							if ($id_recommandation != $id)
							{
								$req2 = $bdd->prepare('SELECT * FROM recommandations 
								WHERE id_emetteur = :id_emetteur 
								AND id_recommandation = :id_recommandation');
								$req2->execute(array(
									'id_emetteur' => $id,
									'id_recommandation' => $id_recommandation));
								$resultat2 = $req2->fetch();
								
								if (!($resultat2))
								{
									$req3 = $bdd->prepare('INSERT INTO recommandations(id_emetteur, id_recommandation) VALUES(:id_emetteur, :id_recommandation)');
									$req3->execute(array(
										'id_emetteur' => $id,
										'id_recommandation' => $id_recommandation));
										
									echo '<div class="bg-success mb-3 p-2"><strong>Recommandation de membre : </strong>Votre recommendation de "'.htmlentities($get_recommander).'" a bien été prise en compte.</div>';
								}
								else
								{
									echo '<div class="bg-danger mb-3 p-2"><strong>Recommandation de membre : </strong>Vous ne pouvez pas recommander '.htmlentities($get_recommander).', vous le recommandez déjà.</div>';
								}
							}
							else
							{
								echo '<div class="bg-danger mb-3 p-2"><strong>Recommandation de membre : </strong>Vous ne pouvez pas vous recommander vous-même.</div>';
							}
						}
						else
						{
							echo '<div class="bg-danger mb-3 p-2"><strong>Recommandation de membre : </strong>Vous ne pouvez pas recommander '.htmlentities($get_recommander).', ce membre n\'est pas inscrit.</div>';
						}
					}
					else
					{
						echo '<div class="bg-danger mb-3 p-2"><strong>Recommandation de membre : </strong>Vous n\'avez pas renseigné de membre.</div>';
					}
				}
			}
		  ?>
		  <div class="card mb-3">
            <div class="card-header bg-light">Rechercher dans les portefeuilles</div>
            <div class="card-body pb-0">
			<h5>Rechercher les membres ayant choisi un certain fonds ou un certain tracker</h5>
			<form action="membres.php" method="get" class="form-inline">
			<label for="rech_isin" class="mr-1">ISIN du fonds ou tracker :</label>
			<input type="text" class="form-control form-control-sm mr-1" id="rech_isin" name="rech_isin" maxlength="12" onkeyup="this.value=this.value.replace(/[^\dA-Z]+/,\'\')" placeholder="FR0123456789">
			<label for="rech_nom" class="mr-1">ou partie de son nom :</label>
			<input type="text" class="form-control form-control-sm mr-1" id="rech_nom" name="rech_nom" maxlength="100">
			<button type="submit" class="btn btn-light btn-sm">Rechercher</button>
			</form>
			</div>
			<hr class="mb-0" />
			<div class="card-body">
			<h5 class="mb-0">Rechercher les membres ayant choisi certains actifs et certaines régions</h5>
			<p>Seuls les portefeuilles contenant l'ensemble des actifs et régions sélectionnés sont pris en compte.</p>
			<form action="membres.php" method="get">
			<div class="row">
			<div class="col-3">
			  <div class="form-group">
				<label for="rech_act">Actifs :</label>
				<select multiple class="form-control" id="rech_act" name="rech_act[]">
				<?php
				$nom_actifs = nom_actifs();
				foreach ($nom_actifs as $key => $value)
				{
					if ($value != '? indéterminé')
					{
						echo '<option value="'.$key.'">'.$value.'</option>';
					}
				}
				?>
				</select>
			  </div>
			</div>
			<div class="col-3">
			  <div class="form-group">
				<label for="rech_reg">Régions :</label>
				<select multiple class="form-control" id="rech_reg" name="rech_reg[]">
				<?php
				$nom_regions = nom_regions($bdd);
				foreach ($nom_regions as $key => $value)
				{
					if ($value != '? indéterminé')
					{
						echo '<option value="'.$key.'">'.$value.'</option>';
					}
				}
				?>
				</select>
			  </div>
			</div>
			</div>
			<button type="submit" class="btn btn-light btn-sm">Rechercher</button>
			
			</form>
			</div>
          </div>
          <div class="card mb-3">
			<?php	
            echo '<div class="card-header card-portefeuille">';
			if (!isset($_GET['rech_isin']) and !isset($_GET['rech_nom']) and !isset($_GET['rech_act']) and !isset($_GET['rech_reg']))
			{
				echo 'Liste des membres';
			}
			else
			{
				echo 'Résultats de la recherche dans les portefeuilles';
			}
			echo '</div>
            <div class="card-body">	';
			
			$message = '';
			$message_type = 'success';
			
			if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
			{
				$requete_tab = array('id_emetteur' => $id);
				$requete = 'SELECT DISTINCT ut.id, ut.nom, rec.nbr_recommandations, recfaite.recommandation_faite 
				FROM utilisateurs ut
				LEFT JOIN
				(
					SELECT id_recommandation, COUNT(*) AS nbr_recommandations
					FROM recommandations
					GROUP BY id_recommandation
				) rec
				ON rec.id_recommandation = ut.id
				LEFT JOIN
				(
					SELECT id_recommandation, COUNT(*) AS recommandation_faite
					FROM recommandations
					WHERE id_emetteur = :id_emetteur
					GROUP BY id_recommandation
				) recfaite
				on recfaite.id_recommandation = ut.id';
			}
			else
			{
				$requete_tab = array();
				$requete = 'SELECT DISTINCT ut.nom, rec.nbr_recommandations 
				FROM utilisateurs ut
				LEFT JOIN
				(
					SELECT id_recommandation, COUNT(*) AS nbr_recommandations
					FROM recommandations
					GROUP BY id_recommandation
				) rec
				ON rec.id_recommandation = ut.id';
			}
			
			if (isset($_GET['rech_isin']) and $_GET['rech_isin']!='')
			{
				if (preg_match("#^[A-Z0-9]{12}$#", $_GET['rech_isin']))
				{
					$requete_isin_tab = array('isin' => $_GET['rech_isin']);
					$requete_tab = array_merge($requete_tab, $requete_isin_tab);
					
					$requete .= '
					INNER JOIN
					utilisateurs_unitesdecompte utuc
					ON utuc.id_utilisateur = ut.id
					INNER JOIN
					unitesdecompte uc
					ON uc.id = utuc.id_unitedecompte
					WHERE uc.isin = :isin
					ORDER BY rec.nbr_recommandations DESC, ut.nom';
					
					$req = $bdd->prepare($requete);
					$req->execute($requete_tab);
					
					if($req->rowCount())
					{
						$message = 'Des membres ont dans un portefeuille l\'ISIN '.htmlentities($_GET['rech_isin']);
						$message_type = 'success';
					}
					else
					{
						$message = 'Aucun membre n\'a dans un portefeuille l\'ISIN '.htmlentities($_GET['rech_isin']);
						$message_type = 'danger';
					}
				}
				else
				{
					$message = 'Le format de l\'ISIN n\'est pas correct.';
					$message_type = 'danger';
				}
			}
			elseif (isset($_GET['rech_nom']) and $_GET['rech_nom']!='')
			{
				if(strlen($_GET['rech_nom']) <= 100)
				{
					$requete_nom_tab = array('nom' => '%'.$_GET['rech_nom'].'%');
					$requete_tab = array_merge($requete_tab, $requete_nom_tab);
					
					$requete .= '
					INNER JOIN
					utilisateurs_unitesdecompte utuc
					ON utuc.id_utilisateur = ut.id
					INNER JOIN
					unitesdecompte uc
					ON uc.id = utuc.id_unitedecompte
					WHERE uc.nom LIKE :nom 
					ORDER BY rec.nbr_recommandations DESC, ut.nom';
					
					$req = $bdd->prepare($requete);
					$req->execute($requete_tab);
					
					if($req->rowCount())
					{
						$message = 'Des membres ont dans un portefeuille un ISIN dont le nom contient "'.htmlentities($_GET['rech_nom']).'"';
						$message_type = 'success';
					}
					else
					{
						$message = 'Aucun membre n\'a dans un portefeuille un ISIN dont le nom contient "'.htmlentities($_GET['rech_nom']).'"';
						$message_type = 'danger';
					}
				}
				else
				{
					$message = 'Le nom est trop long.';
					$message_type = 'danger';
				}
			}
			elseif ((isset($_GET['rech_act']) and !empty($_GET['rech_act'])) or (isset($_GET['rech_reg']) and !empty($_GET['rech_reg'])))
			{
				$succes = 0;
				
				if (isset($_GET['rech_act']) and !empty($_GET['rech_act']))
				{
					if (array_intersect($_GET['rech_act'], array_keys($nom_actifs)) == $_GET['rech_act'])
					{
						$succes = 1;
					}
				}
				if (isset($_GET['rech_reg']) and !empty($_GET['rech_reg']))
				{
					if (array_intersect($_GET['rech_reg'], array_keys($nom_regions)) == $_GET['rech_reg'])
					{
						$succes = 1;
					}
					else
					{
						$succes = 0;
					}
				}
				
				if ($succes)
				{
					$message_act = '';
					$message_reg = '';
					
					if (isset($_GET['rech_act']) and !empty($_GET['rech_act']))
					{
						$requete_act_tab = array();
						$requete_act_chaine = '';
						
						if (count($_GET['rech_act']) == 1)
						{
							$message_act = '<br />- <strong>L\'actif</strong> :';
						}
						else
						{
							$message_act = '<br />- <strong>Les actifs</strong> :';
						}
						
						foreach ($_GET['rech_act'] as $key => $value)
						{
							$key_sql = 'rech_act'.$key;
							$requete_act_tab[$key_sql] = $value;
							$requete_act_chaine .= ':'.$key_sql.',';
							$message_act .= ' '.$nom_actifs[$value].',';
						}
						$requete_act_tab['nbr_actifs'] = count($requete_act_tab);
						$requete_tab = array_merge($requete_tab, $requete_act_tab);
						
						$requete_act_chaine = rtrim($requete_act_chaine, ',');
						$message_act = rtrim($message_act, ',');
						
						$requete .= "
						INNER JOIN
						(
							SELECT DISTINCT ut_actifs_count.id_utilisateur
							FROM
							(
								SELECT ut_actifs.id_utilisateur, ut_actifs.id_portefeuille, COUNT(DISTINCT ut_actifs.id_actif) AS nbr_actifs
								FROM
								(
									SELECT utuc.id_utilisateur, utuc.id_portefeuille, uc_actifs.id_unitedecompte, uc_actifs.id_actif
									FROM utilisateurs_unitesdecompte utuc
									INNER JOIN bdd_unitesdecompte_actifs uc_actifs
									ON uc_actifs.id_unitedecompte = utuc.id_unitedecompte
									INNER JOIN bdd_actifs actifs
									ON actifs.id = uc_actifs.id_actif
									WHERE actifs.nom <> 'Actions'
									AND utuc.pourcentage > 0
									AND uc_actifs.pourcentage > 0
									
									UNION ALL
									
									SELECT utuc.id_utilisateur, utuc.id_portefeuille, uc_actifs.id_unitedecompte, CONCAT(actifs.id, '-', ((uc_actions.id_actions-1) DIV 3)) AS id_actif
									FROM utilisateurs_unitesdecompte utuc
									INNER JOIN bdd_unitesdecompte_actifs uc_actifs
									ON uc_actifs.id_unitedecompte = utuc.id_unitedecompte
									INNER JOIN bdd_actifs actifs
									ON actifs.id = uc_actifs.id_actif
									INNER JOIN bdd_unitesdecompte_actions uc_actions
									ON uc_actions.id_unitedecompte = uc_actifs.id_unitedecompte
									WHERE actifs.nom = 'Actions'
									AND utuc.pourcentage > 0
									AND uc_actifs.pourcentage > 0
								) ut_actifs
								WHERE ut_actifs.id_actif IN ($requete_act_chaine)
								GROUP BY ut_actifs.id_utilisateur, ut_actifs.id_portefeuille
							) ut_actifs_count
							WHERE ut_actifs_count.nbr_actifs >= :nbr_actifs
						) ut_actifs_count_ok
						ON ut_actifs_count_ok.id_utilisateur = ut.id";
					}
					
					if (isset($_GET['rech_reg']) and !empty($_GET['rech_reg']))
					{
						$requete_reg_tab = array();
						$requete_reg_chaine = '';
						
						if (count($_GET['rech_reg']) == 1)
						{
							$message_reg = '<br />- <strong>La région</strong> :';
						}
						else
						{
							$message_reg = '<br />- <strong>Les régions</strong> :';
						}
						
						foreach ($_GET['rech_reg'] as $key => $value)
						{
							$key_sql = 'rech_reg'.$key;
							$requete_reg_tab[$key_sql] = $value;
							$requete_reg_chaine .= ':'.$key_sql.',';
							$message_reg .= ' '.$nom_regions[$value].',';
						}
						$requete_reg_tab['nbr_regions'] = count($requete_reg_tab);
						$requete_tab = array_merge($requete_tab, $requete_reg_tab);
						
						$requete_reg_chaine = rtrim($requete_reg_chaine, ',');
						$message_reg = rtrim($message_reg, ',');
						
						$requete .= "
						INNER JOIN
						(
							SELECT DISTINCT ut_regions_count.id_utilisateur
							FROM
							(
								SELECT utuc.id_utilisateur, utuc.id_portefeuille, COUNT(DISTINCT uc_regions.id_region) AS nbr_regions
								FROM utilisateurs_unitesdecompte utuc
								INNER JOIN bdd_unitesdecompte_regions uc_regions
								ON uc_regions.id_unitedecompte = utuc.id_unitedecompte
								WHERE utuc.pourcentage > 0
								AND uc_regions.pourcentage > 0
								AND uc_regions.id_region IN ($requete_reg_chaine)
								GROUP BY utuc.id_utilisateur, utuc.id_portefeuille
							) ut_regions_count
							WHERE ut_regions_count.nbr_regions >= :nbr_regions
						) nbr_regions_count_ok
						ON nbr_regions_count_ok.id_utilisateur = ut.id";
					}
					
					$requete .= '
					ORDER BY rec.nbr_recommandations DESC, ut.nom';
					
					$req = $bdd->prepare($requete);
					$req->execute($requete_tab);
					
					if($req->rowCount())
					{
						$message = 'Des membres ont un portefeuille contenant :'.$message_act.$message_reg;
						$message_type = 'success';
					}
					else
					{
						$message = 'Aucun membre n\'a un portefeuille contenant :'.$message_act.$message_reg;
						$message_type = 'danger';
					}
				}
				else
				{
					$message = 'Le format des actifs et des régions est incorrect.';
					$message_type = 'danger';
				}
			}
			else
			{
				$requete .= '
				ORDER BY rec.nbr_recommandations DESC, ut.nom';
				
				$req = $bdd->prepare($requete);
				$req->execute($requete_tab);
			}
			
			if ($message != '')
			{
				echo '<p class="bg-'.$message_type.' p-2';
				if ($message_type == 'danger')
				{
					echo ' mb-0';
				}
				echo '">'.$message.'</p>';
			}
			
			if ($message == '' or $message_type == 'success')
			{
				echo '<table class="table table-striped table-hover table-shrink mb-0">';
				while ($donnees = $req->fetch())
				{
					echo '<tr>
					<td class="cell-first"><a href ="membres/'.urlencode($donnees['nom']).'">'.$donnees['nom'].'</a>';
					if ($donnees['nbr_recommandations'] > 0)
					{
						echo " (".$donnees['nbr_recommandations']." reco.)";
					}
					echo '</td>
					<td>';
					if (isset($_SESSION['id']) AND isset($_SESSION['nom']) AND ($donnees['id'] != $_SESSION['id']))
					{
						if ($donnees['recommandation_faite'] == 0)
						{
							echo ' : <a href="membres.php?recommander='.urlencode($donnees['nom']).'">Recommander ce membre</a>';
						}
						else
						{
							echo ' : Vous recommandez ce membre';
						}
					}
					echo '</td>
					</tr>';
				}
				echo '</table>';
			}
			
			echo '</div>';
			?>
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
</body>

</html>