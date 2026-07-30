<?php

namespace App\Http\Resources;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleValue = $this->role?->value ?? $this->role;

        $profileData = null;

        if ($roleValue === UserRole::STUDENT->value) {
            $profileData = [
                'field_of_study' => $this->studentProfile?->field_of_study,
                'institution'    => $this->studentProfile?->institution,
                'bio'            => $this->studentProfile?->bio,
            ];
        } elseif ($roleValue === UserRole::UNIVERSITY->value) {
            $profileData = [
                'website'     => $this->universityProfile?->website,
                'location'    => $this->universityProfile?->location,
                'description' => $this->universityProfile?->description,
                'is_verified' => (bool) ($this->universityProfile?->is_verified ?? false),
            ];
        } elseif ($roleValue === UserRole::ADMIN->value) {
            $profileData = [
                'admin_level' => 'Super Administrator',
            ];
        }
        elseif ($roleValue === UserRole::ADMIN->value) {
    $profileData = [
        'department'   => $this->adminProfile?->department,
        'phone_number' => $this->adminProfile?->phone_number,
        'admin_level'  => $this->adminProfile?->admin_level ?? 'Super Administrator',
    ];
}
        return [
           'id'         => Crypt::encryptString((string) $this->id),
            'name' => $this->name,
            'email' => $this->email,
            'role'       => $this->role?->value ?? $this->role,
            'profile'    => $profileData,
            'created_at' => $this->created_at,
        ];
    }
}