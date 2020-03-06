<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\v2\ActorAttention;
use App\Models\v2\Actors;
use Illuminate\Http\Request;
use App\Models\v2\Version;

class ActorController extends Controller
{
    /**
     * POST /ecapi.actor.list
     */
    public function index()
    {
        $rules = [
            'page'            => 'integer|min:1',
            'per_page'        => 'required_with:page|integer|min:1',
            'country'         => 'string|min:1',
            'name_initial'    => 'string|min:1|max:1',
            'keyword'         => 'string|min:1',
            'sort_key'        => 'string|min:1',
            'sort_value'      => 'required_with:sort_key|string|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Actors::getList($this->validated);

        return $this->json($data);
    }

    /**
     * POST ecapi.actor.getvideolistbyactorid
     */
    public function getVideoListByActorId()
    {
        $rules = [
            'page'     => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
            'actor_id'  => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = Actors::getVideoListByActorId($this->validated);
        return $this->json($data);
    }

    /**
     * POST /ecapi.actor.setAttention
     */
    public function setAttention()
    {
        $rules = [
            'actor_id' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = ActorAttention::setAttention($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.actor.getAttention
     */
    public function getAttention()
    {
        $rules = [
            'actor_id' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = ActorAttention::getAttention($this->validated);

        return $this->json($data);
    }

    /**
     * POST /ecapi.actor.attentioned.list
     */
    public function attentionedList()
    {
        $rules = [
            'page'            => 'required|integer|min:1',
            'per_page'        => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $data = ActorAttention::getList($this->validated);

        return $this->json($data);
    }

}
