<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class CacheService
{
    /**
     * Cache duration in seconds.
     */
    const CACHE_DURATION = 3600; // 1 hour
    const CACHE_SHORT_DURATION = 300; // 5 minutes
    const CACHE_LONG_DURATION = 86400; // 24 hours

    /**
     * Cache tags for better cache management.
     */
    const TAG_EMPLOYEES = 'employees';
    const TAG_VACATIONS = 'vacations';
    const TAG_ARCHIVES = 'archives';
    const TAG_LOOKUPS = 'lookups';
    const TAG_USERS = 'users';

    /**
     * Get cached data or execute callback and cache result.
     *
     * @param string $key
     * @param callable $callback
     * @param int $duration
     * @param array $tags
     * @return mixed
     */
    public function remember(string $key, callable $callback, int $duration = self::CACHE_DURATION, array $tags = [])
    {
        if (empty($tags)) {
            return Cache::remember($key, $duration, $callback);
        }

        return Cache::tags($tags)->remember($key, $duration, $callback);
    }

    /**
     * Clear cache by key.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache by tags.
     *
     * @param array $tags
     * @return bool
     */
    public function flushTags(array $tags): bool
    {
        Cache::tags($tags)->flush();
        return true;
    }

    /**
     * Cache lookup tables (sections, types, etc.)
     *
     * @param string $model
     * @param string $orderBy
     * @return Collection
     */
    public function cacheLookupTable(string $model, string $orderBy = 'name'): Collection
    {
        $key = "lookup_{$model}";
        
        return $this->remember(
            $key,
            function () use ($model, $orderBy) {
                $modelClass = "App\\Models\\{$model}";
                return $modelClass::orderBy($orderBy)->get();
            },
            self::CACHE_LONG_DURATION,
            [self::TAG_LOOKUPS]
        );
    }

    /**
     * Cache employee list.
     *
     * @return Collection
     */
    public function cacheEmployeeList(): Collection
    {
        return $this->remember(
            'employees_list',
            function () {
                return \App\Models\Employee::select('id', 'name', 'employee_number', 'section_id')
                    ->where('is_person', true)
                    ->orderBy('name')
                    ->get();
            },
            self::CACHE_DURATION,
            [self::TAG_EMPLOYEES]
        );
    }

    /**
     * Invalidate employee cache.
     *
     * @return bool
     */
    public function invalidateEmployeeCache(): bool
    {
        return $this->flushTags([self::TAG_EMPLOYEES]);
    }

    /**
     * Invalidate vacation cache.
     *
     * @return bool
     */
    public function invalidateVacationCache(): bool
    {
        return $this->flushTags([self::TAG_VACATIONS]);
    }

    /**
     * Invalidate archive cache.
     *
     * @return bool
     */
    public function invalidateArchiveCache(): bool
    {
        return $this->flushTags([self::TAG_ARCHIVES]);
    }

    /**
     * Invalidate lookup tables cache.
     *
     * @return bool
     */
    public function invalidateLookupCache(): bool
    {
        return $this->flushTags([self::TAG_LOOKUPS]);
    }

    /**
     * Invalidate user cache.
     *
     * @return bool
     */
    public function invalidateUserCache(): bool
    {
        return $this->flushTags([self::TAG_USERS]);
    }

    /**
     * Clear all application cache.
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        return Cache::flush();
    }

    /**
     * Get cache statistics (if using Redis or similar).
     *
     * @return array
     */
    public function getStats(): array
    {
        // This would depend on your cache driver
        // For demo purposes, return basic info
        return [
            'driver' => config('cache.default'),
            'tags_support' => config('cache.default') !== 'file',
        ];
    }
}
