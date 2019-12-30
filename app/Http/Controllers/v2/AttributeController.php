<?php

namespace App\Http\Controllers\v2;

use App\Models\v2\Attribute;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log;

class AttributeController extends Controller
{
    public function getVideoAttribute()
    {
        $data = Attribute::getVideoAttribute();

        return $this->json($data);
    }

}
