<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/24
 * Time: 1:56 PM
 */

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\VideoCategory;

class VideoCategoryController extends Controller
{
    /**
     * POST /api.videocategory.add
     *
     */
    public function add()
    {
        $rules = [
            'title' => 'required|string',
            'parent_id' => 'required|integer|min:0',
            'is_delete' => 'required|integer|min:0',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoCategory::add($this->validated);

        return $this->json($data);
    }

    /**
     * POST /api.videocategory.edit
     */
    public function edit()
    {
        $rules = [
            'id' => 'required|integer|min:1',
            'title' => 'string',
            'parent_id' => 'required|integer|min:0',
            'is_delete' => 'required|integer|min:0',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoCategory::edit($this->validated);

        return $this->json($data);
    }

    /**
     * Get /api.videocategory.info
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

        $data = VideoCategory::getInfo($this->validated);

        return $this->json($data);
    }

    /**
     * Get /api.videocategory.getListByParentId
     */
    public function getListByParentId()
    {
        $rules = [
            'parent_id'            => 'required|integer|min:0'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        extract($this->validated);

        $data = VideoCategory::getListByParentId($this->validated);

        return $this->json($data);
    }

    /**
     * Get /api.videocategory.getList
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

        $data = VideoCategory::getList();

        return $this->json($data);
    }
}