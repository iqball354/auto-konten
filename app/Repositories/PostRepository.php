<?php

namespace App\Repositories;

use App\Models\SosialPost;
use App\Models\PostLog;
use App\Models\PostScheduler;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class PostRepository
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get post info dari stored procedure (optimized single query)
     * Menggantikan multiple queries menjadi 1 query
     */
    public function getPostInfo(int $postId, bool $useCache = true)
    {
        $cacheKey = "post_info.{$postId}";
        
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = DB::select("SELECT get_post_info(?) as info", [$postId]);
        
        if (empty($result)) {
            return null;
        }

        $info = json_decode($result[0]->info, true);
        
        if ($useCache && $info) {
            Cache::put($cacheKey, $info, self::CACHE_TTL);
        }
        
        return $info;
    }

    /**
     * Get multiple posts info dengan batch processing
     * Lebih efficient dari loop individual getPostInfo
     */
    public function getMultiplePostsInfo(array $postIds, bool $useCache = true): array
    {
        if (empty($postIds)) {
            return [];
        }

        $results = [];
        $uncachedIds = [];

        // Cek cache terlebih dahulu
        if ($useCache) {
            foreach ($postIds as $postId) {
                $cacheKey = "post_info.{$postId}";
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $results[$postId] = $cached;
                } else {
                    $uncachedIds[] = $postId;
                }
            }
        } else {
            $uncachedIds = $postIds;
        }

        // Query hanya post yang tidak di-cache
        if (!empty($uncachedIds)) {
            $placeholders = implode(',', array_fill(0, count($uncachedIds), '?'));
            $query = "SELECT id, get_post_info(id) as info FROM sosial_post WHERE id IN ({$placeholders})";
            
            $dbResults = DB::select($query, $uncachedIds);
            
            foreach ($dbResults as $row) {
                $info = json_decode($row->info, true);
                if ($info) {
                    $results[$row->id] = $info;
                    
                    if ($useCache) {
                        $cacheKey = "post_info.{$row->id}";
                        Cache::put($cacheKey, $info, self::CACHE_TTL);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get paginated posts with full info (untuk ShowPosting)
     * Menggabungkan pagination dengan getPostInfo
     */
    public function getPaginatedPostsWithInfo(int $userId, array $filters = [], int $perPage = 10)
    {
        $query = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['platform'])) {
            $query->whereJsonContains('platform_targets', $filters['platform']);
        }

        if (isset($filters['tanggal'])) {
            $query->whereDate('created_at', $filters['tanggal']);
        }

        $pagination = $query->latest()->paginate($perPage);
        
        // Get post IDs
        $postIds = $pagination->getCollection()->pluck('id')->all();
        
        if (!empty($postIds)) {
            // Get semua post info dalam satu batch
            $postsInfo = $this->getMultiplePostsInfo($postIds, true);
            
            // Merge info ke collection
            $pagination->getCollection()->transform(function ($post) use ($postsInfo) {
                $post->_info = $postsInfo[$post->id] ?? null;
                return $post;
            });
        }
        
        return $pagination;
    }

    /**
     * @return array<int, PostLog>
     */
    public function getLatestSuccessLogs(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $logs = PostLog::whereIn('post_id', $postIds)
            ->where('status', 'success')
            ->latest('executed_at')
            ->get()
            ->groupBy('post_id');

        $latestLogs = [];

        foreach ($logs as $postId => $postLogGroup) {
            $latestLogs[(int) $postId] = $postLogGroup->first();
        }

        return $latestLogs;
    }

    /**
     * @return LengthAwarePaginator<PostLog>
     */
    public function getRiwayatData(int $userId, array $filters): LengthAwarePaginator
    {
        $query = PostLog::join('sosial_post', 'post_logs.post_id', '=', 'sosial_post.id')
            ->where('sosial_post.user_id', $userId)
            ->whereNull('sosial_post.deleted_at')
            ->select('post_logs.*')
            ->with(['post.media', 'schedule.akunSosial'])
            ->latest('executed_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tanggal'])) {
            $query->whereDate('executed_at', $filters['tanggal']);
        }

        if (!empty($filters['platform'])) {
            $query->whereHas('schedule.akunSosial', function ($q) use ($filters): void {
                $q->where('platform', $filters['platform']);
            });
        }

        return $query->paginate(10);
    }

    public function getLogDetail(int $userId, int $logId): PostLog
    {
        return PostLog::join('sosial_post', 'post_logs.post_id', '=', 'sosial_post.id')
            ->where('sosial_post.user_id', $userId)
            ->whereNull('sosial_post.deleted_at')
            ->select('post_logs.*')
            ->with(['post.media', 'schedule.akunSosial'])
            ->findOrFail($logId);
    }

    /**
     * @return Collection<int, PostScheduler>
     */
    public function getCalendarSchedules(int $userId): Collection
    {
        return PostScheduler::join('post_detail', 'post_scheduler.detail_id', '=', 'post_detail.id')
            ->join('sosial_post', 'post_detail.post_id', '=', 'sosial_post.id')
            ->where('sosial_post.user_id', $userId)
            ->whereNull('sosial_post.deleted_at')
            ->select('post_scheduler.*')
            ->with(['detail.post:id,user_id,caption,status,publish_type,platform_targets', 'akunSosial:id,platform,username'])
            ->where('status', '!=', 'failed')
            ->get();
    }

    /**
     * Get post untuk publishing dengan optimasi maksimal
     * Menggantikan 4+ queries di PublishPostJob
     */
    public function getPostForPublishing(int $postId)
    {
        $info = $this->getPostInfo($postId, true);
        
        if (!$info) {
            return null;
        }

        // Info sudah berisi semua data yang dibutuhkan:
        // - post data
        // - media array
        // - detail
        // - scheduler
        // - latest error
        
        return $info;
    }

    /**
     * Invalidate cache untuk post
     */
    public function invalidateCache(int $postId)
    {
        Cache::forget("post_info.{$postId}");
    }

    /**
     * Invalidate cache untuk multiple posts
     */
    public function invalidateMultipleCache(array $postIds)
    {
        foreach ($postIds as $postId) {
            $this->invalidateCache($postId);
        }
    }

    /**
     * Get post errors (optimized version)
     * Mendapatkan latest error untuk posts
     */
    public function getPostsErrors(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        // Menggunakan getPostInfo yang sudah include latest_error
        $postsInfo = $this->getMultiplePostsInfo($postIds, true);
        
        $errors = [];
        foreach ($postsInfo as $postId => $info) {
            if (isset($info['latest_error']) && !empty($info['latest_error'])) {
                $errors[$postId] = $info['latest_error'];
            }
        }
        
        return $errors;
    }
}
