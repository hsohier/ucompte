<?php
function script_googleanalytics()
{
	echo '
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-147424985-1"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag(\'js\', new Date());

	  gtag(\'config\', \'UA-147424985-1\');
	</script>
	';
}
function script_googleadsense()
{
	echo '
		<div class="col-12 border shadow mt-2 pt-1 pb-1 bg-primary publicite-degrade publicite_conteneur">
			<div class="mb-1 text-center publicite_elment">Les revenus publicitaires sont redistribués aux membres. <a class="publicite_texte" href="/publicite.php"><strong>En savoir plus</strong></a>.</div>
			<div class="publicite_elment">
				<ins class="adsbygoogle"
				   style="display:inline-block;min-width:400px;max-width:970px;width:100%;height:90px"
				   data-ad-client="ca-pub-3702641784758713"
				   data-ad-slot="7741213901"></ins>
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
			</div>
		</div>
	';
}
?>