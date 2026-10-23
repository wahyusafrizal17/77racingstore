<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    public const TTL_SECONDS = 120;

    public function touch(User $user): void
    {
        Cache::put($this->cacheKey((int) $user->id), now()->timestamp, self::TTL_SECONDS);
    }

    public function isOnline(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        return Cache::has($this->cacheKey($userId));
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, bool> user_id => online
     */
    public function onlineMapFor(array $userIds): array
    {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $keys = [];
        foreach ($ids as $id) {
            $keys[$this->cacheKey($id)] = $id;
        }

        $cached = Cache::many(array_keys($keys));
        $map = [];
        foreach ($keys as $cacheKey => $userId) {
            $map[$userId] = array_key_exists($cacheKey, $cached) && $cached[$cacheKey] !== null;
        }

        return $map;
    }

    private function cacheKey(int $userId): string
    {
        return 'user_presence:'.$userId;
    }
}
