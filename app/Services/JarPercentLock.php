<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

/**
 * Per-user mutex for jar mutations.
 *
 * Protects the invariant "sum of active percent-type jars <= 100%" against
 * concurrent requests that follow a read-sum -> validate -> write pattern.
 *
 * Without this lock, two simultaneous saves can each read the same partial sum
 * (e.g. 80%) and both write a new 30% jar, persisting a total of 140%.
 *
 * Backed by the atomic "database" cache lock (see the `cache_locks` table).
 * Every controller entry point that validates the percent total and then
 * writes jars MUST wrap that region in JarPercentLock::withUserLock().
 */
class JarPercentLock
{
    /** How long the lock is considered owned (auto-released after this). */
    private const TTL_SECONDS = 20;

    /** How long to block waiting for a concurrent same-user mutation to finish. */
    private const WAIT_SECONDS = 5;

    /**
     * Acquire the per-user jar lock, run $work, then release.
     *
     * The caller owns its own DB transaction inside $work; this method only
     * serializes access so two same-user requests cannot interleave their
     * read-sum and write.
     *
     * @param int      $userId
     * @param callable $work  Returns a Response or arbitrary value.
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public static function withUserLock(int $userId, callable $work): mixed
    {
        return Cache::lock(self::key($userId), self::TTL_SECONDS)
            ->block(self::WAIT_SECONDS, function () use ($work) {
                return $work();
            });
    }

    public static function key(int $userId): string
    {
        return "owf:jars-percent:u:{$userId}";
    }

    /**
     * Acquire the per-user jar lock manually. The caller MUST release it
     * (typically via try/finally). Prefer withUserLock() for short regions.
     *
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public static function acquire(int $userId): Lock
    {
        $lock = Cache::lock(self::key($userId), self::TTL_SECONDS);
        $lock->block(self::WAIT_SECONDS);

        return $lock;
    }
}
