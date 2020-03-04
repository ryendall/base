<?php
namespace im\model;

class Stock extends Base {

    const DB_TABLE = 'sc_stock';
    const PRIMARY_KEYS = ['stock_id'];
    const DB_LISTS = [
        1 => ['always'=>'Always', 'polled'=>'Polled', 'never'=>'Never'],
    ];

    const DB_MODEL = [
        'stock_id'       => ["type"=>"key"],
        'title'          => ["type"=>"txt", "required"=>true],
        'path'           => ["type"=>"txt"],
        'latest_price'   => ["type"=>"num", 'scale'=>4],
        'price_date'     => ["type"=>"dat"],
        'price_check'    => ["type"=>"txt", "default"=>"polled", "list"=>1],
        'source_id'      => ["type"=>"num"],
        'epic'           => ["type"=>"txt", "required"=>true],
        'previous_price' => ["type"=>"num", 'scale'=>4],
    ];

    public function updatePrice(float $price, $price_date) {

        $stockPrice = new StockPrice($this->container);
        if ( $price != $this->get('latest_price') || $price != $this->get('previous_price') ) {
            // Not the same as the last two prices, add new price record
            $stockPrice->create([
                'stock_id'       => $this->get('stock_id'),
                'price'          => $price,
                'price_date'     => $price_date,
            ]);
        } else {
            // Price same as previous two entries, just update the date of the most recent one
            $query = [
                'stock_id'=>$this->get('stock_id'),
                'price'=>$this->get('latest_price'),
                'price_date'=>$this->get('price_date'),
            ];
            if ( !$stockPrice->findOne($query) ) {
                throw new \Exception('Failed to find existing price: '.json_encode($query).' price:'.$price.' previous:'.$this->get('previous_price'));
            }
            $stockPrice->update(['price_date'=>$price_date]);
        }
        $this->update([
            'previous_price'=> $this->get('latest_price'),
            'latest_price'  => $price,
            'price_date'    => $price_date,
        ]);
    }
}