<?php
namespace im\model;

class StockPrice extends Base {

    const DB_TABLE = 'sc_stock_price';
    const PRIMARY_KEYS = ['stock_price_id'];
    const DB_MODEL = [
        'stock_price_id' => ["type"=>"key"],
        'stock_id'       => ["type"=>"num", "required"=>true],
        'price'          => ["type"=>"num", "required"=>true, 'scale'=>4],
        'price_date'     => ["type"=>"dat", "required"=>true],
    ];
}