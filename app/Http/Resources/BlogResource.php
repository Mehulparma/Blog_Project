<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'image'         => url('storage/' . $this->image),
            'likes_count'   => $this->likes_count ?? 0,
            'is_liked'      => (bool) $this->is_liked,
            'created_by'    => $this->user->name,
            'created_at'    => Carbon::parse($this->created_at)->format('d-m-Y H:i:s'),
        ];
    }
}
