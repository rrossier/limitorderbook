<?php
$id=(isset($_GET['stock'])) ? htmlspecialchars($_GET['stock']) : 1;
$authorized=array(1,2,3,4,5,6,7,8,9,10);

if(in_array($id, $authorized)){

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
	$req=$connexion->prepare("SELECT * FROM past_orders WHERE stock=:stock ORDER BY timestamp ASC");
	$stock="STOCK-".$id;
	$req->execute(array('stock'=>$stock));
	$prices=array();
	$volumes=array();
	$timestamps=array();
	while($row=$req->fetch(PDO::FETCH_ASSOC)){
		$prices[]=$row['price'];
		$volumes[]=$row['quantity'];
		$timestamps[]=$row['timestamp'];
	}
	$time=array();
	$start=$timestamps[0];
	foreach ($timestamps as $tp) {
		$time[]=round(($tp-$start)*1000,3);
	}
	//var_dump(count($time));
	//var_dump(count($prices));
	//var_dump($volumes);


	require_once ('graph/src/jpgraph.php');
	require_once ('graph/src/jpgraph_line.php');
	require_once ('graph/src/jpgraph_bar.php');
	require_once ("graph/src/jpgraph_mgraph.php");
	 
	// Some data
	$ydata = $prices;
	 
	// Create the graph. These two calls are always required
	$graph = new Graph(1000,400);
	$graph->SetScale('intint',min($prices)-1,max($prices)+1,0,max($time)+10);
	$graph->SetMargin(40,10,20,40);
	$title=$stock." prices";
	$graph->title->Set($title);
	// Setup titles and X-axis labels
	$graph->xaxis->title->Set('milliseconds');
	$graph->xaxis->SetTickLabels($time);
	// Setup Y-axis title
	$graph->yaxis->title->Set('prices');

	 
	// Create the linear plot
	$lineplot=new LinePlot($ydata, $time);
	$lineplot->SetColor('blue');
	$lineplot->mark->SetType(MARK_CROSS);
	$lineplot->mark->SetColor('red');
	$lineplot->mark->SetFillColor('red');
	$lineplot->value->Show();
	 
	// Add the plot to the graph
	$graph->Add($lineplot);
	 
	// Display the graph
	//$graph->Stroke();

	$graph2= new Graph(1000, 200);
	$graph2->SetScale('intint',0,max($volumes)+2,0,max($time)+10);
	 
	// Add a drop shadow
	$graph2->SetShadow();
	 
	// Adjust the margin a bit to make more room for titles
	$graph2->SetMargin(40,10,20,40);
	 
	// Create a bar pot
	$bplot = new BarPlot($volumes,$time);
	 
	// Adjust fill color
	$bplot->SetFillColor('orange');
	$graph2->Add($bplot);
	 
	// Setup the titles
	$title=$stock." Volumes";
	$graph2->title->Set($title);
	$graph2->xaxis->title->Set('milliseconds');
	$graph2->yaxis->title->Set('Volumes');
	 
	$graph2->title->SetFont(FF_FONT1,FS_BOLD);
	$graph2->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
	$graph2->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

	$mgraph = new MGraph();
	$xpos1=3;$ypos1=3;
	$xpos2=3;$ypos2=400;
	$mgraph->Add($graph,$xpos1,$ypos1);
	$mgraph->Add($graph2,$xpos2,$ypos2);
	$mgraph->Stroke();

}
elseif($id='all'){

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
	$req=$connexion->prepare("SELECT * FROM past_orders ORDER BY timestamp ASC");
	$stock="STOCK-".$id;
	$req->execute();
	$prices=array();
	$volumes=array();
	$timestamps=array();
	while($row=$req->fetch(PDO::FETCH_ASSOC)){
		$timestamps[]=$row['timestamp'];
	}
	$time=array();
	$start=$timestamps[0];
	$nbs=array();
	foreach ($timestamps as $tp) {
		$time[]=round(($tp-$start)*1000,0);
		$nbs[]=(int) (end($time) / 100);
	}
	//var_dump($nbs);
	//var_dump(count($time));
	//var_dump(count($prices));
	//var_dump($volumes);
	$arr=array();
	foreach($nbs as $nb){
		$arr[$nb][]=$nb;
	}
	//var_dump($arr);
	$freqs=array();
	foreach($arr as $key=>$tp){
		$freqs[]=count($tp);
	}
	//var_dump($freqs);

	require_once ('graph/src/jpgraph.php');
	require_once ('graph/src/jpgraph_line.php');
	require_once ('graph/src/jpgraph_bar.php');
	require_once ("graph/src/jpgraph_mgraph.php");
	$graph2= new Graph(1000, 200);
	$graph2->SetScale('intint',0,max($freqs)+2,0,0);
	 
	// Add a drop shadow
	$graph2->SetShadow();
	 
	// Adjust the margin a bit to make more room for titles
	$graph2->SetMargin(40,10,20,40);
	 
	// Create a bar pot
	$bplot = new BarPlot($freqs);
	 
	// Adjust fill color
	$bplot->SetFillColor('orange');
	$graph2->Add($bplot);
	 
	// Setup the titles
	$title="Number of Trades";
	$graph2->title->Set($title);
	$graph2->xaxis->title->Set('100 milliseconds');
	$graph2->yaxis->title->Set('Volumes');
	 
	$graph2->title->SetFont(FF_FONT1,FS_BOLD);
	$graph2->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
	$graph2->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
	$graph2->Stroke();

}
else{
	header('Location:http://google.ch');
}


?>