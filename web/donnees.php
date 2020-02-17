<?php
Header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>
<donnees>
';

$connexion = false;

if ( (isset($_GET['nom']) and $_GET['nom']!='') and (isset($_GET['mdp']) and $_GET['mdp']!='') )
{
	if (strlen($_GET['nom'])<=15 and preg_match("#^[\w\.]+$#", $_GET['nom']))
	{
		try
		{
			$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
		}
		catch(Exception $e)
		{
			die('Erreur : '.$e->getMessage());
		}
		
		$req = $bdd->prepare('SELECT id, mdp FROM utilisateurs WHERE nom = :nom');
		$req->execute(array('nom' => $_GET['nom']));
		$resultat = $req->fetch();
		
		if ($resultat)
		{
			if (password_verify($_GET['mdp'], $resultat['mdp']))
			{
				$connexion = true;
			}
			else
			{
				echo "	<erreur>Mauvais identifiant ou mot de passe.</erreur>
";
			}
		}
		else
		{
			echo "	<erreur>Mauvais identifiant ou mot de passe.</erreur>
";
		}
	}
	else
	{
		echo "	<erreur>Mauvais identifiant ou mot de passe.</erreur>
";
	}
}
else
{
	echo "	<erreur>Un nom d'utilisateur et un mot de passe doivent être renseignés.</erreur>
";
}

$recuperation = false;

if ($connexion)
{
	if (isset($_GET['isin']) and !empty($_GET['isin']))
	{
		$isin_pregmatch = true;
		
		foreach ($_GET['isin'] as $value)
		{
			if (!preg_match("#^[A-Z0-9]{12}$#", $value))
			{
				$isin_pregmatch = false;
			}
		}
		
		if ($isin_pregmatch)
		{
			$requete_tab = array();
			$requete_chaine = '';
							
			foreach ($_GET['isin'] as $key => $value)
			{
				$key_sql = 'isin'.$key;
				$requete_tab[$key_sql] = $value;
				$requete_chaine .= ':'.$key_sql.',';
			}
			
			$requete_chaine = rtrim($requete_chaine, ',');
			
			$requete_actifs = "SELECT isin, nom, (date_donnees IS NULL) as donnees_manquantes
			FROM unitesdecompte
			WHERE isin IN ($requete_chaine)";
			
			$req = $bdd->prepare($requete_actifs);
			$req->execute($requete_tab);
			$fonds_existant = array();
			$fonds_donnees_manquantes = array();
			
			while ($donnees = $req->fetch())
			{
				$tableau_noms[$donnees['isin']] = $donnees['nom'];
				
				if ($donnees['donnees_manquantes'] == 1)
				{
					$isin_donnees_manquantes[] = $donnees['isin'];
				}
			}
			
			$isin_inexistant = array_diff($_GET['isin'], array_keys($tableau_noms));
			
			if (empty($isin_inexistant))
			{
				if (empty($isin_donnees_manquantes))
				{
					$requete_actifs = "SELECT isin, pourcentage, nom_actifs_complet
					FROM
					(
					SELECT uc.isin, uc.nom, uc_actifs.pourcentage, actifs.nom AS nom_actifs_complet
					FROM unitesdecompte uc
					INNER JOIN bdd_unitesdecompte_actifs uc_actifs
					ON uc_actifs.id_unitedecompte = uc.id
					INNER JOIN bdd_actifs actifs
					ON actifs.id = uc_actifs.id_actif
					WHERE uc.isin IN ($requete_chaine)
					AND actifs.nom <> 'Actions'
					UNION ALL
					SELECT uc.isin, uc.nom, uc_actifs.pourcentage, CONCAT(actifs.nom, ((uc_actions.id_actions-1) DIV 3)) AS nom_actifs_complet
					FROM unitesdecompte uc
					INNER JOIN bdd_unitesdecompte_actifs uc_actifs
					ON uc_actifs.id_unitedecompte = uc.id
					INNER JOIN bdd_actifs actifs
					ON actifs.id = uc_actifs.id_actif
					INNER JOIN bdd_unitesdecompte_actions uc_actions
					ON uc_actions.id_unitedecompte = uc.id
					WHERE uc.isin IN ($requete_chaine)
					AND actifs.nom = 'Actions'
					)resultat";
					
					$req2 = $bdd->prepare($requete_actifs);
					$req2->execute($requete_tab);
					$tableau_actifs = array();
					// $tableau_noms_actifs = array();
					
					while ($donnees2 = $req2->fetch())
					{
						if ($donnees2['pourcentage'] != '0.00')
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
							
							$tableau_actifs[$donnees2['isin']][$nom_actif] = $donnees2['pourcentage'];
							
							// array_push($tableau_noms_actifs, $nom_actif);
							// $tableau_noms_actifs = array_unique($tableau_noms_actifs);
						}
					}
					
					$requete_regions = "SELECT uc.isin, uc_regions.pourcentage, regions.nom AS nom_regions_complet
					FROM unitesdecompte uc
					INNER JOIN bdd_unitesdecompte_regions uc_regions
					ON uc_regions.id_unitedecompte = uc.id
					INNER JOIN bdd_regions regions
					ON regions.id = uc_regions.id_region
					WHERE uc.isin IN ($requete_chaine)";
					
					$req3 = $bdd->prepare($requete_regions);
					$req3->execute($requete_tab);
					$tableau_regions = array();
					// $tableau_noms_regions = array();
					
					while ($donnees3 = $req3->fetch())
					{
						if ($donnees3['pourcentage'] != '0.00')
						{
							$tableau_regions[$donnees3['isin']][$donnees3['nom_regions_complet']] = $donnees3['pourcentage'];
							
							// array_push($tableau_noms_regions, $donnees3['nom_regions_complet']);
							// $tableau_noms_regions = array_unique($tableau_noms_regions);
						}
					}
					
					if (empty(array_diff(array_keys($tableau_actifs), array_keys($tableau_noms))) and empty(array_diff(array_keys($tableau_regions), array_keys($tableau_noms))))
					{
						if (isset($_GET['historique']) and $_GET['historique']=='1')
						{
							$requete_regions = "SELECT uc.isin, val.date_valeur, val.valeur
							FROM unitesdecompte uc
							INNER JOIN bdd_valeurs val
							ON val.id_unitedecompte = uc.id
							WHERE uc.isin IN ($requete_chaine)";
							
							$req4 = $bdd->prepare($requete_regions);
							$req4->execute($requete_tab);
							$tableau_valeurs = array();
							
							while ($donnees4 = $req4->fetch())
							{
								$tableau_valeurs[$donnees4['isin']][$donnees4['date_valeur']] = $donnees4['valeur'];
							}
						}
						
						$recuperation = true;
					}
					else
					{
						echo "	<erreur>Erreur lors de la récupération des informations.</erreur>
";
					}
				}
				else
				{
					echo "	<erreur>Les informations relatives à l'ISIN ou aux ISINs suivants ne sont pas encore disponibles, ces informations sont récupérées dans les 60 minutes qui suivent l'ajout d'un ISIN à la base de données  : ".implode(", ", $isin_donnees_manquantes)."</erreur>
";
				}
			}
			else
			{
				echo "	<erreur>Le ou les ISINs suivants n'ont pas encore été enregistrés dans la base de données par les membres : ".implode(", ", $isin_inexistant)."</erreur>
";
			}
		}
		else
		{
			echo "	<erreur>Le format des ISINs n'est pas correct.</erreur>
";
		}
	}
	else
	{
		echo "	<erreur>Un ou des ISINs doivent être renseignés.</erreur>
";
	}
}

if ($recuperation)
{
	$pourcentage_fondseuro = array();
	$req = $bdd->query("SELECT nom FROM bdd_actifs WHERE nom != 'Actions'");
	$tableau_noms_actifs = $req->fetchAll(PDO::FETCH_COLUMN);
	array_push($tableau_noms_actifs, 'Actions (petites)', 'Actions (moyennes)', 'Actions (grandes)', 'Actions (divers)');
	
	$req2 = $bdd->query('SELECT nom FROM bdd_regions');
	$tableau_noms_regions = $req2->fetchAll(PDO::FETCH_COLUMN);
	
	sort($tableau_noms_actifs);
	sort($tableau_noms_regions);
	
	foreach ($tableau_noms as $key => $value)
	{
		echo "	<fonds>
";
		echo "		<isin>".$key."</isin>
";
		echo "		<nom>".htmlspecialchars($value, ENT_XML1, 'UTF-8')."</nom>
";
		echo "		<actifs>
";
		foreach ($tableau_noms_actifs as $value2)
		{
			echo "			<actif>
";
			echo "				<nom>".htmlspecialchars($value2, ENT_XML1, 'UTF-8')."</nom>
";
			echo "				<pourcentage>";
			if (array_key_exists($value2, $tableau_actifs[$key]))
			{
				echo $tableau_actifs[$key][$value2];
			}
			else
			{
				echo '0.00';
			}
			echo "</pourcentage>
";
			echo "			</actif>
";
		}
		echo "		</actifs>
";
		echo "		<regions>
";
		foreach ($tableau_noms_regions as $value2)
		{
			echo "			<region>
";
			echo "				<nom>".htmlspecialchars($value2, ENT_XML1, 'UTF-8')."</nom>
";
			echo "				<pourcentage>";
			if (array_key_exists($value2, $tableau_regions[$key]))
			{
				echo $tableau_regions[$key][$value2];
			}
			else
			{
				echo '0.00';
			}
			echo "</pourcentage>
";
			echo "			</region>
";
		}
		echo "		</regions>
";
		if (isset($_GET['historique']) and $_GET['historique']=='1')
		{
			echo "		<historique>
";
			if (array_key_exists($key, $tableau_valeurs))
			{
				ksort($tableau_valeurs[$key]);
				foreach ($tableau_valeurs[$key] as $key2 => $value2)
				{
					echo "			<entree>
";
					echo "				<date>".$key2."</date>
";
					echo "				<valeur>".$value2."</valeur>
";
					echo "			</entree>
";
				}
			}
			echo "		</historique>
";
		}
		echo "	</fonds>
";
	}
}

echo '</donnees>';
?>