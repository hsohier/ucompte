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
  <title>Aide à l'utilisation du site</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" href="theme.css" type="text/css">
	<?php
	script_googleanalytics();
	?>
  </head>

<body class="bg-light">
<?php
afficher_navbar('aide');
?>
  <div class="mb-2 text-justify">
    <div class="container">
      <div class="row">
        <div class="col-12 bg-white border shadow">
          <h3 class="text-center">Aide à l'utilisation du site</h3>
		  <div class="card mb-3">
            <div class="card-header bg-light">Présentation générale</div>
            <div class="card-body">
			Ce site rassemble des outils destinés aux communautés partageant des informations sur les placements financiers pour particulier.
			Il ne concerne que les fonds (où l'argent est confié à un organisme d'investissement) et les ETF, aussi appelés trackers (où l'argent suit les performances d'un indice comme le CAC40 français ou le S&P 500 américain).
			Ce site ne concerne donc pas les investissements directs, tels que l'achat d'actions. 
			Les supports privilégiés sont l'Assurance vie et le PEA, voire le CTO.
			</div>
          </div>
          <div class="card mb-3">
            <div class="card-header bg-light">Partage de portefeuilles</div>
            <div class="card-body">
			Ce site vous permet d'abord de <strong>partager un ou plusieurs portefeuilles</strong>. La seule contrainte est alors de respecter l'idée générale du type de portefeuille (défensif, réactif ou dynamique), décrite au survol de l'icône "<img src="images/info.png" class="rounded-circle" width="15em">" sur les pages concernées.
			Les membres partagent des portefeuilles pour une variété de raisons. Il peut s'agir pour vous :
			<ul>
			<li>de recommandations de placement</li>
			<li>de portefeuilles dont vous vous inspirez pour réaliser vos placements, sans forcément toujours suivre exactement l'un d'eux</li>
			<li>de portefeuilles que vous avez de manière très concrètes sur plusieurs Assurances vie ou PEA</li>
			<li>...</li>
			</ul>
			Vous pouvez ajouter n'importe quel fonds ou ETF. Lorsque vous partagez un portefeuille, des recommandations de fonds et de trackers vous sont automatiquement faites à partir de portefeuilles similaires existants. 
			Une fois vos portefeuilles finalisés, vous pouvez facilement partager votre fiche publique dont l'adresse est de la forme suivante, "Admin" étant à remplacer par votre nom:
			<samp class="bg-light p-2 m-2" style="display: block;">
			http://ucompte.com/membres/Admin
			</samp>
			La fiche publique vous permet également de naviguer vers des portefeuilles similaires.
			</div>
          </div>
		  <div class="card mb-3">
            <div class="card-header bg-light">Actifs et régions des portefeuilles</div>
            <div class="card-body">
			<strong>Les actifs (actions, obligations, liquidités, ou autres) et les régions</strong> sont automatiquement recherchés dans les 15 minutes qui suivent l'ajoute d'un fonds ou d'un ETF. Si les informations affichées vous paraissent ensuite incomplètes, elles peuvent être corrigées. 
			Chaque portefeuille peut ainsi être grossièrement décrit par ses actifs et les régions qu'il couvre à l'aide de graphiques en camembert. Cette visualisation claire doit notamment permettre de faciliter l'apprentissage de la diversification des placements.
			</div>
          </div>
		  <div class="card mb-3">
            <div class="card-header bg-light">Synthèse</div>
            <div class="card-body">
			Une page <strong>synthétise</strong> l'ensemble des portefeuilles partagés sur le site, permettant d'offrir des éléments de réponses aux questions suivantes pour chaque type de portefeuille :
			<ul>
			<li>quelle est la part moyenne du fonds euro ?</li>
			<li>quels sont les fonds et les ETFs soulevant le plus d'intérêt ?</li>
			<li>lorsque des membres choisissent un fonds ou un ETF, quel pourcentage de leur porteuille lui consacre-t-elles en moyenne ?</li>
			<li>quelle est la répartition d'actifs et de régions moyennes ?</li>
			<li>...</li>
			</ul>
			
			L'"allocation moyenne" indiquée en page de synthèse représente le pourcentage que représente le fonds ou l'ETF dans le portefeuille des membres qui en possèdent. 
			Ce chiffre est donc indépendant des membres qui ne le possèdent pas.<br />
			<br />
			Les moyennes (part moyenne du fonds euro, allocations moyennes, répartition moyenne des actifs et des régions,..) sont pondérées par le nombre de recommandations reçues par chaque membre.<br />
			<br />
			Un score reflétant l'intérêt des membres est calculé pour chaque fonds et ETF. Ce score n'est pas affiché mais c'est sur lui que se base le classement des sélections hors fonds euro. La formule de calcul du score est :<br />
			<br />
			<img src="images/equation_ucs.gif" alt="[equation UCs]" /><br />
			<br />
			<i>P</i> représente le pourcentage aloué à l\'unité de compte par un membre donné, 
			<i>T</i> est une valeur qui décroit linéairement avec le temps jusqu'à atteindre 0.25% de sa valeur initiale après une période égale à <span class="math">&tau;=5</span>ans, 
			et <i>R</i> est le nombre de recommendations reçues par le membre.
			</div>
          </div>
		  <div class="card mb-3">
            <div class="card-header bg-light">Testeur et optimiseur</div>
            <div class="card-body">
			Le <span class="gras">testeur</span> permet :
			<ul>
			<li>de tester des portefeuilles facilement, sans même être nécessairement membre</li>
			<li>d'observer la meilleure et la pire performance sur un an du portefeuille (backtest)</li>
			</ul>
			Le backtest est bien sûr contraint par le fonds ou l'ETF existant depuis le moins longtemps. Comme pour les actifs et les régions, les performances passées sont récupérées automatiquement et peuvent être complétées au besoin.<br />
			<br />
			L'<strong>optimiseur</strong> permet quand à lui de trouver les pourcentages à consacrer à des fonds et ETFs à partir d'une répartition cible pour les actifs et les régions.
			Des valeurs minimales et maximales sont présentées. Il s'agit des valeurs que les fonds et ETFs sélectionnés ne permettent pas de dépasser.
			Bien sûr, plus la répartition d'actifs et de régions souhaitée est particulière, moins il y a de chance que le résultat lui corresponde exactement, mais il s'agira toujours d'un résultat optimal.
			</div>
          </div>
		  <div class="card mb-3">
            <div class="card-header bg-light">Vos recommandations</div>
            <div class="card-body">
			Ce site est votre outil, à vous donc d'identifier les fonctionnalités et le contenu que vous souhaiteriez voir ajoutés !
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