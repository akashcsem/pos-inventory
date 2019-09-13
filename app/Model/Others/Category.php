<?php

namespace App\Model\Others;

use App\Model;

class Category extends Model
{
    protected $table = "categories";

    public function product()
    {
        return $this->hasOne(Product::class);
    }
}
