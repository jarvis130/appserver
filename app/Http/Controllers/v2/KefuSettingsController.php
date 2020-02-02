<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\v2\Comment;
use App\Models\v2\KefuSettings;
use Illuminate\Http\Request;
use App\Models\v2\Features;
use App\Models\v2\Coupon;

class KefuSettingsController extends Controller
{
    //POST  ecapi.kefu.setting.get
    public function index()
    {

        $data = KefuSettings::getList();

        return $this->json($data);
    }

}
