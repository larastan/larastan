<?php

namespace App;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\User */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'meta'  => json_decode($this->meta),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'has_verified_email' => $this->hasVerifiedEmail(),
        ];
    }
}
