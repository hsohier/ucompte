<?php
function afficher_fondseuro($pourcentage_fondseuro, $portefeuille)
{
	echo '<div class="progress">';
	if ($pourcentage_fondseuro[id_portefeuille($portefeuille)] > 0)
	{
		echo '<div class="progress-bar progress-bar-striped progress-bar-fonds" role="progressbar" style="width:'.$pourcentage_fondseuro[id_portefeuille($portefeuille)].'%">';

		if ($pourcentage_fondseuro[id_portefeuille($portefeuille)] >= 15)
		{
			echo $pourcentage_fondseuro[id_portefeuille($portefeuille)].'% fonds euro';
		}
		else
		{
			echo $pourcentage_fondseuro[id_portefeuille($portefeuille)].'%';
		}
		echo '</div>';
	}
	if ((100-$pourcentage_fondseuro[id_portefeuille($portefeuille)]) > 0)
	{
		echo '<div class="progress-bar progress-bar-striped progress-bar-horsfonds" role="progressbar" style="width:'.(100-$pourcentage_fondseuro[id_portefeuille($portefeuille)]).'%">';
		if ((100-$pourcentage_fondseuro[id_portefeuille($portefeuille)]) >= 15)
		{
			echo (100-$pourcentage_fondseuro[id_portefeuille($portefeuille)]).'% hors fonds euro';
		}
		else
		{
			echo (100-$pourcentage_fondseuro[id_portefeuille($portefeuille)]).'%';
		}
		echo '</div>';
	}
	echo '</div>';
}
?>