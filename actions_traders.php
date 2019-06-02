<?php
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

	include('config.php');
	include('classes/db.class.php');
	include('classes/orderbook.class.php');
	include('classes/order.class.php');
	include('classes/storedorder.class.php');
	include('classes/activeorder.class.php');
	include('classes/user.class.php');
	include('classes/market.class.php');
	include('classes/stock.class.php');

	$connexion=DataBase::getInstance();
	$connexion->exec("TRUNCATE TABLE current_orders");
	$connexion->exec("TRUNCATE TABLE accounts");
	$connexion->exec("TRUNCATE TABLE orderbook");
	$connexion->exec("TRUNCATE TABLE past_orders");
	$market=Market::getInstance();
	$orderbook=OrderBook::getInstance();
	//$orderbook->display();

	function getTimestamp(){ 
	    $seconds = microtime(true); // false = int, true = float 
	    return round( ($seconds * 1000) ); 
	} 
	$steps=2;
	$i=0;
	$nb_traders=100;
	$nb_stocks=10;
	$latence=3000;
	$market->generate_stocks($nb_stocks);
	$traders=array();
	$j=1;
	while($j<$nb_traders){
		$traders[]=new Trader($j);
		$j++;
	}
	while($i<$steps){
		$start=getTimestamp();

		$operations=0;
		while($operations<100){
			foreach($traders as $trader){
				$trader->autotrade($nb_stocks);
				$operations++;
			}
		}
		$current=getTimestamp();
		/*
		if($current-$start<$latence){
			$nap=$latence-($current-$start);
			$t=0;
			while($t<$nap){
				sleep(1);
				echo "sleep ".$nap."<br/>";
				$t+=1000;
			}
		}
		*/
		//$orderbook->display();
		$orderbook->process();
		$i++;
	}

	echo 1;

}
else{
	echo 0;
}
?>