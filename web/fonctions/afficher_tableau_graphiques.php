<?php
function afficher_tableau_graphiques($portefeuille)
{
	echo '<table class="mx-auto">
	  <tr>
		<td>
			<div id="chart_div_actifs_'.$portefeuille.'" style="border: 1px solid #ccc"></div>
		</td>
		<td>
			<div id="chart_div_regions_'.$portefeuille.'" style="border: 1px solid #ccc"></div>
		</td>
	  </tr>
	</table>';
}
?>