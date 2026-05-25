<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    /**
     * Jalankan callback dalam DB transaction dengan retry otomatis.
     */
    public static function runInTransaction(callable $callback, int $attempts = 1)
    {
        return DB::transaction($callback, $attempts);
    }
}
