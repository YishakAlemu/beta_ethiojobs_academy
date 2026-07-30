<?php

namespace App\Http\Resources;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\EncryptsId;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->encrypted_id,
            'title'               => $this->title,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'content'             => $this->content,
            'video_url'           => $this->video_url,
            'duration_in_seconds' => $this->duration_in_seconds,
            'order'               => $this->order,
            'is_published'        => $this->is_published,
            'is_free_preview'     => $this->is_free_preview,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}