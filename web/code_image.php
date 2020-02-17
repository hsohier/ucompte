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
  <title>Sélection hors fonds euro au format image</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('index');
?>
  <div class="mb-2">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Sélection hors fonds euro au format image</h3>
			<p>La sélection hors fonds euro (apparaissant sur <a href="/">la page de synthèse</a>) est aussi disponible au format image.<br />
			Par exemple, pour le portefeuille défensif :</p>
			<img class="mb-2 ml-2" src="image_top.php?portefeuille=defensif" /><br />
			<p>Il s'agit d'une image générée à la volée qui s'appuie, à chaque chargement, sur les données les plus à jour.<br />
			Le format image permet d'afficher facilement le classement sur un autre site ou forum.<br />
			Les adresses des images correspondant aux trois portefeuilles sont :</p>
			<samp class="bg-light p-2 m-2" style="display: block;">
			http://ucompte.com/image_top_uc.php?portefeuille=defensif<br />
			http://ucompte.com/image_top_uc.php?portefeuille=reactif<br />
			http://ucompte.com/image_top_uc.php?portefeuille=dynamique
			</samp>
			<p>Par exemple, pour afficher la sélection du portefeuille défensif sur un forum, le code est:</p>
			<samp class="bg-light p-2 m-2" style="display: block;">
			[img]http://ucompte.com/image_top_uc.php?portefeuille=defensif[/img]
			</samp>
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