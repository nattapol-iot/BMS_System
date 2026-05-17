<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleDevice extends Model
{
    protected $fillable = ['schedule_id','equipment_id','on_time','off_time','days'];
    protected $casts = ['days' => 'array'];
    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }
}
