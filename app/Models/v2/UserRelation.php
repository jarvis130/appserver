<?php

namespace App\Models\v2;

use App\Models\BaseModel;

class UserRelation extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'user_relation';
    public $timestamps = false;
    protected $guarded = [];

}
