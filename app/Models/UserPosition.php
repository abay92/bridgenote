<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ksr\Services\OnQuery;

class UserPosition extends Model
{
    use OnQuery;
    
    protected $table = 'user_positions';

    protected $primary_key = null;
    
    public $incrementing = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'status',
        'position'
    ];

    /**
     * The attributes for sorting.
     *
     * @var array
     */
    protected $sortable = [
        'user_id' => 'user_id',
        'status' => 'status',
        'position' => 'position'
    ];

    /**
     * The attributes for searching.
     *
     * @var array
     */
    protected $searchable = [
        'position' => 'position'
    ];
}
