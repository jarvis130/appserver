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
        $model = Banner::getAdsList();

        return $this->json($model);
    }
}
