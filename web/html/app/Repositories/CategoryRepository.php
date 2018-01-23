<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Category;

class CategoryRepository extends Repository {
    
    protected $modelClass = Category::class;
}