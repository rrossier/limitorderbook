<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Limit Order Book - v0.1</title>
    <script src="jquery-1.7.2.min.js"></script>
    <script type='text/javascript'>
	$(document).on("click","button.engine",function(event){
	  $(function() {
	          $.ajax({
	            url: 'actions_traders.php',
	            type: "POST",
	            data: '',
	            dataType: "json",
	            beforeSend:function(data){
	              $('#loading').show();
	          },
	            success: function(result) {
	            	if(result){
	                	$('#results').show();
	                	$('#loading').hide();
	            	}
	            	else{

	            	}
	            }
	          });
	      });
	});
    </script>
  </head>
<body>
	<h1>Limit Order Book</h1>
	<h3>10 stocks</h3>
	<h3>100 traders</h3>
	<h3>>=1 000 orders</h3>
	<h3>>=3 000 trades</h3>
	<hr/>
	<button onclick="engine()" class="engine"/>Generate Order Book and process it</button>
	<div id="loading" style="display:none;">
	  Processing...
	</div>
	<hr/>
	<div id="results" style="display:none;">
		Done!
	</div>
	<div>
	<?php
	for($i=1;$i<=10;$i++){
		echo '<strong><a href="view.php?stock='.$i.'" target="_blank">View Results Stock-'.$i.'</a></strong><br/>';
	}
	?>
	<strong><a href="view.php?stock=all" target="_blank">View All Trades</a></strong><br/>
	</div>

	<br/><br/><em>Note: la latence est dûe principalement à l'hébergement mutualisé.<br/>Sur une machine dédiée le double de calculs prend moins de 6 secondes.</em>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-16540758-6']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body>
</html>