<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Category;

class CategoryRepository extends Repository {
    
    protected $modelClass = Category::class;

    public function search(array $array = [], $collumns = ["*"]) {
        $categories = $this->newQuery();
        if(isset($array['type']) && $array['type'] === 'all') {
            $categories = $categories->withTrashed();
        }
        if(isset($array['term'])) {
            $categories = $categories->where('name','like','%'.$array['term'].'%');
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $categories->count()];
        }

        $categories = $categories->orderBy('created_at', 'desc');

        $categories = $categories->select($collumns);

        return $this->doQuery($categories, $take);
    }
}