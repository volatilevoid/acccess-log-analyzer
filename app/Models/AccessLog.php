<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory;

    /**
     * Disable timestamps
     */
    public $timestamps = false;

    /**
     * All attributes are mass assignable
     */
    protected $guarded = [];

    /**
     * Get log entries
     */
    public function entries()
    {
        return $this->hasMany('App\Models\AccessLogEntry');
    }
}
