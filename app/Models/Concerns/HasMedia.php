<?php

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    public function media(): MorphMany { return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order'); }
    public function mediaFor(string $collection): MorphMany { return $this->media()->where('collection', $collection); }
    public function primaryMedia(string $collection): ?Media { return $this->mediaFor($collection)->orderByDesc('is_primary')->first(); }
}
