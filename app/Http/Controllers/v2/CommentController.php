<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\v2\Comment;
use Illuminate\Http\Request;
use App\Models\v2\Features;
use App\Models\v2\Coupon;

class CommentController extends Controller
{
    //POST  ecapi.comment.list
    public function index()
    {
        $rules = [
            'page'          => 'required|integer|min:1',
            'per_page'      => 'required|integer|min:1',
            'status'        => 'integer',
            'product'      => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Comment::getReview($this->validated);

        return $this->json($model);
    }

    //POST  ecapi.comment.create
    public function create()
    {
        $rules = [
            'comment_type'  => 'integer',
            'status'        => 'integer',
            'id_value'      => 'required|integer|min:1',
            'content'       => 'required|String|max:320',
            'comment_rank'       => 'numeric',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Comment::add($this->validated);

        return $this->json($model);
    }

    //POST  ecapi.comment.createRate
    public function createRate()
    {
        $rules = [
            'comment_type'  => 'required|integer',
            'status'        => 'required|integer',
            'id_value'      => 'required|integer|min:1',
            'content'       => 'String|max:320',
            'comment_rank'       => 'Numeric|min:0',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Comment::addRate($this->validated);

        return $this->json($model);
    }

    //POST  ecapi.comment.getInfo
    public function getInfo()
    {
        $rules = [
            'product'      => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Comment::getInfo($this->validated);

        return $this->json($model);
    }
}
