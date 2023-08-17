<?php

namespace App;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\User */
class UserResourceInherited extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @inheritdoc
     */
    public function toArray($request)
    {
        $array = parent::toArray($request);
        $array['has_verified_email'] = $this->hasVerifiedEmail();

        return $array;
    }
}
