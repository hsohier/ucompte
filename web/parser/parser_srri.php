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

$req = $bdd->query("SELECT * FROM unitesdecompte uc
LEFT JOIN bdd_srri srri
ON srri.id_unitedecompte = uc.id
WHERE srri.id_unitedecompte IS NULL
LIMIT 5");

while ($donnees = $req->fetch())
{
	echo $donnees['isin'].'<br />';
	$adresse = 'https://www.opcvm360.com/recherche-globale?q='.$donnees['isin'].'&ok=';
	$html = file_get_html($adresse);
	
	if($html)
	{
		$ret = $html->find('table[class=table-srri] td[class=active]', 0);
		
		if ($ret != null)
		{
			$srri = $ret->plaintext;
		}
		else
		{
			$srri = 0;
		}
	}
	else
	{
		$srri = 0;
	}
	
	if ($srri == 0)
	{
		$adresse = 'https://www.quantalys.com/search/listefonds.aspx?autobind=1&autoredirect=1&ISINorNom='.$donnees['isin'].'&id_listeFCPE=1';
		$html = file_get_html($adresse);
	
		if($html)
		{
			$ret = $html->find('div[class=indic-srri-selected]', 0);
			
			if ($ret != null)
			{
				$srri = $ret->plaintext;
			}
			else
			{
				$srri = 0;
			}
		}
		else
		{
			$srri = 0;
		}
	}
	
	if ($srri == 0)
	{
		$adresse = '';
	}
	
	echo $srri.'<br /><br />';
	
	$req2 = $bdd->prepare('INSERT INTO bdd_srri(id_unitedecompte, srri, date, source) VALUES(:id_unitedecompte, :srri, CURDATE(), :source)');
	$req2->execute(array(
		'id_unitedecompte' => $donnees['id'],
		'srri' => $srri,
		'source' => $adresse));
}

?>