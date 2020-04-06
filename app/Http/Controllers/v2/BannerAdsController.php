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
            'type' => 'integer|min:1'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Banner::getAdsList($this->validated);

        return $this->json($model);
    }
}
