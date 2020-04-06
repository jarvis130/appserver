<?php

namespace App\Http\Controllers\v2;

use App\Models\v2\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BannerAdsController extends Controller
{

    /**
    * POST ecapi.ads.list
    */
    public function index(Request $request)
    {
        $rules = [
            'scene' => 'integer|min:1'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        if(empty($scene)){
            $scene = 2;
        }

        $model = Banner::getList($scene);

        return $this->json($model);
    }
}
