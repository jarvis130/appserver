<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/24
 * Time: 1:56 PM
 */

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\VideoTag;

class VideoTagController extends Controller
{
    /**
     * POST /api.videotag.add
     */
    public function add()
    {
        $rules = [
            'title' => 'string',
            'status' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoTag::add($this->validated);

        return $this->json($data);
    }

    /**
     * POST /api.videotag.edit
     */
    public function edit()
    {
        $rules = [
            'id' => 'required|integer|min:1',
            'title' => 'string',
            'status' => 'required|integer|min:0',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoTag::edit($this->validated);

        return $this->json($data);
    }

    /**
     * Get /api.videotag.info
     */
    public function info()
    {
        $rules = [
            'tagId' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoTag::getInfo($this->validated);

        return $this->json($data);
    }

    /**
     * Get /api.videotag.getList
     */
    public function getList()
    {
//        $rules = [
//            'page'            => 'integer|min:1',
//            'per_page'        => 'required_with:page|integer|min:1',
//        ];
//
//        if ($error = $this->validateInput($rules)) {
//            return $error;
//        }
//
//        extract($this->validated);

        $data = VideoTag::getList();

        return $this->json($data);
    }
}