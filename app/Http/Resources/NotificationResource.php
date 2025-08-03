<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'body'      => $this->body,
            'type'      => $this->type,
            'is_read'   => $this->is_read,
            'is_sent'   => $this->is_sent,
            'sent_at'   => $this->sent_at?->format('Y-m-d H:i'),
            'created_at'=> $this->created_at->format('Y-m-d H:i'),
        ];
    }

}
