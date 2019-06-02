<?php

class StoredOrder{

	private $id;
	private $stock;
	private $quantity;
	private $type;
	private $position;
	private $timestamp;
	private $id_user;
	private $price;
	private $expiration;

	public function __construct($stock, $quantity, $type, $position, $id_user, $price){
		//$this->id=uniqid();
		$this->stock=$stock;
		$this->quantity=$quantity;
		$this->type=$type;
		$this->position=$position;
		$this->id_user=$id_user;
		$this->price=$price;
		$this->timestamp=microtime(true);
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

	static function cmp_price($a, $b)
    {
        $al = $a->price;
        $bl = $b->price;
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

	public function insert(){
		$this->set_expiration();
		OrderBook::add($this);
		return TRUE;
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

	public function archive(){
		//insert into db archives
		$this->timestamp=microtime(true);
	}
}