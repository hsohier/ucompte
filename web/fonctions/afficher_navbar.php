<?php
function afficher_navbar($page)
{
	echo '<nav class="navbar navbar-expand-md bg-primary navbar-dark my-2">
		<div class="container">
		  <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbar2SupportedContent" aria-controls="navbar2SupportedContent" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button>
		  <div class="collapse navbar-collapse text-center justify-content-center" id="navbar2SupportedContent">
			<ul class="navbar-nav">
			  <li class="nav-item">
<a class="nav-link'; if ($page == 'index') {echo ' active';} echo '" href="/"><i class="fa d-inline fa-lg fa-eye"></i>Synthèse</a>
			  </li>';
	if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
	{
		echo '<li class="nav-item">
			<a class="nav-link'; if ($page == 'portefeuilles') {echo ' active';} echo '" href="/portefeuilles.php"><i class="fa fa-fw fa-pencil"></i>Vos portefeuilles</a>
		</li>';
	}

	echo '<li class="nav-item">
		<a class="nav-link'; if ($page == 'testeur') {echo ' active';} echo '" href="/testeur.php"><i class="fa d-inline fa-lg fa-crosshairs"></i>Testeur et optimiseur</a>
	</li>
	<li class="nav-item">
		<a class="nav-link'; if ($page == 'membres') {echo ' active';} echo '" href="/membres.php"><i class="fa fa-fw fa-address-book"></i>Membres</a>
	</li>
	<li class="nav-item">
		<a class="nav-link'; if ($page == 'aide') {echo ' active';} echo '" href="/aide.php"><i class="fa fa-fw fa-compass"></i>Aide</a>
	</li>';

	if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
	{
		echo '<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle'; if ($page == 'revenus_publicite' or $page == 'deconnexion') {echo ' active';} echo '" data-toggle="dropdown" href="#">Compte</a>
			<div class="dropdown-menu">
				<a class="dropdown-item" href="/revenus_publicite.php">Publicité</a>
				<a class="dropdown-item" href="/deconnexion.php">Déconnexion</a>
			</div>
		</li>';
	}
	else
	{
		echo '<li class="nav-item">
			<a class="nav-link'; if ($page == 'connexion') {echo ' active';} echo '" href="/connexion.php"><i class="fa fa-user fa-fw"></i>Connexion</a>
		</li>';
	}
	echo '<li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">Retour vers</a>
            <div class="dropdown-menu">
              <a class="dropdown-item" href="https://avenuedesinvestisseurs.fr/">Avenue des investisseurs</a>
              <a class="dropdown-item" href="https://forum.hardware.fr/hfr/Discussions/Viepratique/pognon-epargne-placements-sujet_66515_1.htm">Forum HFR</a>
			  <a class="dropdown-item" href="http://www.frikenfonds.com/">Frikenfonds</a>
            </div>
          </li>
		  </ul>
		  </div>
		</div>
	  </nav>';
}
?>