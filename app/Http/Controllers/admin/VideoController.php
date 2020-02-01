<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/17
 * Time: 5:16 PM
 */

namespace App\Http\Controllers\admin;


class VideoController extends Controller
{
    /**
     * POST /api.video.add
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
}