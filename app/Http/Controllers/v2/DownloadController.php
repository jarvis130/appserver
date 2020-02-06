<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\v2\Download;

class DownloadController extends Controller
{
    //POST  ecapi.share.download.get
    public function index()
    {
        $data = KefuSettings::getList();

        return $this->json($data);
    }

    //POST ecapi.share.download.insert
    public function insertData()
    {

        $rules = [
            'ip'            => 'required|String|min:1',
            'userId'        => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Download::insertData($this->validated);

        return $this->json($data);
    }

}
