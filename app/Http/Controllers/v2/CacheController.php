<?php

namespace App\Http\Controllers\v2;

use App\Models\v2\Caches;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CacheController extends Controller
{
    public function refresh()
    {
        $rules = [
            'code' => 'required|string',
            'attrid' => 'string'
        ];
        if ($error = $this->validateInput($rules)) {
            return $error;
        }
        $data = Caches::refreshCache($this->validated);
        return $this->json($data);
    }
}
