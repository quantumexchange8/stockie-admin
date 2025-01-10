<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'position' => $this->position,
            'role_id' => $this->role_id,
            'image' => $this->getFirstMediaUrl('user'),
            'permission' => $this->hasRole('Super Admin') ? Permission::pluck('name') : $this->getAllPermissions()->pluck('name'),
        ];
    }
}
