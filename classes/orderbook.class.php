<?php

class OrderBook{
	
	private $orders;
	private $last_entry;
	private $last_update;

    private $best_bids;
    private $best_asks;

    private $sorted_orders;

	private static $Instance; 

    private function __construct(){
        $this->load_quotes();
    } 

    public static function getInstance() 
    { 
        if (!self::$Instance) 
        { 
            self::$Instance = new OrderBook(); 
        } 

        return self::$Instance; 
    }

    public function add(Order &$order){
        $this->orders[$order->get_id()]=$order;
        $this->save($order);
        $this->last_entry=microtime(true);
    }

    public function delete(Order &$order){
        if(!in_array($order->get_id(), array_keys($this->orders))){
            return 0;
        }
        else{
            unset($this->orders[$order->get_id()]);
            return 1;
        }
    }

    public function save(Order &$order){
        //insert into db orderbook
        $connexion=DataBase::getInstance();
        $req=$connexion->prepare("INSERT INTO orderbook (id_order, status) VALUES (:id_order, 1)");
        $req->execute(array('id_order'=>$order->get_id()));
    }

    private function sort_by_price($array){
        usort($array, array("Order", "cmp_price"));
        return $array;
    }

    private function get_orders_by_stock($array, $stock){
        $tp_array=array();
        foreach($array as $tp_order){
            if($tp_order->get_stock()==$stock){
                $tp_array[]=$tp_stock;
            }
        }
        return $tp_array;
    }

    private function get_orders_by_position($array, $position){
        if(is_array($array)){
            $tp_array=array();
            foreach($array as $tp_stock){
                if($tp_stock->get_position()==$position){
                    $tp_array[]=$tp_stock;
                }
            }
        }
        else{
            if($array->get_position()==$position){
                $tp_array=array($array);
            }
        }

        return $tp_array;
    }

    public function execute_market_order(Order &$order){
        $all_orders=$this->orders['market'];
        $tp_orders1=$this->get_orders_by_stock($all_orders, $order->get_stock());
        $tp_orders2=$this->get_orders_by_position($tp_orders1, $order->get_position());
        $pertinents=$this->sort_by_price($tp_orders2);

        $i=0;
        if($order->get_position()=='sell'){
            $pertinents=array_reverse($pertinents);
            while(!$completed){
                if($i==count($pertinents)){
                    $this->add($order);
                    $completed=true;
                    return "NEW-PARTIAL";
                }
                else{
                    $tp_order=$pertinents[$i];
                }
                if($tp_order->get_price()<=$order->get_price()){
                    $compromise=round(($tp_order->get_price()*$tp_order->get_quantity()+$order->get_price()*$order->get_quantity())/($tp_order->get_quantity()+$order->get_quantity()),4);
                    $remaining=$tp_order->get_quantity()-$order->get_quantity();
                    if($remaining=0){
                        //both orders fully executed
                        $order->execute($tp_order->get_quantity(), $compromise);
                        $tp_order->execute($tp_order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                        $completed=true;
                        return "BOTH-FULL";
                    }
                    else if($remaining>0){
                        //old order not fully executed
                        //new order fully executed
                        $order->execute($order->get_quantity(), $compromise);
                        $tp_order->execute($order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                        $completed=true;
                        $new_order=clone($tp_order);
                        $new_order->__set('quantity', $remaining);
                        $this->add($new_order);
                        return "NEW-FULL";
                    }
                    else if($remaining<0){
                        //old order fully executed
                        //new order not fully executed
                        $order->execute($order->get_quantity(), $compromise);
                        $order->__set('quantity', -$remaining);
                        $tp_order->execute($order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                    }
                }
                $i++;
            }
            return true;
        }
        else if($order->get_position()=='buy'){
            while(!$completed){
                if($i==count($pertinents)){
                    $this->add($order);
                    $completed=true;
                    return "NEW-PARTIAL";
                }
                else{
                    $tp_order=$pertinents[$i];
                }
                if($tp_order->get_price()>=$order->get_price()){
                    $compromise=round(($tp_order->get_price()*$tp_order->get_quantity()+$order->get_price()*$order->get_quantity())/($tp_order->get_quantity()+$order->get_quantity()),4);
                    $remaining=$tp_order->get_quantity()-$order->get_quantity();
                    if($remaining=0){
                        //both orders fully executed
                        $order->execute($tp_order->get_quantity(), $compromise);
                        $tp_order->execute($tp_order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                        $completed=true;
                        return "BOTH-FULL";
                    }
                    else if($remaining>0){
                        //old order not fully executed
                        //new order fully executed
                        $order->execute($order->get_quantity(), $compromise);
                        $tp_order->execute($order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                        $completed=true;
                        $new_order=clone($tp_order);
                        $new_order->__set('quantity', $remaining);
                        $this->add($new_order);
                        return "NEW-FULL";
                    }
                    else if($remaining<0){
                        //old order fully executed
                        //new order not fully executed
                        $order->execute($order->get_quantity(), $compromise);
                        $order->__set('quantity', -$remaining);
                        $tp_order->execute($order->get_quantity(), $compromise);
                        $this->delete($tp_order);
                    }
                }
                $i++;
            }
            return true;
        }
        else{
            return false;
        }
    }

    public function display(){
        //market
        $this->load_quotes();
        if(!empty($this->orders)){
            $limit_orders=$this->split_by_stocks();
            //var_dump($limit_orders);
            $str=null;
            foreach($limit_orders as $stack_orders){
                $stock_name=$stack_orders[0]->get_stock();
                $str.="<h3>".$stock_name."</h3>";
                $str.="<table border='1'><tr><th>Bid</th><th>Qty</th><th>Ask</th><th>Stock</th><th>Order</th><th>Time</th></tr>";
                foreach($stack_orders as $tp_order){
                    $str.= "<tr><td>";
                    $str.= ($tp_order->get_position()=='buy') ? "</td><td>".$tp_order->get_quantity()."</td><td>".$tp_order->get_price()."</td>" : $tp_order->get_price()."</td><td>".$tp_order->get_quantity()."</td><td></td>";
                    $str.= "<td>".$tp_order->get_stock()."</td><td>".$tp_order->get_type()."</td><td>".$tp_order->get_timestamp()."</td></tr>";
                }
                $str.="</table>";
            }
        }
        else{
            return FALSE;
        }
        
        echo $str;
    }

    private function load_quotes(){
        $this->orders=null;
        $connexion=DataBase::getInstance();
        //$req=$connexion->prepare("SELECT * FROM orderbook INNER JOIN current_orders ON current_orders.id=orderbook.id_order WHERE orderbook.status=1");
        $req=$connexion->prepare("SELECT * FROM current_orders");
        $req->execute();
        while($row=$req->fetch(PDO::FETCH_ASSOC)){
            $tp_order=new Order($row);
            $this->orders[$tp_order->get_id()]=$tp_order;
        }
    }

    private function split_by_stocks(){
        $limit_orders=array();
        foreach($this->orders as $tp){
            $limit_orders[$tp->get_stock()][]=$tp;
        }
        return $limit_orders;
    }

    private function organize(){
        $limit_orders=$this->split_by_stocks();
        foreach($limit_orders as $stack){
            $stock_name=$stack[0]->get_stock();
            $tp_limit_orders[$stock_name]['buy']=$this->sort_by_price($this->get_orders_by_position($stack,'buy'));
            $tp_limit_orders[$stock_name]['sell']=array_reverse($this->sort_by_price($this->get_orders_by_position($stack,'sell')));
        }
        $this->sorted_orders=$tp_limit_orders;
        //var_dump($this->sorted_orders);
        //exit();
    }

    public function process(){
        $this->load_quotes();
        if(!empty($this->orders)){
            $this->organize();
            $i=0;
            while($i<1){
                foreach($this->orders as $order){
                    $this->execute_order($order);
                }
                $i++;
            }
        }
        else{
            return FALSE;
        }
    }

    private function execute_order(Order &$order){
        $position=($order->get_position()=='buy') ? 'sell' : 'buy' ;
        $orderprice=$order->get_price();
        $qty=$order->get_quantity();
        $stock=$order->get_stock();
        if(isset($this->sorted_orders[$order->get_stock()][$position])){
            $pertinents=$this->sorted_orders[$order->get_stock()][$position];
            if($order->get_position()=='buy'){
                if($pertinents[0]->get_price()>$orderprice){
                    return FALSE;
                }
                else{
                    //prix d'achat supérieur à au moins un prix de vente
                    $completed=false;
                    $i=0;
                    $limit=count($pertinents);
                    while(!$completed){
                        if($order->get_type()=='market'){
                            $pass=true;
                        }
                        elseif($order->get_type()=='limit'){
                            //prix d'achat supérieur au prix de vente
                            if($orderprice>=$pertinents[$i]->get_price()){
                                $pass=true;
                            }
                            else{
                                $pass=false;
                            }
                        }
                        if($pass){
                            $compromise=round(($orderprice+$pertinents[$i]->get_price())/2,4);
                            if($qty>$pertinents[$i]->get_quantity()){
                                $this->suppr($pertinents[$i]);
                                //$this->process_trade($seller,$buyer,$price,$qty);
                                $this->execute_trade($pertinents[$i]->get_id_user(),$order->get_id_user(),$stock,$compromise,$pertinents[$i]->get_quantity());
                                $qty-=$pertinents[$i]->get_quantity();
                                $order->__set('quantity',$qty);
                            }
                            if($qty<$pertinents[$i]->get_quantity()){
                                $completed=true;
                                $this->suppr($order);
                                $this->execute_trade($pertinents[$i]->get_id_user(),$order->get_id_user(),$stock,$compromise,$qty);
                                $new_qty=$pertinents[$i]->get_quantity()-$qty;
                                $pertinents[$i]->__set('quantity',$new_qty);
                                $this->update($pertinents[$i]);
                                return true;
                            }
                            if($qty==$pertinents[$i]->get_quantity()){
                                $completed=true;
                                $this->suppr($order);
                                $this->suppr($pertinents[$i]);
                                $this->execute_trade($pertinents[$i]->get_id_user(),$order->get_id_user(),$stock,$compromise,$qty);
                                return true;
                            }
                        }
                        $i++;
                        if($i==$limit){
                            $this->update($order);
                            $completed=true;
                            return true;
                        }
                    }
                }
            }
            elseif($order->get_position()=='sell'){
                if($pertinents[0]->get_price()<$orderprice){
                    return FALSE;
                }
                else{
                    //prix de vente inférieur à au moins un prix d'achat
                    $completed=false;
                    $i=0;
                    $limit=count($pertinents);
                    while(!$completed){
                        if($order->get_type()=='market'){
                            $pass=true;
                        }
                        elseif($order->get_type()=='limit'){
                            //prix de vente inférieur au prix d'achat
                            if($orderprice<=$pertinents[$i]->get_price()){
                                $pass=true;
                            }
                            else{
                                $pass=false;
                            }
                        }
                        if($pass){
                            $compromise=round(($orderprice+$pertinents[$i]->get_price())/2,4);
                            if($qty>$pertinents[$i]->get_quantity()){
                                $this->suppr($pertinents[$i]);
                                //$this->execute_trade($seller,$buyer,$price,$qty);
                                $this->execute_trade($order->get_id_user(),$pertinents[$i]->get_id_user(),$stock,$compromise,$pertinents[$i]->get_quantity());
                                $qty-=$pertinents[$i]->get_quantity();
                                $order->__set('quantity',$qty);
                            }
                            if($qty<$pertinents[$i]->get_quantity()){
                                $completed=true;
                                $this->suppr($order);
                                $this->execute_trade($order->get_id_user(),$pertinents[$i]->get_id_user(),$stock,$compromise,$qty);
                                $new_qty=$pertinents[$i]->get_quantity()-$qty;
                                $pertinents[$i]->__set('quantity',$new_qty);
                                $this->update($pertinents[$i]);
                                return true;
                            }
                            if($qty==$pertinents[$i]->get_quantity()){
                                $completed=true;
                                $this->suppr($order);
                                $this->suppr($pertinents[$i]);
                                $this->execute_trade($order->get_id_user(),$pertinents[$i]->get_id_user(),$stock,$compromise,$qty);
                                return true;
                            }
                        }
                        $i++;
                        if($i==$limit){
                            $this->update($order);
                            $completed=true;
                            return true;
                        }
                    }
                }
            }
            return TRUE;
        }
        else{
            return FALSE;
        }
        return TRUE;
    }

    private function suppr(Order &$order){
        $this->delete($order);
        $connexion=DataBase::getInstance();
        $req=$connexion->prepare("DELETE FROM current_orders WHERE id=:id_order");
        $req->execute(array('id_order'=>$order->get_id()));
        //$req2=$connexion->prepare("DELETE FROM orderbook WHERE id_order=:id_order");
        //$req2->execute(array('id_order'=>$order->get_id()));
    }

    private function update(Order &$order){
        $connexion=DataBase::getInstance();
        $req=$connexion->prepare("UPDATE current_orders SET quantity=:quantity WHERE id=:id_order");
        $req->execute(array('id_order'=>$order->get_id(),'quantity'=>$order->get_quantity()));
    }

    private function all_stocks(){
        if(!empty($this->orders)){
            $tp_array=array();
            foreach($this->orders as $tp_order){
                $tp_array[]=$tp_order->get_stock();
            }
            return array_unique($tp_array);
        }
    }

    public function execute_trade($id_seller, $id_buyer, $stock, $price, $qty){
        $connexion=DataBase::getInstance();
        $req=$connexion->prepare("INSERT INTO past_orders (stock, quantity, timestamp, id_buyer, id_seller, price) VALUES (:stock, :quantity, :timestamp, :id_buyer, :id_seller, :price)");
        $req->execute(array('stock'=>$stock,'quantity'=>$qty, 'timestamp'=>microtime(true),'id_buyer'=>$id_buyer, 'id_seller'=>$id_seller, 'price'=>$price));
        $amount=$price*$qty;
        $req2=$connexion->prepare("INSERT INTO accounts (id_trader, amount) VALUES (:id, :amount)");
        $req2->execute(array('id'=>$id_seller,'amount'=>$amount));
        $amount=-$price*$qty;
        $req2=$connexion->prepare("INSERT INTO accounts (id_trader, amount) VALUES (:id, :amount)");
        $req2->execute(array('id'=>$id_buyer,'amount'=>$amount));
    }

    public function specify_orderbook_by_stock($stock){
        $all_orders=$this->orders['market'];
        $tp_orders1=$this->get_orders_by_stock($all_orders, $order->get_stock());
        $pertinents=$this->sort_by_price($tp_orders1);
        return $pertinents;
    }

}