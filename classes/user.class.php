<?php

class Trader{
	private $id;
	private $name;
	private $stocks;
	private $cash;

	private $last_trade;

	public function __construct($id){
		$this->id=$id;
		$this->last_trade=100;
	}

	public function fill($id){
		$connexion=DataBase::getInstance();
		$req=$connexion->prepare("SELECT * FROM users WHERE id=:id AND status=1");
		$req->execute(array('id'=>$id));
		$row=$req->fetch(PDO::FETCH_ASSOC);
		if(!isset($row)){
			return FALSE;
		}
		else{
			$this->id=$id;
			$this->name=$row['name'];
			$this->stocks=unserialize($row['stocks']);
			$this->cash=$row['cash'];
		}
	}

	public function execute(Order &$order, $quantity, $price){
		$amount=round($price*$quantity,4);
		if($cash<$amount){
			return FALSE;
		}
		else{
			$cash-=$amount;
			$this->stocks[]=array('stock'=>$order->get_stock(),'quantity'=>$quantity,'price'=>$price);
			return TRUE;
		}
	}

	public function order($stock, $quantity, $type, $position, $tp_price=null){
		$connexion=DataBase::getInstance();
		$req=$connexion->prepare("INSERT INTO current_orders (stock, quantity, type, position, timestamp, id_user, price) VALUES (:stock, :quantity, :type, :position, :timestamp, :id_user, :price)");
		$timestamp=microtime(true);
		$id_user=$this->id;
		$price=($type=='market') ? 0 : $tp_price;
		$req->execute(array('stock'=>$stock, 'quantity'=>$quantity, 'type'=>$type, 'position'=>$position, 'timestamp'=>$timestamp, 'id_user'=>$id_user, 'price'=>$price));
		if($req){
			return $connexion->getLastInsert();
		}
		return FALSE;
	}

	public function autotrade($nb_stocks){
		$id_stock="STOCK-".rand(1,$nb_stocks);
		$qty=rand(10,100);
		$id=$this->id;
		$position=($id%2) ? "buy" : "sell";
		$limit_up=rand(0,5);
		$limit_down=rand(0,5);
		$nb=($position=='buy') ? rand(0,$limit_up) : -rand(0,$limit_down);
		$price=$this->last_trade+$nb;
		$this->last_trade=$price;
		$type='limit';
		$id_order=$this->order($id_stock, $qty, $type, $position, $price);
		//$connexion=DataBase::getInstance();
		//$req=$connexion->prepare("INSERT INTO orderbook (id_order, status) VALUES (:id_order, 1)");
        //$req->execute(array('id_order'=>$id_order));
	}


}