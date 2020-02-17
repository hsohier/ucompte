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
  <title>Redistribution des revenus publicitaires</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
  	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('publicite');
?>
  <div class="mb-2 text-justify">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Redistribution des revenus publicitaires</h3>
		  <div class="card mb-3">
            <div class="card-header bg-light">Principe</div>
            <div class="card-body">
			Une publicité est affichée au bas de chaque page d'Ucompte, et notamment sur les <a href="/membres.php">fiches publiques de chaque membre</a>. Les revenus générés par la publicité affichée sur votre fiche publique vous sont intégralement reversés. Plus vous partagez votre fiche publique, plus vos revenus seront importants. L'objectif est triple :
			<ul>
			<li>Favoriser le partage des choix d'épargne et rendre ainsi plus utiles les outils statistiques mis à disposition</li>
			<li>Rémunérer de manière juste les membres de la communauté pour leur contribution</li>
			<li>Assurer la pérénité du site par les revenus générés en dehors des fiches publiques (synthèse, testeur et optimiseur, etc.)</li>
			</ul>
			</div>
          </div>
          <div class="card mb-3">
            <div class="card-header bg-light">Fonctionnement</div>
            <div class="card-body">
			<p>Ucompte utilise la régie publicitaire Google Adsense ainsi que les mesures de Google Analytics. Comme expliqué <a href="https://support.google.com/analytics/answer/3254288?hl=fr">sur le site de Google</a>, l'association de Google Adsense et de Google Analytics permet de mesurer les revenus générés par chaque page d'Ucompte. C'est le revenu ainsi mesuré pour votre fiche publique qui vous est ensuite reversé.</p>
			<p>Les revenus sont versés bi-annuellement par Paypal, le 5 janvier et le 5 juillet.</p>
			<p>Une fois identifié, cliquez sur "Compte" puis sur "Publicité" pour déclarer votre compte Paypal et voir l'historique de vos revenus. Le compte Paypal doit être déclaré au plus tard la veille du jour prévu pour le versement.</p>
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
</body>

</html>