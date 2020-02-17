<?php
session_start();

include("fonctions/afficher_navbar.php");
include("fonctions/scripts_google.php");

$message = "";

if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
{
	$message = "Vous êtes déjà connecté.";
	$message_type = 'danger';
}
else
{
	try
	{
		$bdd = new PDO('mysql:host=*****;dbname=*****;charset=utf8', '*****', '*****');
	}
	catch(Exception $e)
	{
		die('Erreur : '.$e->getMessage());
	}
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		require('autoload.php');
		$secret = "*****";
		$recaptcha = new \ReCaptcha\ReCaptcha($secret);
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
		
		if ($resp->isSuccess())
		{
			if (($_POST['nom'] != '') and (strlen($_POST['nom'])<=15) and ($_POST['mdp'] != ''))
			{
				$nom = $_POST['nom'];
			
				if (isset($_POST['inscription']))
				{
					if (preg_match("#^[\w\.]+$#", $_POST['nom']))
					{
						if ($_POST['mdp'] == $_POST['mdp_conf'])
						{
							$req = $bdd->prepare('SELECT * FROM utilisateurs WHERE nom = :nom');
							$req->execute(array('nom' => $nom));
							
							if($req->rowCount())
							{
								$message = "Nom d'utilisateur existant.";
								$message_type = 'danger';
							}
							else
							{
								$req = $bdd->prepare('INSERT INTO utilisateurs(nom, mdp) VALUES(:nom, :mdp)');
								$req->execute(array(
									'nom' => $nom,
									'mdp' => password_hash($_POST['mdp'], PASSWORD_DEFAULT)));
								
								$req = $bdd->prepare('SELECT * FROM utilisateurs WHERE nom = :nom');
								$req->execute(array('nom' => $nom));
								$resultat = $req->fetch();
								
								$_SESSION['id'] = $resultat['id'];
								$_SESSION['nom'] = $nom;
								
								$message = "Votre inscription s'est déroulée avec succès. Vous êtes maintenant connecté.";
								$message_type = 'success';
							}
						}
						else
						{
							$message = "Confirmation du mot de passe différente du mot de passe.";
							$message_type = 'danger';
						}
					}
					else
					{
						$message = 'Le nom ne peut comporter que des lettres, chiffres, points, tirets bas.';
						$message_type = 'danger';
					}
				}
				else
				{
					$req = $bdd->prepare('SELECT id, mdp FROM utilisateurs WHERE nom = :nom');
					$req->execute(array('nom' => $nom));
					$resultat = $req->fetch();

					if ($resultat)
					{
						if (password_verify($_POST['mdp'], $resultat['mdp']))
						{
							$_SESSION['id'] = $resultat['id'];
							$_SESSION['nom'] = $nom;
						
							$message = "Vous êtes maintenant connecté.";
							$message_type = 'success';
						}
						else
						{
							$message = "Mauvais identifiant ou mot de passe.";
							$message_type = 'danger';
						}
					}
					else
					{
						$message = "Mauvais identifiant ou mot de passe.";
						$message_type = 'danger';
					}
				}
			}
			else
			{
				$message = 'Les champs "Nom" et "Mot de passe" ne peuvent pas être vides, et le champ "Nom" est limité à 15 caractères.';
				$message_type = 'danger';
			}
		}
		else
		{
			$message = 'Erreur CAPTCHA : ';
			foreach ($resp->getErrorCodes() as $code) {
                $message .= $code.' ';
            }
			$message_type = 'danger';
		}
	}
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
	<script src='https://www.google.com/recaptcha/api.js'></script>
	<script type="text/javascript">
	  var onSubmit = function(response) {
		document.getElementById("form_connexion").submit();
	  };
	</script>
	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('connexion');
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center mb-4">Connexion</h3>
			<?php
			if ($message != "")
			{
				echo '<div class="bg-'.$message_type.' p-2">'.$message.'</div>';
			}
			
			echo '<div class="col-6 mb-5 mx-auto">';
			
			if (!(isset($_SESSION['id']) AND isset($_SESSION['nom'])))
			{
			?>
			<form action="connexion.php" method="post" id="form_connexion" class="mt-2">
			<div class="form-group row my-0">
			  <label for="nom" class="col-6 col-form-label pb-0"><strong>Nom : </strong></label>
			  <div class="col-6">
				<input class="form-control form-control-sm" type="text" id="nom" name="nom" maxlength="15">
			  </div>
			</div>
			<div class="form-group row">
			  <div class="col-6"></div>
			  <div class="col-6 small text-muted">
			    (Caractères autorisés : lettres, chiffres, points, tirets bas)
			  </div>
			</div>
			<div class="form-group row my-0">
			  <label for="mdp" class="col-6 col-form-label"><strong>Mot de passe : </strong></label>
			  <div class="col-6">
				<input class="form-control form-control-sm" type="password" id="mdp" name="mdp">
			  </div>
			</div>
			<div class="form-group row mt-1 mb-0">
			  <label for="inscription" class="col-6"><strong>Si inscription, cocher la case : </strong></label>
			  <div class="col-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox"  name="inscription" id="inscription" onclick="enable_text(this.checked)">
				</div>
			  </div>
			</div>
			<div class="form-group row my-0">
			  <label for="mdp_conf" class="col-6 col-form-label"><strong>Confirmation du mot de passe : </strong></label>
			  <div class="col-6">
				<input class="form-control form-control-sm" type="password" id="mdp_conf" name="mdp_conf" disabled>
			  </div>
			</div>
			<div class="form-group row my-0">
			<div class="col-6"></div>
			<div class="col-6">
			<button
			class="g-recaptcha btn btn-primary btn-sm"
			data-sitekey="6LdmkjgUAAAAAOmAkV_pN6SGYD2Oz4n0_HRfoMX8"
			data-callback="onSubmit">
			Envoyer
			</button>
			</div>
			</div>
			</form>
			<?php
			}
			
			echo '</div>';
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
	<script type="text/javascript">
	function enable_text(status)
	{
		status=!status;
		document.getElementById("form_connexion").mdp_conf.disabled = status;
	}
	</script>
</body>

</html>