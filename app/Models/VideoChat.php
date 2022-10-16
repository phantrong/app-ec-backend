<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class VideoChat extends CoreModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dtb_video_chats';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'calendar_staff_id',
        'user_id',
        'type',
        'comment',
    ];
}
