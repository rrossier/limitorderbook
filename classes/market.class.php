<?php

class Market{
	
	private $id;
	private $name;
	private $list_stocks;
	private $open_hours;

	private static $Instance; 
    private $OrderBook;

    private function __construct(){
    	//retrieve data from db
    	$this->open_hours=array('open'=>'09:00','close'=>'16:00');
        $this->OrderBook=OrderBook::getInstance();
    } 

    public static function getInstance() 
    { 
        if (!self::$Instance) 
        { 
            self::$Instance = new Market(); 
        } 

        return self::$Instance; 
    }

    public function IPO(Stock &$stock){
    	$this->list_stocks[]=$stock;
    }

    public function execute_market_order(Order &$order){
        $this->OrderBook->execute_market_order($order);
    }

    public function execute_limit_order(Order &$order){
        $this->OrderBook->execute_limit_order($order);
    }

    public function execute_orderbook(OrderBook &$orderbook){
        $this->OrderBook->process();
    }

    public function end_of_day_microtime(){
    	return mktime(date('H',$this->open_hours['close']),date('i',$this->open_hours['close']),date('s',$this->open_hours['close']),date('n'),date('j'),date('Y'))*1000;
    }

    public function generate_stocks($nb){
        $i=1;
        $connexion=DataBase::getInstance();
        $connexion->exec("TRUNCATE TABLE stocks");
        while($i<=$nb){
            $stock="STOCK-".$i;
            $req=$connexion->prepare("INSERT INTO stocks (stock) VALUES (:stock)");
            $req->execute(array('stock'=>$stock));
            if($req){
                $i++;
            }
        }
    }
}