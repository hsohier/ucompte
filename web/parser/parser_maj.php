<?php
include('simple_html_dom.php');

try
{
	$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
}
catch(Exception $e)
{
	die('Erreur : '.$e->getMessage());
}

$req = $bdd->query("SELECT * FROM unitesdecompte 
WHERE date_donnees IS NOT NULL and UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_donnees)>2592000 
AND (chemin_morningstar LIKE '/%' OR chemin_morningstar LIKE 'http://www.morningstar%' ) 
ORDER BY UNIX_TIMESTAMP()-UNIX_TIMESTAMP(date_donnees) DESC 
LIMIT 2");
while ($donnees = $req->fetch())
{
	echo $donnees['isin'].' '.$donnees['nom'].' : '.$donnees['chemin_morningstar'].'<br />';

	if (mb_substr($donnees['chemin_morningstar'],0,1) == '/')
	{
		$chemin_complet = 'http://www.morningstar.fr'.$donnees['chemin_morningstar'];
	}
	else
	{
		$chemin_complet = $donnees['chemin_morningstar'];
	}
	
	if (preg_match("#morningstar.be#", $chemin_complet) and !preg_match("#&lang=fr-BE#i", $chemin_complet))
	{
		$chemin_complet = $chemin_complet.'&lang=fr-BE';
	}
	
	if (!preg_match("#&tab=3#", $chemin_complet))
	{
		$chemin_complet = $chemin_complet.'&tab=3';
	}

	if (preg_match("#id=([A-Z0-9]+)#", $chemin_complet, $matches))
	{
		$code_morningstar = $matches[1];

		$json = file_get_contents('http://tools.morningstar.fr/api/rest.svc/timeseries_price/ok91jeenoo?id='.$code_morningstar.']2]1]&currencyId=EUR&idtype=Morningstar&priceType=&frequency=monthly&startDate=1990-01-01&outputType=COMPACTJSON');
		$obj = json_decode($json);
		
		if (count($obj) > 0)
		{
			if (count($obj[0]) == 2)
			{
				echo count($obj).' valeurs<br />';
				
				$req2 = $bdd->prepare('SELECT MAX(date_valeur) FROM bdd_valeurs WHERE id_unitedecompte = :id_unitedecompte');
				$req2->execute(array('id_unitedecompte' => $donnees['id']));
				$derniere_date = $req2->fetchColumn();
				
				$req20 = $bdd->prepare('INSERT INTO bdd_valeurs(id_unitedecompte, date_valeur, valeur) VALUES(:id_unitedecompte, :date_valeur, :valeur)');
				foreach ($obj as $value)
				{
					if ($derniere_date == null or ($derniere_date != null and $value[0] > $derniere_date))
					{
						$req20->execute(array(
							'id_unitedecompte' => $donnees['id'],
							'date_valeur' => $value[0],
							'valeur' => $value[1]));
					}
				}
			}
		}
	}
		
	$html2 = file_get_html($chemin_complet);
	
	if($html2)
	{
		$ret2 = $html2->find('table[class=portfolioAssetAllocationTable] table[class=portfolioAssetAllocationTable] tr');
		
		if (count($ret2) > 1)
		{
			echo 'Actifs<br />';
			$ret2 = array_slice($ret2, 1);
			$tableau_actifs_maj = [];
			
			foreach($ret2 as $element)
			{
				$nom_actif = $element->find('td[class=label]', 0);
				$pourcentage_actif = $element->find('td[class=value number]', -1);
				
				if ($nom_actif != null and $pourcentage_actif != null and $pourcentage_actif != '0.00')
				{
					$nom_actif = $nom_actif->plaintext;
					
					$tableau_actifs_français = ['Actions', 'Obligations', 'Liquidités', 'Autres', 'Immobilier'];
					$tableau_actifs_anglais =  ['Stock',   'Bond',        'Cash',       'Other',  'Property'];
					if (in_array($nom_actif, $tableau_actifs_anglais))
					{
						$id_actif_anglais = array_search($nom_actif, $tableau_actifs_anglais);
						$nom_actif = $tableau_actifs_français[$id_actif_anglais];
					}
					
					$pourcentage_actif = $pourcentage_actif->plaintext;
					$pourcentage_actif = str_replace(',', '.', $pourcentage_actif);
				
					$req7 = $bdd->prepare('SELECT * FROM bdd_actifs WHERE nom = :nom');
					$req7->execute(array('nom' => $nom_actif));
					
					if($req7->rowCount())
					{
						$resultat_actifs = $req7->fetch();
					}
					else
					{
						$req8 = $bdd->prepare('INSERT INTO bdd_actifs(nom) VALUES(:nom)');
						$req8->execute(array('nom' => $nom_actif));
						
						$req9 = $bdd->prepare('SELECT * FROM bdd_actifs WHERE nom = :nom');
						$req9->execute(array('nom' => $nom_actif));
						$resultat_actifs = $req9->fetch();
					}
					
					$tableau_actifs_maj[$resultat_actifs['id']] = $pourcentage_actif;
					
					echo $nom_actif.' '.$pourcentage_actif.'<br />';
				}
			}
			
			if (array_sum($tableau_actifs_maj) > 90 and array_sum($tableau_actifs_maj) < 120)
			{
				$req3 = $bdd->prepare('DELETE FROM bdd_unitesdecompte_actifs WHERE id_unitedecompte=:id_unitedecompte');
				$req3->execute(array('id_unitedecompte' => $donnees['id']));
				
				foreach ($tableau_actifs_maj as $key => $value)
				{
					$req10 = $bdd->prepare('INSERT INTO bdd_unitesdecompte_actifs(id_unitedecompte, id_actif, pourcentage) VALUES(:id_unitedecompte, :id_actif, :pourcentage) 
					ON DUPLICATE KEY UPDATE pourcentage=:pourcentage');
					$req10->execute(array(
						'id_unitedecompte' => $donnees['id'],
						'id_actif' => $key,
						'pourcentage' => $value));
				}
				
				$ret3 = $html2->find('img[src*=stylebox]', 0);
			
				if ($ret3 != null)
				{
					$image_actions = $ret3->src;
					preg_match("#\d#", $image_actions, $matches);
					$id_actions = $matches[0];
					
					if ($id_actions != 0)
					{
						$req21 = $bdd->prepare('DELETE FROM bdd_unitesdecompte_actions WHERE id_unitedecompte=:id_unitedecompte');
						$req21->execute(array('id_unitedecompte' => $donnees['id']));
						
						$req6 = $bdd->prepare('INSERT INTO bdd_unitesdecompte_actions (id_unitedecompte, id_actions) VALUES(:id_unitedecompte, :id_actions)
						ON DUPLICATE KEY UPDATE id_actions=:id_actions');
						$req6->execute(array(
							'id_unitedecompte' => $donnees['id'],
							'id_actions' => $id_actions));
							
						echo 'Actions '.$id_actions.'<br />';
					}
				}
			}
		}
			
		$ret4 = $html2->find('table[class=portfolioRegionalBreakdownTable] table[class=portfolioRegionalBreakdownTable] tr');
		
		if (count($ret4) > 1)
		{
			echo 'Régions<br />';
			$ret4 = array_slice($ret4, 1);
			$tableau_regions_maj = [];
			
			foreach($ret4 as $element)
			{
				$nom_region = $element->find('td[class=label]', 0);
				$pourcentage_region = $element->find('td[class=value number]', 0);
				
				if ($nom_region != null and $pourcentage_region != null and $pourcentage_region != '0.00')
				{
					$nom_region = $nom_region->plaintext;
					
					$tableau_regions_français = ['Etats Unis',    'Asie - Émergente', 'Eurozone', 'Royaume Uni',    'Japon', 'Asie - Pays Développés', 'Europe - sauf Euro', 'Canada', 'Europe - Émergente', 'Amérique Latine', 'Afrique', 'Australasie', 'Moyen Orient'];
					$tableau_regions_anglais =  ['United States', 'Asia - Emerging',  'Eurozone', 'United Kingdom', 'Japan', 'Asia - Developed',       'Europe - ex Euro',   'Canada', 'Europe - Emerging',  'Latin America',   'Africa',  'Australasia', 'Middle East'];
					if (in_array($nom_region, $tableau_regions_anglais))
					{
						$id_region_anglais = array_search($nom_region, $tableau_regions_anglais);
						$nom_region = $tableau_regions_français[$id_region_anglais];
					}
					elseif ($nom_region == 'États-Unis')
					{
						$nom_region = 'Etats Unis';
					}
					
					$pourcentage_region = $pourcentage_region->plaintext;
					$pourcentage_region = str_replace(',', '.', $pourcentage_region);
				
					$req11 = $bdd->prepare('SELECT * FROM bdd_regions WHERE nom = :nom');
					$req11->execute(array('nom' => $nom_region));
					
					if($req11->rowCount())
					{
						$resultat_regions = $req11->fetch();
					}
					else
					{
						$req12 = $bdd->prepare('INSERT INTO bdd_regions(nom) VALUES(:nom)');
						$req12->execute(array('nom' => $nom_region));
						
						$req13 = $bdd->prepare('SELECT * FROM bdd_regions WHERE nom = :nom');
						$req13->execute(array('nom' => $nom_region));
						$resultat_regions = $req13->fetch();
					}
					
					$tableau_regions_maj[$resultat_regions['id']] = $pourcentage_region;
					
					echo $nom_region.' '.$pourcentage_region.'<br />';
				}
			}
			
			if (array_sum($tableau_regions_maj) > 90 and array_sum($tableau_regions_maj) < 120)
			{
				$req5 = $bdd->prepare('DELETE FROM bdd_unitesdecompte_regions WHERE id_unitedecompte=:id_unitedecompte');
				$req5->execute(array('id_unitedecompte' => $donnees['id']));
				
				foreach ($tableau_regions_maj as $key => $value)
				{
					$req14 = $bdd->prepare('INSERT INTO bdd_unitesdecompte_regions(id_unitedecompte, id_region, pourcentage) VALUES(:id_unitedecompte, :id_region, :pourcentage)');
					$req14->execute(array(
						'id_unitedecompte' => $donnees['id'],
						'id_region' => $key,
						'pourcentage' => $value));
				}
			}
		}
	}
	
	$req17 = $bdd->prepare('UPDATE unitesdecompte SET date_donnees = CURDATE() WHERE isin = :isin');
	$req17->execute(array('isin' => $donnees['isin']));
	echo 'FIN<br />';
}

?>