<?php
/**
 * Created by PhpStorm.
 * User: jarvis
 * Date: 2019/11/17
 * Time: 5:10 PM
 */

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\AdminUser;
use Illuminate\Http\Request;
use App\Helper\Token;
use Cookie;

class AdminUserController extends Controller
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

        $data = AdminUser::login($this->validated);
        return $this->json($data);
    }

    /**
     * Get api.auth.getUserInfo
     */
    public function getUserInfo()
    {

        $data = AdminUser::getUserInfo();
        return $this->json($data);
    }
}