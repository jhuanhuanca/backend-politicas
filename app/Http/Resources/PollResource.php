<?php

namespace App\Http\Resources;

use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Poll
 */
class PollResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'badge' => $this->badge,
            'place' => $this->place,
            'category' => $this->category,
            'voted_option_id' => $this->relationLoaded('votes') ? $this->votes->first()?->poll_option_id : null,
            'options' => $this->relationLoaded('options')
                ? $this->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'votes' => $option->votes_count,
                ])->values()->all()
                : [],
        ];
    }
}
