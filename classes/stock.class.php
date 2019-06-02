<?php

class Stock{
	
	private $ticker;
	private $name;
	private $name_long;
	private $last_transaction;
	private $timestamp_last_transaction;
	private $price;
	private $shares;
	private $traded_shares;
	private $date_ipo;
	private $bid;
	private $ask;

	public function __construct(){

	}

	public function pricing($price){
		$this->price=$price;
	}

	private function get_bid(){
		$tp_orderbook=OrderBook::getInstance();
		$all_orders=$tp_orderbook->orders['market'];
        $tp_orders1=$tp_orderbook->get_orders_by_stock($all_orders, $this->ticker);
        $tp_orders2=$tp_orderbook->get_orders_by_position($tp_orders1, 'sell');
        $pertinents=array_reverse($tp_orderbook->sort_by_price($tp_orders2));
        if(isset($pertinents[0])){
        	$this->bid=$pertinents[0]->get_price();
        	return TRUE;
        }
        else{
        	return FALSE;
        }
	}

	private function get_ask(){
		$tp_orderbook=OrderBook::getInstance();
		$all_orders=$tp_orderbook->orders['market'];
        $tp_orders1=$tp_orderbook->get_orders_by_stock($all_orders, $this->ticker);
        $tp_orders2=$tp_orderbook->get_orders_by_position($tp_orders1, 'buy');
        $pertinents=$tp_orderbook->sort_by_price($tp_orders2);
        if(isset($pertinents[0])){
        	$this->bid=$pertinents[0]->get_price();
        	return TRUE;
        }
        else{
        	return FALSE;
        }
	}

	public function get_price(){
		if($this->get_ask() && $this->get_bid()){
			return round(($this->bid+$this->ask)/2,4);
		}

	}
}