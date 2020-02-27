<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\v2\VirtualCard;
use Log;

class VirtualCardController extends Controller
{
    /**
     * POST /ecapi.virtualcard.use
     */
    public function use()
    {
        $rules = [
            'card_sn'   =>  'required|string',
            'card_password'   =>  'required|string',
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = VirtualCard::use($this->validated);

        return $this->json($data);
    }
}
