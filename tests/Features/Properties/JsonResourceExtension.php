<?php

namespace Tests\Features\Properties;

use App\User;
use App\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonResourceExtension
{
    /**
     * @test
     * @return JsonResource<User>
     */
    public function resources_proxy_to_underlying_model_type(): JsonResource
    {
        $user = new User();
        $resource = new UserResource($user);
        $array = $resource->toArray(new Request());
        $id = $resource->id;
        $hasVerifiedEmail = $resource->hasVerifiedEmail();

        return $resource;
    }

    /** @test */
    public function resource_property_types_are_correct(): int
    {
        $user = new User();
        $resource = new UserResource($user);

        return $resource->id;
    }
}
