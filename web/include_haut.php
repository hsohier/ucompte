<div id="principal">
<div id="menu">
<a href="/">Synthèse</a> -
<?php
if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
{
	echo ' <a href="/portefeuilles.php">Vos portefeuilles</a> -';
}

echo ' <a href="/testeur.php">Testeur et optimiseur</a> - <a href="/membres.php">Membres</a> - <a href="/aide.php">Aide</a> -';

if (isset($_SESSION['id']) AND isset($_SESSION['nom']))
{
	echo ' <a href="/deconnexion.php">Déconnexion</a>';
}
else
{
	echo ' <a href="/connexion.php">Connexion</a>';
}
?>
</div> <!-- div menu -->