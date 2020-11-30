<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLogEntry extends Model
{
    use HasFactory;

    /**
     * Disable timestamps
     *
     * @var boolean
     */
    public $timestamps = false;
    
    /**
     * All attributes are mass assignable
     *
     * @var array
     */
    protected $guarded = [];
}
