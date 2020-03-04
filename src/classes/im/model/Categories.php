<?php
namespace im\model;

class Categories {

    const DB_TABLE = 'category';

    /**
     * For a given top-level category, return array of subcategory data
     * @param $db
     * @param int $categoryId
     * @return array $results
     *
    */
    public static function getSubcategories(\SqlDb $db, int $categoryId) {

        $results = [];
        $sql = 'SELECT * FROM category WHERE status = "active" AND parent_id = '.$categoryId.' ORDER BY list_order';
        $result=$db->sql_query($sql);
        $parents=$db->sql_fetchrowset($result);

        foreach($parents as $parent) {
            $sql = 'SELECT * FROM category WHERE status = "active" AND parent_id = '.$parent['category_id'].' ORDER BY list_order';
            $result=$db->sql_query($sql);
            $children=$db->sql_fetchrowset($result);
            if ( count($children) > 0 ) {
                $results[] = ['id'=>$parent['category_id'], 'title'=>$parent['category'], 'is_parent'=>true];
                foreach($children as $child) {
                    $results[] = ['id'=>$child['category_id'], 'title'=>$child['category'], 'is_parent'=>false];
                }
            } else {
                $results[] = ['id'=>$parent['category_id'], 'title'=>$parent['category'], 'is_parent'=>false];
            }
        }
        return $results;

    }
}