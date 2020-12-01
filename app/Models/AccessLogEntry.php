<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLogEntry extends Model
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
     * Get the log that owns the entry
     */
    public function accessLog()
    {
        return $this->belongsTo('App\Models\AccessLog');
    }
}
