<?php

namespace Goldoni\LaravelTeams\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasAvatarUrl
{
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? Storage::disk($this->avatarDisk())->url($this->avatar)
            : $this->defaultAvatarUrl();
    }

    protected function avatarDisk(): string
    {
        return config('filesystems.default', 's3');
    }

    protected function defaultAvatarUrl(): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode((string)$this->name) . '&color=7F9CF5&background=EBF4FF';
    }
}
