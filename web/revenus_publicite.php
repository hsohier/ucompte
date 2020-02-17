<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/scripts_google.php");
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vos revenus publicitaires</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
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
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if(!empty($_POST['paypal']))
			{
				$req = $bdd->prepare('INSERT INTO paypal_comptes(id_utilisateur, paypal) VALUES(:id_utilisateur, :paypal)');
				$req->execute(array(
					'id_utilisateur' => $id,
					'paypal' => $_POST['paypal']));
				
				$message_form = "Votre compte Paypal a bien été enregistré.";
				$message_type = "success";
			}
			else
			{
				$message_form = "Veuillez indiquer votre compte Paypal.";
				$message_type = "danger";
			}
		}
	}
	script_googleanalytics();
  ?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('revenus_publicite');
?>
  <div class="mb-2 text-justify">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Vos revenus publicitaires</h3>
			<?php
			if (!(isset($_SESSION['id']) and isset($_SESSION['nom'])))
			{
				echo '<p class="bg-danger p-2">Vous n\'êtes pas connecté.</p>';
			}
			else
			{
				echo '<div class="card mb-3">
					<div class="card-header bg-light">Compte Paypal</div>
					<div class="card-body">
						<p>Comme expliqué <a href="/publicite.php">sur cette page</a>, vous pouvez déclarer un compte Paypal sur lequel vous recevrez les revenus publicitaires générés par votre fiche publique (<a href="/membres/'.urlencode($_SESSION['nom']).'">voir votre fiche publique</a>).</p>';
				
				if ($_SERVER['REQUEST_METHOD'] == 'POST')
				{
					echo '<p class="bg-'.$message_type.' p-2">'.$message_form.'</p>';
				}
						
				$req = $bdd->prepare('SELECT * FROM paypal_comptes WHERE id_utilisateur = :id_utilisateur
				ORDER BY date DESC
				LIMIT 1');
				$req->execute(array('id_utilisateur' => $id));

				if($req->rowCount())
				{
					$resultat = $req->fetch();
					echo '<p>Compte Paypal enregistré pour <strong>'.htmlentities($_SESSION['nom']).'</strong> : <strong>'.htmlentities($resultat['paypal']).'</strong></p>';
				}
				else
				{
					echo '<p>Aucun compte Paypal enregistré.</p>';
				}
			
				echo '<form class="form-inline" action="revenus_publicite.php" method="post">
						  <div class="form-group">
							<label for="paypal" class="col-form-label mr-2">Nouveau compte Paypal : </label>
							<input type="text" class="form-control mr-2" id="paypal" name="paypal" required>
						  </div>
						  <button type="submit" class="btn btn-primary">Enregistrer</button>
						</form>
					</div>
				</div>
				<div class="card mb-3">
					<div class="card-header bg-primary">Versements passés</div>
					<div class="card-body">';
				
				$req = $bdd->prepare('SELECT pv.montant, UNIX_TIMESTAMP(pv.date) AS date, pc.paypal, pv.date-pc.date
				FROM paypal_versements pv
				INNER JOIN paypal_comptes pc
				ON pc.id_utilisateur = pv.id_utilisateur
				WHERE (pv.id, pv.date-pc.date) IN 
				(
					SELECT pv.id, min(pv.date-pc.date) 
					FROM paypal_versements pv
					INNER JOIN paypal_comptes pc
					ON pc.id_utilisateur = pv.id_utilisateur
					WHERE pv.id_utilisateur = :id_utilisateur
					AND pv.date-pc.date >= 0
					GROUP BY pv.id
				)');
				$req->execute(array('id_utilisateur' => $id));
				
				if($req->rowCount())
				{
					echo'<table class="table table-striped table-hover table-sm mt-3">
					  <thead>
						<tr>
						  <th scope="col">Compte</th>
						  <th scope="col">Montant (€)</th>
						  <th scope="col">Date</th>
						</tr>
					  </thead>
					  <tbody>';
					  
					while ($donnees = $req->fetch())
					{
						echo '<tr>
						  <td>'.$donnees['paypal'].'</td>
						  <td>'.$donnees['montant'].'</td>
						  <td>'.date("d/m/Y", $donnees['date']).'</td>
						</tr>';
					}
						
					echo '</tbody>
					</table>';
				}
				else
				{
					echo 'Aucun versement n\'a encore été effectué.';
				}

				echo '</div>
				</div>';
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
</body>

</html>