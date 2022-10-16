<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessageMongo extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';
    protected $table = 'chat_messages';
    protected $guarded = [];
    protected $softDelete = true;
}
