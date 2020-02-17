<?php
function afficher_graphiques_actifs($portefeuille, $actifs_moyennes, $couleurs_actifs)
{
?>
	  // Set a callback to run when the Google Visualization API is loaded.
	  google.charts.setOnLoadCallback(drawChart_actifs_<?php echo $portefeuille ?>);

	  // Callback that creates and populates a data table,
	  // instantiates the pie chart, passes in the data and
	  // draws it.
	  function drawChart_actifs_<?php echo $portefeuille ?>() {

		// Create the data table.
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Type d\'actif');
		data.addColumn('number', 'Pourcentage');
		data.addRows([
		<?php
		$nombre_actifs = count($actifs_moyennes);
		$i = 1;
		foreach ($actifs_moyennes as $key => $value)
		{
			if ($value < 0)
			{
				$value = 0;
			}
			echo "['".$key." : ".$value."%', {v: ".$value.", f: '".$value."%'}]";
			if ($i != $nombre_actifs)
			{
				echo ",";
			}
			$i++;
		}
		?>
		]);

		// Set chart options
		var options = {'title':'Types d\'actifs',
					   'width':325,
					   'height':200,
					   'chartArea': {'width': '90%', 'height': '80%'},
					   'enableInteractivity': false,
					   'colors':[
						<?php
						$i = 1;
						foreach ($actifs_moyennes as $key => $value)
						{
							echo "'".$couleurs_actifs[$key]."'";
							if ($i != $nombre_actifs)
							{
								echo ",";
							}
							$i++;
						}
						?>
					   ],
					   'pieSliceText': 'value'};

		// Instantiate and draw our chart, passing in some options.
		var chart = new google.visualization.PieChart(document.getElementById('chart_div_actifs_<?php echo $portefeuille ?>'));
		chart.draw(data, options);
	  }
<?php
}
?>