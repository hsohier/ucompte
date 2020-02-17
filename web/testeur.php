<?php
session_start();
setlocale(LC_TIME, "fr_FR");

include("fonctions/afficher_navbar.php");
include("fonctions/couleurs_graphiques.php");
include("fonctions/message_fonds_manquant.php");
include("fonctions/message_fonds_inexistant.php");
include("fonctions/remplacer_vide_zero.php");
include("fonctions/afficher_graphiques_actifs.php");
include("fonctions/afficher_graphiques_regions.php");
include("fonctions/scripts_google.php");

function test_fonds($bdd, $isin)
{
	$fonds_existant = array();
	$fonds_donnees_manquantes = array();
	
	$in_isin  = str_repeat('?,', count($isin) - 1) . '?';
	
	$req = $bdd->prepare("SELECT uc.isin, uc.nom, (uc.date_donnees IS NULL) as donnees_manquantes, def.pourcentage_defensif, rea.pourcentage_reactif, dyn.pourcentage_dynamique
	FROM unitesdecompte uc
	LEFT JOIN 
	( 
		SELECT utuc.id_unitedecompte, SUM((IFNULL(rec.nbr_recommandations,0)+1)*utuc.pourcentage)/SUM(IFNULL(rec.nbr_recommandations,0)+1) AS pourcentage_defensif
		FROM utilisateurs_unitesdecompte utuc
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = utuc.id_utilisateur
		WHERE id_portefeuille = '1'
		AND utuc.pourcentage > 0
		GROUP BY utuc.id_unitedecompte
	) def
	ON def.id_unitedecompte = uc.id
	LEFT JOIN 
	( 
		SELECT utuc.id_unitedecompte, SUM((IFNULL(rec.nbr_recommandations,0)+1)*utuc.pourcentage)/SUM(IFNULL(rec.nbr_recommandations,0)+1) AS pourcentage_reactif
		FROM utilisateurs_unitesdecompte utuc
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = utuc.id_utilisateur
		WHERE utuc.id_portefeuille = '2'
		AND utuc.pourcentage > 0
		GROUP BY utuc.id_unitedecompte
	) rea
	ON rea.id_unitedecompte = uc.id
	LEFT JOIN 
	( 
		SELECT utuc.id_unitedecompte, SUM((IFNULL(rec.nbr_recommandations,0)+1)*utuc.pourcentage)/SUM(IFNULL(rec.nbr_recommandations,0)+1) AS pourcentage_dynamique
		FROM utilisateurs_unitesdecompte utuc
		LEFT JOIN
		(
			SELECT id_recommandation, COUNT(*) AS nbr_recommandations
			FROM recommandations
			GROUP BY id_recommandation
		) rec
		ON rec.id_recommandation = utuc.id_utilisateur
		WHERE utuc.id_portefeuille = '3'
		AND utuc.pourcentage > 0
		GROUP BY utuc.id_unitedecompte
	) dyn
	ON dyn.id_unitedecompte = uc.id
	WHERE isin IN ($in_isin)");
	$req->execute($isin);
	
	while ($donnees = $req->fetch())
	{
		$fonds_existant[$donnees['isin']]['nom'] = $donnees['nom'];
		$fonds_existant[$donnees['isin']]['pourcentage_defensif'] = round($donnees['pourcentage_defensif']);
		$fonds_existant[$donnees['isin']]['pourcentage_reactif'] = round($donnees['pourcentage_reactif']);
		$fonds_existant[$donnees['isin']]['pourcentage_dynamique'] = round($donnees['pourcentage_dynamique']);
		
		if ($donnees['donnees_manquantes'] == 1)
		{
			$fonds_donnees_manquantes[] = $donnees['isin'];;
		}
	}
	
	return array($fonds_existant, $fonds_donnees_manquantes);
}

function valeurs_graphiques_actifs($bdd, $isin, $pourcentage)
{
	$in_isin  = str_repeat('?,', count($isin) - 1) . '?';
	
	$req = $bdd->prepare("SELECT isin, pourcentage, nom_actifs_complet, id_actifs_complet
	FROM
	(
		SELECT uc.isin, uc_actifs.pourcentage, actifs.nom AS nom_actifs_complet, actifs.id AS id_actifs_complet
		FROM unitesdecompte uc
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_unitedecompte = uc.id
		INNER JOIN bdd_actifs actifs
		ON actifs.id = uc_actifs.id_actif
		WHERE uc.isin IN ($in_isin)
		AND actifs.nom <> 'Actions'
		
		UNION ALL
		
		SELECT uc.isin, uc_actifs.pourcentage, CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3)) AS nom_actifs_complet, CONCAT(actifs.id, '-', ((uc_actions.id_actions-1) DIV 3)) AS id_actifs_complet
		FROM unitesdecompte uc
		INNER JOIN bdd_unitesdecompte_actifs uc_actifs
		ON uc_actifs.id_unitedecompte = uc.id
		INNER JOIN bdd_actifs actifs
		ON actifs.id = uc_actifs.id_actif
		INNER JOIN bdd_unitesdecompte_actions uc_actions
		ON uc_actions.id_unitedecompte = uc.id
		WHERE uc.isin IN ($in_isin)
		AND actifs.nom = 'Actions'
	)resultat");
	$req->execute(array_merge($isin, $isin));
		
	while ($donnees = $req->fetch())
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
			$nom_actif = 'Actions (divers)';
		}
		else
		{
			$nom_actif = $donnees['nom_actifs_complet'];
		}
		
		$actifs[$nom_actif][$donnees['isin']] = $donnees['pourcentage'];
		$id_actifs[$nom_actif] = $donnees['id_actifs_complet'];
	}
	
	foreach ($actifs as $key => $value)
	{
		foreach ($isin as $value2)
		{
			if (!array_key_exists($value2, $value))
			{
				$value[$value2] = 0;
			}
		}
		
		$max_tableau = round(max($value));
		$min_tableau = round(min($value));
		
		if ($max_tableau > 0)
		{
			$actifs_max[$key] = $max_tableau;
			$actifs_min[$key] = $min_tableau;
		}
	}
	arsort($actifs_max);
	
	foreach ($actifs as $key => $value)
	{
		foreach ($value as $key2 => $value2)
		{
			$actifs_avec_poids[$key][$key2] = $pourcentage[$key2]/100*$value2;
		}
	}
	foreach ($actifs_avec_poids as $key => $value)
	{
		$actifs_moyennes[$key] = round(array_sum($value));
	}
	arsort($actifs_moyennes);
	
	return array($actifs_moyennes, $actifs_max, $actifs_min, $id_actifs);
}
function valeurs_graphiques_regions($bdd, $isin, $pourcentage)
{
	$in_isin  = str_repeat('?,', count($isin) - 1) . '?';
	
	$req2 = $bdd->prepare("SELECT uc.isin, uc_regions.pourcentage, regions.nom AS nom_regions_complet, regions.id AS id_regions_complet
	FROM unitesdecompte uc
	INNER JOIN bdd_unitesdecompte_regions uc_regions
	ON uc_regions.id_unitedecompte = uc.id
	INNER JOIN bdd_regions regions
	ON regions.id = uc_regions.id_region
	WHERE uc.isin IN ($in_isin)");
	$req2->execute($isin);
		
	while ($donnees2 = $req2->fetch())
	{
		$regions[$donnees2['nom_regions_complet']][$donnees2['isin']] = $donnees2['pourcentage'];
		$id_regions[$donnees2['nom_regions_complet']] = $donnees2['id_regions_complet'];
	}
	
	foreach ($regions as $key => $value)
	{
		foreach ($isin as $value2)
		{
			if (!array_key_exists($value2, $value))
			{
				$value[$value2] = 0;
			}
		}
		
		$max_tableau = round(max($value));
		$min_tableau = round(min($value));
		
		if ($max_tableau > 0)
		{
			$regions_max[$key] = $max_tableau;
			$regions_min[$key] = $min_tableau;
		}
	}
	arsort($regions_max);
	
	foreach ($regions as $key => $value)
	{
		foreach ($value as $key2 => $value2)
		{
			$regions_avec_poids[$key][$key2] = $pourcentage[$key2]/100*$value2;
		}
	}
	foreach ($regions_avec_poids as $key => $value)
	{
		$regions_moyennes[$key] = round(array_sum($value));
	}
	arsort($regions_moyennes);
	
	return array($regions_moyennes, $regions_max, $regions_min, $id_regions);
}

function afficher_tableau_optim($act_reg_max, $act_reg_min, $id_act_reg, $nom, $nom_simple, $abrev)
{
	echo '<div class="form-check">
		<input class="form-check-input" name="'.$abrev.'" id="'.$abrev.'_check" type="checkbox">
		<label class="form-check-label" for="'.$abrev.'_check"><strong>Une certaine répartition des '.$nom.'</strong></label>
		</div>
	<table class="table table-striped table-hover table-shrink mb-0">
	<thead>
	  <tr>
		<th></th>
		<th></th>
		<th colspan="2" class="text-center">Valeurs possibles</th>
	  </tr>
	  <tr>
		<th style="width: 50%" class="cell-first text-capitalize">'.$nom.'</th>
		<th style="width: 20%">Part</th>
		<th style="width: 15%" class="text-center">Min</th>
		<th style="width: 15%" class="text-center">Max</th>
	  </tr>
	</thead>
	<tbody>';
	
	foreach ($act_reg_max as $key => $value)
	{
		if($key != '? indéterminé')
		{
			echo '<tr>
				<td class="cell-first"><label for="'.$abrev.'_val_'.$id_act_reg[$key].'" class="col-form-label py-0">'.$key.'</label></td>
				<td><div class="col-8 p-0"><div class="input-group"><input type="text" name="'.$abrev.'_val[]" id="'.$abrev.'_val_'.$id_act_reg[$key].'" autocomplete="off" maxlength="3" onkeyup="this.value=this.value.replace(/[^\d]+/,\'\'); somme_pourcentages(\'optim\',\''.$abrev.'\')" class="form-control form-control-sm"><input type="hidden" name="'.$abrev.'_id[]" value="'.$id_act_reg[$key].'"><span class="input-group-addon">%</span></div></div></td>
				<td class="text-center">'.$act_reg_min[$key].'%</td>
				<td class="text-center">'.$value.'%</td>
			</tr>';
		}
	}
	
	echo'<tr>
		<td class="cell-first"><label for="somme_'.$abrev.'" class="col-form-label py-0">Somme*</label></td>
		<td><div class="col-8 p-0"><div class="input-group"><input type="text" id="somme_'.$abrev.'" maxlength="3" class="form-control form-control-sm" disabled><span class="input-group-addon">%</span></div></div></td>
		<td></td>
		<td></td>
	</tr>
	</tbody>
	</table>';
}

function verifier_tableau_uc($pct)
{
	$message = '';
	
	$pourcentage = $pct;
	$isin = array_keys($pct);
	
	$isin_pregmatch = 1;
	foreach ($isin as $value)
	{
		if (!preg_match("#^[A-Z0-9]{12}$#", $value))
		{
			$isin_pregmatch = 0;
		}
	}
	
	if ($isin_pregmatch)
	{
		$verif_entiers = ($pourcentage == array_filter($pourcentage, 'ctype_digit'));
		
		if (!$verif_entiers)
		{
			$message = 'Les pourcentages doivent être des entiers.';					
		}
	}
	else
	{
		$message = 'Le format des ISINs n\'est pas correct.';
	}
	
	return $message;
}

function historique_valeurs($bdd, $isin)
{
	$in_isin  = str_repeat('?,', count($isin) - 1) . '?';
	
	$req = $bdd->prepare("SELECT uc.isin, val.date_valeur, val.valeur
	FROM unitesdecompte uc
	INNER JOIN bdd_valeurs val
	ON val.id_unitedecompte = uc.id
	WHERE uc.isin IN ($in_isin)
	AND val.date_valeur >= (
        SELECT MIN(bdd_valeurs.date_valeur) AS date_min
        FROM bdd_valeurs
        INNER JOIN unitesdecompte
        ON unitesdecompte.id = bdd_valeurs.id_unitedecompte
        WHERE unitesdecompte.isin IN ($in_isin)
        GROUP BY bdd_valeurs.id_unitedecompte
        ORDER BY date_min DESC
        LIMIT 1
    )
	AND val.date_valeur <= (
        SELECT MAX(bdd_valeurs.date_valeur) AS date_max
        FROM bdd_valeurs
        INNER JOIN unitesdecompte
        ON unitesdecompte.id = bdd_valeurs.id_unitedecompte
        WHERE unitesdecompte.isin IN ($in_isin)
        GROUP BY bdd_valeurs.id_unitedecompte
        ORDER BY date_max ASC
        LIMIT 1
    )
	ORDER BY val.date_valeur ASC");
	$req->execute(array_merge($isin, $isin, $isin));
	
	$test_isin = array();
	$valeurs_temp = array();
	while ($donnees = $req->fetch())
	{
		$valeurs_temp[$donnees['date_valeur']][$donnees['isin']] = $donnees['valeur'];
		if (!in_array($donnees['isin'], $test_isin))
		{
			$test_isin[] = $donnees['isin'];
		}
	}
	return array($valeurs_temp, $test_isin);
}

function message_valeurs_manquantes($diff_isin)
{
	$n = count($diff_isin);
	$message_valeurs_manquantes =  'L\'historique des valeurs ';
	
	if ($n == 1)
	{
		$message_valeurs_manquantes .=  'de l\'ISIN ';
	}
	else
	{
		$message_valeurs_manquantes .= 'des ISINs ';
	}
	
	$i = 1;
	foreach ($diff_isin as $key => $value)
	{
		$message_valeurs_manquantes .= $value;
		if ($i != $n)
		{
			$message_valeurs_manquantes .= ', ';
		}
		else
		{
			$message_valeurs_manquantes .= ' ';
		}
		$i++;
	}
	
	$message_valeurs_manquantes .= 'n\'est pas disponible.';
	
	return $message_valeurs_manquantes;
}

function traitement_valeurs($valeurs_temp, $date_valeurs, $isin)
{
	$i = 0;
	while ($i <= (count($date_valeurs)-1))
	{
		foreach ($isin as $value)
		{
			if (!array_key_exists($value, $valeurs_temp[$date_valeurs[$i]]))
			{
				if (($i-1) >= 0)
				{
					$valeurs_temp[$date_valeurs[$i]][$value] = $valeurs_temp[$date_valeurs[$i-1]][$value];
				}
				else
				{
					$j = 1;
					while (!array_key_exists($value, $valeurs_temp[$date_valeurs[$i+$j]]))
					{
						$j++;
					}
					$valeurs_temp[$date_valeurs[$i]][$value] = $valeurs_temp[$date_valeurs[$i+$j]][$value];
				}
			}
		}
		$i++;
	}
	
	foreach ($isin as $value)
	{
		foreach ($date_valeurs as $value2)
		{
			$valeurs[$value][] = $valeurs_temp[$value2][$value];
		}
	}
	
	return $valeurs;
}

function analyse_courbe($valeurs, $isin, $pourcentage, $i_debut, $n_fenetre)
{
	$i = $i_debut;
	$n_complet = count($valeurs[$isin[0]]);
	
	while ($i <= ($n_complet-1))
	{
		$temp = 0;
		foreach ($valeurs as $key => $value)
		{
			$temp += $pourcentage[$key]*$value[$i]/$value[$i_debut];
		}
		$valeurs_finales[] = $temp;
		$i++;
	}
	
	$i = 0;
	$perf_min = round(100*($valeurs_finales[$i+$n_fenetre]-$valeurs_finales[$i])/$valeurs_finales[$i],2);
	$indice_min = $i;

	$perf_max = $perf_min;
	$indice_max = $indice_min;

	$i = 1;
	$n_court = count($valeurs_finales);
	
	while ($i <= $n_court-1-$n_fenetre)
	{
		$perf_courant = round(100*($valeurs_finales[$i+$n_fenetre]-$valeurs_finales[$i])/$valeurs_finales[$i],2);
		if ($perf_courant <= $perf_min)
		{
			$perf_min = $perf_courant;
			$indice_min = $i;
		}
		if ($perf_courant >= $perf_max)
		{
			$perf_max = $perf_courant;
			$indice_max = $i;
		}
		$i++;
	}
	
	return array($valeurs_finales, $perf_min, $indice_min+$i_debut, $perf_max, $indice_max+$i_debut);
}

function analyse_point_entree($valeurs, $isin, $pourcentage, $n_fenetre)
{
	$n_complet = count($valeurs[$isin[0]]);
	
	$i_debut = 0;
	$resultat_analyse_courbe = analyse_courbe($valeurs, $isin, $pourcentage, $i_debut, $n_fenetre);
	
	$valeurs_finales_min = $resultat_analyse_courbe[0];
	$perf_min = $resultat_analyse_courbe[1];
	$indice_min = $resultat_analyse_courbe[2];
	$indice_debut_min = $i_debut;
	
	$valeurs_finales_max = $resultat_analyse_courbe[0];
	$perf_max = $resultat_analyse_courbe[3];
	$indice_max = $resultat_analyse_courbe[4];
	$indice_debut_max = $i_debut;
	
	$i_debut = 1;
	while ($i_debut <= $n_complet-1-$n_fenetre)
	{
		$resultat_analyse_courbe = analyse_courbe($valeurs, $isin, $pourcentage, $i_debut, $n_fenetre);
		if ($resultat_analyse_courbe[1] <= $perf_min)
		{
			$valeurs_finales_min = $resultat_analyse_courbe[0];
			$perf_min = $resultat_analyse_courbe[1];
			$indice_min = $resultat_analyse_courbe[2];
			$indice_debut_min = $i_debut;
		}
		if ($resultat_analyse_courbe[3] >= $perf_max)
		{
			$valeurs_finales_max = $resultat_analyse_courbe[0];
			$perf_max = $resultat_analyse_courbe[3];
			$indice_max = $resultat_analyse_courbe[4];
			$indice_debut_max = $i_debut;
		}
		$i_debut++;
	}
	
	return array($valeurs_finales_min, round($perf_min), $indice_min, $indice_debut_min, $valeurs_finales_max, round($perf_max), $indice_max, $indice_debut_max);	
}

function afficher_courbe($min_max, $indice_origine, $valeurs_finales, $date_valeurs, $indice_debut, $indice_min_max, $perf, $n_fenetre)
{
	$n = count($date_valeurs);
	
	echo '
      google.charts.setOnLoadCallback(drawChart_courbe_'.$min_max.');

      function drawChart_courbe_'.$min_max.'() {
		var data = new google.visualization.DataTable();
			data.addColumn(\'date\', \'Mois\');
			data.addColumn(\'number\', "Courbe");
			data.addColumn(\'number\', "Intervale '.$min_max.'");
			data.addRows([';

	$i = $indice_origine;
	
	while ($i <= ($n-1))
	{
		echo '[new Date('.date("Y", round($date_valeurs[$i]/1000)).', '.(date("n", round($date_valeurs[$i]/1000))-1).'), ';
		
		if ($i >= $indice_debut and ($i <= $indice_min_max or $i >= $indice_min_max+$n_fenetre))
		{
			echo $valeurs_finales[$i-$indice_debut].', ';
		}
		else
		{
			echo 'null, ';
		}
		
		if ($i >= $indice_debut and $i >= $indice_min_max and $i <= $indice_min_max+$n_fenetre)
		{
			echo $valeurs_finales[$i-$indice_debut];
		}
		else
		{
			echo 'null';
		}

		echo ']';

		if ($i != $n)
		{
			echo ',';
		}
		echo '
		';
		
		$i++;
	}
		
	echo ']);

        var options = {
          title: \'Performance ';
	if ($min_max == 'min')
	{
		echo 'minimale';
	}
	elseif ($min_max == 'max')
	{
		echo 'maximale';
	}
	echo ' sur 1 an : ';
	if ($perf > 0)
	{
		echo '+';
	}
	echo $perf.'% entre '.utf8_encode(strftime("%B %Y", round($date_valeurs[$indice_min_max]/1000))).' et '.utf8_encode(strftime("%B %Y", round($date_valeurs[$indice_min_max+$n_fenetre]/1000))).' avec un investissement commencé en '.utf8_encode(strftime("%B %Y", round($date_valeurs[$indice_debut]/1000))).'\',
		  colors: [\'#cccccc\',\'';
	if ($min_max == 'min')
	{
		echo 'red';
	}
	elseif ($min_max == 'max')
	{
		echo 'green';
	}
		
	echo '\'],
		  legend: \'none\',
		  enableInteractivity: false,
		  hAxis: {
			minValue: new Date('.date("Y", round($date_valeurs[$indice_origine]/1000)).', '.(date("n", round($date_valeurs[$indice_origine]/1000))-1).')
		  }
        };

        var chart = new google.visualization.LineChart(document.getElementById(\'curve_chart_'.$min_max.'\'));

        chart.draw(data, options);
      }';
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Testeur et optimiseur</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
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
	$message_type = "success";
	$message_fonds_manquant = array();
	$portefeuille = 'test';
	$message_header = '';
	$message_valeurs_manquantes = '';
	
	$pourcentage = array();
	$isin = array();
	
	$actifs_moyennes = array();
	$id_actifs = array();
	$regions_moyennes = array();
	$id_regions = array();
	
	if (!isset($_GET['effX']))
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
			{
				require('autoload.php');
				$secret = "*****";
				$recaptcha = new \ReCaptcha\ReCaptcha($secret);
				$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
				
				if ($resp->isSuccess())
				{
					if (preg_match("#^[A-Z0-9]{12}$#", $_POST['isin']))
					{
						if(strlen($_POST['nom']) <= 100)
						{
							if (isset($_POST['pct']))
							{
								$pourcentage = $_POST['pct'];
								array_walk_recursive($pourcentage, 'remplacer_vide_zero');
								$isin = array_keys($pourcentage);
								$message = verifier_tableau_uc($pourcentage);
								$message_type = "danger";
							}
							
							if($message == '')
							{
								$req = $bdd->prepare('SELECT * FROM unitesdecompte
								WHERE isin = :isin');
								$req->execute(array('isin' => $_POST['isin']));
								
								if(!($req->rowCount()))
								{
									$req2 = $bdd->prepare('INSERT INTO unitesdecompte(isin, nom) VALUES(:isin, :nom)');
									$req2->execute(array(
										'isin' => $_POST['isin'],
										'nom' => $_POST['nom']));
									
									if (empty($pourcentage))
									{
										$pourcentage[$_POST['isin']] = 100;
									}
									else
									{
										$pourcentage[$_POST['isin']] = 0;
									}
									$isin[] = $_POST['isin'];
									
									$message = 'L\'ISIN '.htmlentities($_POST['isin']).' a bien été ajouté.';
									$message_type = "success";
								}
								else
								{
									$message = 'L\'ISIN est déjà enregistré dans la base de données.';
									$message_type = "danger";
								}
							}
							else
							{
								$pourcentage = array();
								$isin = array();
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
						$message = 'Le format de l\'ISIN n\'est pas correct.';
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
			else
			{
				$message = 'Vous devez être connecté pour enregistrer un ISIN dans la base de données.';
				$message_type = "danger";
			}
		}
		else
		{
			if (isset($_GET['pct']))
			{
				$pourcentage = $_GET['pct'];
				if (isset($_GET['eff']))
				{
					foreach ($_GET['eff'] as $key => $value)
					{
						unset($pourcentage[$key]);
					}
				}
				array_walk_recursive($pourcentage, 'remplacer_vide_zero');
				$isin = array_keys($pourcentage);
				$message = verifier_tableau_uc($pourcentage);
				$message_type = "danger";
			}
			
			if($message == '')
			{
				if (isset($_GET['ajt']) and $_GET['ajt'] != '')
				{
					if (preg_match("#^[A-Z0-9]{12}$#", $_GET['ajt']))
					{
						$req = $bdd->prepare('SELECT * FROM unitesdecompte
						WHERE isin = :isin');
						$req->execute(array('isin' => $_GET['ajt']));
						
						if(!($req->rowCount()))
						{
							if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
							{
								echo '<script src=\'https://www.google.com/recaptcha/api.js\'></script>
								<script type="text/javascript">
								  var onSubmit = function(response) {
									document.getElementById("form_ajout").submit();
								  };
								</script>';
								
								$message ='<p>Cet ISIN n\'est pas encore enregistré dans notre base de données, merci de lui associer un nom.<br />
								Préférez les formes courtes et synthétiques, en évitant le tout majuscule.<br />
								S\'il s\'agit d\'un ETF, commencez par "ETF | ".</p>
								<form action="testeur.php" method="post" id="form_ajout" class="form-inline mb-1">
								<label for="nom" class="mr-1">Nom :</label>
								<input type="text" class="form-control form-control-sm mr-1" id="nom" name="nom"  maxlength="100" required>
								<input type="hidden" name="isin" value="'.$_GET['ajt'].'">';
								
								if (isset($_GET['pct']))
								{
									foreach ($_GET['pct'] as $key => $value)
									{
										if(!isset($_GET['eff'][$key]))
										{
											$message .= '<input type="hidden" name="pct['.$key.']" value="'.$value.'">';
										}
									}
								}
									
								$message .= '<button
								class="g-recaptcha btn btn-light btn-sm"
								data-sitekey="6LfEGTkUAAAAABedVXbq1qALgELa8aZ4YjtSfbwK"
								data-callback="onSubmit">
								Ajouter
								</button>
								</form>';
								$message_type = "success";
							}
							else
							{
								$message = 'L\'ISIN '.htmlentities($_GET['ajt']).' n\'est pas enregistré dans notre base de données. Vous pouvez l\'enregistrer en vous connectant.';
								$message_type = "danger";
							}
						}
						else
						{
							if (empty($pourcentage))
							{
								$pourcentage[$_GET['ajt']] = 100;
							}
							else
							{
								$pourcentage[$_GET['ajt']] = 0;
							}
							$isin[] = $_GET['ajt'];
							$message = 'L\'ISIN '.htmlentities($_GET['ajt']).' a bien été ajouté.';
							$message_type = "success";
						}
					}
					else
					{
						$message = 'Le format de l\'ISIN n\'est pas correct.';
						$message_type = "danger";
					}
				}
			}
			else
			{
				$pourcentage = array();
				$isin = array();
			}
		}
		
		if (!empty($pourcentage) and !empty($isin))
		{
			$couleur_graphiques = couleurs_graphiques($bdd);
			$couleurs_actifs = $couleur_graphiques[0];
			$couleurs_regions = $couleur_graphiques[1];
			
			$resultat_test_fonds = test_fonds($bdd, $isin);
			$fonds_existant = $resultat_test_fonds[0];
			$fonds_donnees_manquantes = $resultat_test_fonds[1];
			
			$isin_fonds_existant = array_keys($fonds_existant);
			$fonds_inexistant = array_diff($isin, $isin_fonds_existant);
			
			if (!empty($fonds_inexistant))
			{
				$message = message_fonds_inexistant($fonds_inexistant);
				$message_type = "danger";
				$pourcentage = array();
				$isin = array();
			}
			else
			{
				if (!empty($fonds_donnees_manquantes))
				{
					$message_fonds_manquant[$portefeuille] = message_fonds_manquant($fonds_donnees_manquantes);
				}
				else
				{
					$valeurs_graphiques_actifs = valeurs_graphiques_actifs($bdd, $isin, $pourcentage);
					$actifs_moyennes = $valeurs_graphiques_actifs[0];
					$actifs_max = $valeurs_graphiques_actifs[1];
					$actifs_min = $valeurs_graphiques_actifs[2];
					$id_actifs = $valeurs_graphiques_actifs[3];
					
					$valeurs_graphiques_regions = valeurs_graphiques_regions($bdd, $isin, $pourcentage);
					$regions_moyennes = $valeurs_graphiques_regions[0];
					$regions_max = $valeurs_graphiques_regions[1];
					$regions_min = $valeurs_graphiques_regions[2];
					$id_regions = $valeurs_graphiques_regions[3];
					
					if (array_sum($pourcentage) == 100)
					{
						$resultat_historique_valeurs = historique_valeurs($bdd, $isin);
						$valeurs_temp = $resultat_historique_valeurs[0];
						$test_isin = $resultat_historique_valeurs[1];
						
						$diff_isin = array_diff($isin, $test_isin);
						$n_fenetre = 12;
						
						if (empty($diff_isin))
						{
							$date_valeurs = array_keys($valeurs_temp);
							sort($date_valeurs);
							
							if (count($date_valeurs) < ($n_fenetre+1))
							{
								$message_valeurs_manquantes = 'L\'intervalle de temps sur lequel les valeurs sont disponibles pour tous les ISINs est trop court.';
							}
						}
						else
						{
							$message_valeurs_manquantes = message_valeurs_manquantes($diff_isin);
						}
						
						echo '<!--Load the AJAX API-->
						<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
						<script type="text/javascript">

						  // Load the Visualization API and the corechart package.
						  google.charts.load(\'current\', {\'packages\':[\'corechart\']});';
						
						afficher_graphiques_actifs($portefeuille, $actifs_moyennes, $couleurs_actifs);
						afficher_graphiques_regions($portefeuille, $regions_moyennes, $couleurs_regions);
						
						if ($message_valeurs_manquantes == '')
						{
							$valeurs = traitement_valeurs($valeurs_temp, $date_valeurs, $isin);
							$resultat_analyse_point_entree = analyse_point_entree($valeurs, $isin, $pourcentage, $n_fenetre);

							$valeurs_finales_min = $resultat_analyse_point_entree[0];
							$perf_min = $resultat_analyse_point_entree[1];
							$indice_min = $resultat_analyse_point_entree[2];
							$indice_debut_min = $resultat_analyse_point_entree[3];

							$valeurs_finales_max = $resultat_analyse_point_entree[4];
							$perf_max = $resultat_analyse_point_entree[5];
							$indice_max = $resultat_analyse_point_entree[6];
							$indice_debut_max = $resultat_analyse_point_entree[7];
							
							$indice_origine = min([$indice_debut_min, $indice_debut_max]);
							
							afficher_courbe('min', $indice_origine, $valeurs_finales_min, $date_valeurs, $indice_debut_min, $indice_min, $perf_min, $n_fenetre);
							afficher_courbe('max', $indice_origine, $valeurs_finales_max, $date_valeurs, $indice_debut_max, $indice_max, $perf_max, $n_fenetre);
						}
						
						echo '</script>';
					}
				}
			}
		}
	}
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('testeur');
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Testeur et optimiseur</h3>
          <div class="card mb-3">
            <div class="card-header card-portefeuille">Testeur</div>
			<?php
			if ($message != "")
			{
				echo '<div class="card-body pb-0">
				<div class="bg-'.$message_type.' mb-0 p-2">'.$message.'</div>
				</div>';
			}

			echo '<div class="card-body';
			if (!empty($pourcentage) and !empty($isin))
			{
				echo ' pb-0';
			}
			echo '">
			<form action="testeur.php" method="get" id="formulaire">';
	
			if (!empty($pourcentage) and !empty($isin))
			{
				echo '<table class="table table-striped table-hover table-shrink mb-1">
				<thead>
				  <tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th colspan="3" class="text-center">Allocations moyennes des membres</th>
				  </tr>
				  <tr>
					<th style="width: 20%" class="cell-first">ISIN</th>
					<th style="width: 30%">Nom</th>
					<th style="width: 12%">Allocation</th>
					<th style="width: 8%">Retirer</th>
					<th style="width: 10%" class="text-center">Défensif</th>
					<th style="width: 10%" class="text-center">Réactif</th>
					<th style="width: 10%" class="text-center">Dynamique</th>
				  </tr>
				</thead>
				<tbody>';
				$somme = 0;
				arsort($pourcentage);
				foreach ($pourcentage as $key => $value)
				{
					echo '<tr>
					<td class="isin cell-first">'.$key.'</td>
					<td><label for="pourcentage_'.$key.'" class="col-form-label py-0">'.htmlentities($fonds_existant[$key]['nom']).'</label></td>
					<td><div class="col-8 p-0"><div class="input-group"><input type="text" name="pct['.$key.']" id="pourcentage_'.$key.'" autocomplete="off" value="'.$value.'" maxlength="3" onkeyup="this.value=this.value.replace(/[^\d]+/,\'\'); somme_pourcentages(\'formulaire\',\'pct\')" class="form-control form-control-sm"><span class="input-group-addon">%</span></div></div></td>
					<td class="cellule_retrait"><input name="eff['.$key.']" type="checkbox"></td>
					<td class="text-center">'.$fonds_existant[$key]['pourcentage_defensif'].'%</td>
					<td class="text-center">'.$fonds_existant[$key]['pourcentage_reactif'].'%</td>
					<td class="text-center">'.$fonds_existant[$key]['pourcentage_dynamique'].'%</td>
					</tr>';
					$somme += intval($pourcentage[$key]);
				}
				echo '
				<tr>
				<td class="cell-first"><label for="somme_pct" class="col-form-label py-0">Somme</label></td>
				<td></td>
				<td><div class="col-8 p-0"><div class="input-group"><input type="text" id="somme_pct" autocomplete="off" value="'.$somme.'" size="3" maxlength="3" class="form-control form-control-sm ';
				if ($somme != 100) {echo 'form_incorrect';}
				echo '" disabled><span class="input-group-addon">%</span></div></div></td>
				<td class="cellule_retrait"><input name="effX" type="checkbox"> Tout</td>
				<td colspan="3" class="cellule_retrait"> </td>
				</tr>
				<tr>
				<td></td>
				<td></td>
				<td>[<a onclick="repartition_egale(\'formulaire\')" style="cursor: pointer; cursor: hand;">Répartir</a>]</td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				</tr>
				</tbody>
				</table>
				';
			}
			
			echo '<div class="form-group row">
				<label for="ajt" class="col-2 col-form-label">Sélectionner un ISIN :</label>
				<div class="col-3">
				  <input type="text" class="form-control" id="ajt" name="ajt" maxlength="12" onkeyup="this.value=this.value.replace(/[^\dA-Z]+/,\'\')" placeholder="FR0123456789">
				</div>
			  </div>
			
			<button type="submit" class="btn btn-primary btn-sm">Mettre à jour</button>
			</form>
			</div>';
			
			if (!empty($pourcentage) and !empty($isin))
			{
				echo '<hr class="mb-0" />
				<div class="card-body">';
				if (array_key_exists($portefeuille, $message_fonds_manquant))
				{
					echo '<p>'.$message_fonds_manquant[$portefeuille].'</p>';
				}
				else
				{
					if ($somme == 100)
					{
						echo '<table class="mx-auto">
						  <tr>
							<td>
								<div id="chart_div_actifs_'.$portefeuille.'" style="border: 1px solid #ccc"></div>
							</td>
							<td>
								<div id="chart_div_regions_'.$portefeuille.'" style="border: 1px solid #ccc"></div>
							</td>
						  </tr>';
						if ($message_valeurs_manquantes == '')
						{
							echo '<tr>
								<td colspan="2">
									<div id="curve_chart_min"></div>
								</td>
							  </tr>
							  <tr>
								<td colspan="2">
									<div id="curve_chart_max"></div>
								</td>
							  </tr>';
						}
						echo '</table>';
						
						if ($message_valeurs_manquantes != '')
						{
							echo '<p class="mb-0 mt-2">'.$message_valeurs_manquantes.'</p>';
						}
					}
					else
					{
						echo '<p class="m-0">Les actifs, les régions et l\'historique des valeurs pourront être affichés lorsque le portefeuille sera finalisé (somme égale à 100%).</p>';
					}
				}
				echo '</div>';
			}
			?>
          </div>
          <div class="card mb-3">
            <div class="card-header card-portefeuille"> Optimiseur </div>
			<div class="card-body">
				<?php
				if (count($isin) > 1)
				{
					foreach ($actifs_max as $key => $value)
					{
						$actifs_diff[$key] = $value-$actifs_min[$key];
					}
					$verif_actifs = array_sum($actifs_diff);
					
					foreach ($regions_max as $key => $value)
					{
						$regions_diff[$key] = $value-$regions_min[$key];
					}
					$verif_regions = array_sum($regions_diff);
					
					if ($verif_actifs or $verif_regions)
					{
						echo '<p class="mb-3"><strong>Rechercher l\'allocation optimale permettant d\'obtenir :</strong></p>
						<form id="optim">
						<div class="row">';
						if ($verif_actifs)
						{
						echo '<div class="col-6 pr-2">';
							afficher_tableau_optim($actifs_max, $actifs_min, $id_actifs, 'actifs', 'actifs', 'act');
							echo '</div>';
						}
						if ($verif_regions)
						{
							echo '<div class="col-6'; if ($verif_actifs) {echo ' pl-2';} else {echo ' pr-2';} echo '">';
							afficher_tableau_optim($regions_max, $regions_min, $id_regions, 'régions', 'regions', 'reg');
							echo '</div>';
						}
						foreach ($isin as $value)
						{
							echo '<input type="hidden" name="isin[]" value="'.$value.'">';
						}
						echo '</div>
						<p class="m-0">* La somme doit être inférieure ou égale à 100</p>
						<button type="submit" class="btn btn-primary btn-sm mt-3">Rechercher</button>
						</form>
						<div id="chargement_optim" style="display: none" class="bg-success mt-3 p-2">
						Chargement...
						</div>
						<div id="solution_optim">
						</div>';
					}
					else
					{
						echo '<p class="m-0">Sélectionner au moins deux fonds dont les actifs et les régions ne sont pas indéterminés vous permet de faire une recherche d\'allocation optimale.</p>';
					}
				}
				else
				{
					echo '<p class="m-0">Sélectionner au moins deux fonds vous permet de faire une recherche d\'allocation optimale.</p>';
				}
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
	function somme_pourcentages(id_form, boite)
	{
		if ((boite=='act') || (boite=='reg'))
		{
			var val = '_val';
		}
		else if (boite=='pct')
		{
			var val = '';
		}
		
		var tab = document.querySelectorAll('#' + id_form + ' [name^="' + boite + val + '"]');
		
		var tot = 0;
		var tab_vide = true;
		for(var i=0;i<tab.length;i++)
		{
			if(parseInt(tab[i].value))
				tot += parseInt(tab[i].value);
			if(tab[i].value!='')
					tab_vide = false;
		}

		if ((boite=='act') || (boite=='reg'))
		{
			var boite_cocher = document.querySelectorAll('#optim [name="' + boite + '"]');
			
			if (tab_vide)
			{
				document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').value = '';
				document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').className  = "form-control form-control-sm";
				boite_cocher[0].checked = false;
			}
			else
			{
				document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').value = tot;
				
				if (tot <= 100)
				{
					document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').className  = "form-control form-control-sm";
					boite_cocher[0].checked = true;
				}
				else if (tot > 100)
				{
					document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').className  = "form-control form-control-sm form_incorrect";
					boite_cocher[0].checked = true;
				}
			}
		}
		else if (boite=='pct')
		{
			document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').value = tot;
			
			if (tot == 100)
			{
				document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').className  = "form-control form-control-sm";
			}
			else
			{
				document.querySelector('#' + id_form + ' [id="somme_' + boite + '"]').className  = "form-control form-control-sm form_incorrect";
			}
		}
	}
	function repartition_egale(id_form)
	{
		var tab = document.querySelectorAll('#' + id_form + ' [name^="pct"]');
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
			document.querySelectorAll('#' + id_form + ' [name^="pct"]')[i].value = val;
		}
		
		document.querySelector('#' + id_form + ' [id="somme_pct"]').value = 100;
		document.querySelector('#' + id_form + ' [id="somme_pct"]').className  = "form-control form-control-sm";
		document.getElementById('avert').style.display = 'inline';
	}
	$('#optim').submit(function() { // catch the form's submit event
		$('#solution_optim').hide();
		$('#chargement_optim').show();
		
		var isin = document.querySelectorAll('#optim [name="isin[]"]');
		isin = [].map.call(isin, function(obj) {
			return obj.value;
		});
		isin = isin.join('|');
		var message = 'isin='+isin
		
		var act = document.querySelectorAll('#optim [name="act"]');
		if(act.length)
		{
			if(act[0].checked)
			{
				act = '1'
			}
			else
			{
				act = '0'
			}
		}
		else
		{
			act = '0'
		}
		message += '&act='+act
		
		if(act)
		{
			var act_val = document.querySelectorAll('#optim [name="act_val[]"]');
			var act_id = document.querySelectorAll('#optim [name="act_id[]"]');
			var act_val_final = [];
			var act_id_final = [];
			
			act_val.forEach( 
			  function(currentValue, currentIndex, listObj) {
				  if(currentValue.value!='')
				  {
					  act_val_final.push(currentValue.value);
					  act_id_final.push(act_id[currentIndex].value); 
				  }
			});
			
			message += '&act_id='+act_id_final.join('|');
			message += '&act_val='+act_val_final.join('|');
		}
		
		
		var reg = document.querySelectorAll('#optim [name="reg"]');
		if(reg.length)
		{
			if(reg[0].checked)
			{
				reg = '1'
			}
			else
			{
				reg = '0'
			}
		}
		else
		{
			reg = '0'
		}
		message += '&reg='+reg
		
		if(reg)
		{
			var reg_val = document.querySelectorAll('#optim [name="reg_val[]"]');
			var reg_id = document.querySelectorAll('#optim [name="reg_id[]"]');
			var reg_val_final = [];
			var reg_id_final = [];
			
			reg_val.forEach( 
			  function(currentValue, currentIndex, listObj) {
				  if(currentValue.value!='')
				  {
					  reg_val_final.push(currentValue.value);
					  reg_id_final.push(reg_id[currentIndex].value); 
				  }
			});
			
			message += '&reg_id='+reg_id_final.join('|');
			message += '&reg_val='+reg_val_final.join('|');
		}
		
		$.ajax({ // create an AJAX call...
			data: message, // get the form data
			type: 'GET', // GET or POST
			url: 'optim.py', // the file to call
			success: function(response) { // on success..
				$('#solution_optim').html(response); // update the DIV
				$('#chargement_optim').hide();
				$('#solution_optim').show();
			}
		});
		return false; // cancel original event to prevent form submitting
	});
	</script>
</body>

</html>