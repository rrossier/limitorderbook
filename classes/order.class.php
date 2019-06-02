<?php

class Order{

	private $id;
	private $stock;
	private $quantity;
	private $type;
	private $position;
	private $timestamp;
	private $id_user;
	private $price;
	private $expiration;

	public function __construct($array=array()){
		foreach($array as $key=>$value){
			$this->$key=$value;
		}
	}

	public function __set($key, $value){
		$this->$key=$value;
	}

	public function __get($key){
		return $this->$key;
	}

	public function get_id(){
		return $this->id;
	}

	public function get_type(){
		return $this->type;
	}

	public function get_position(){
		return $this->position;
	}

	public function get_quantity(){
		return $this->quantity;
	}

	public function get_price(){
		return $this->price;
	}

	public function get_stock(){
		return $this->stock;
	}

	public function get_timestamp(){
		return $this->timestamp;
	}

	public function get_id_user(){
		return $this->id_user;
	}

	static function cmp_price($a, $b)
    {
        $al = $a->price;
        $bl = $b->price;
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

	public function send(){
		if(!isset($stock, $quantity, $type, $position, $user)){
			return FALSE;
		}
		else{
			switch($type){
				case 'market':
					Market::execute_market_order($this);

				break;

				case 'limit':
					Market::execute_limit_order($this);

				break;

				case 'gtc':
				case 'aon':
				case 'stop':
					$this->set_expiration();
					$this->insert_orderbook();
				break;

				default:
			}
		}
	}

	private function set_expiration(){
		switch ($this->type) {
			case 'gtc':
				$this->expiration=bcadd($this->timestamp,(90*24*3600*1000),4);
				break;
			
			default:
				$this->expiration=Market::end_of_day_microtime();
				break;
		}
	}

	public function execute($quantity, $price){
		//$this->user->pay() or $this-user->receive
		$user=new User();
		$user->fill($this->id_user);
		if($user->execute($this, $quantity, $price)){
			$this->stock->pricing($price);
			$this->archive();
			return TRUE;
		}
		else{
			return FALSE;
		}
	}

	public function execute_partially($quantity, $price){
		$tp_order=clone($this);
		$new_quantity=($this->quantity - $quantity);
		$tp_order->quantity=$new_quantity;
		$tp_order->send();
	}

	public function archive(){
		//insert into db archives
		$this->timestamp=microtime(true);
	}

}