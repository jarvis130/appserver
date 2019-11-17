<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/17
 * Time: 5:10 PM
 */

namespace App\Http\Controllers\admin;


class UserController extends Controller
{
    /**
     * POST /admin/signin
     */
    public function signin()
    {
        $rules = [
            'username' => 'required|string',
            'password' => 'required|min:6|max:20'
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Member::login($this->validated);
        return $this->json($data);
    }
}