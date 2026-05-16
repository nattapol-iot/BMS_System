<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRun extends Model
{
    protected $fillable = ['schedule_id','executed_at','action','status','note'];
    protected $casts = ['executed_at' => 'datetime'];
    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
}
