<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends Model
{
    protected $fillable = [
        'name','building_id','floor_id','category','priority','start_date','end_date',
        'turn_on_time','turn_off_time','timezone','repeat_days','recurrence',
        'holiday_exception','status','created_by'
    ];
    protected $casts = ['start_date'=>'date','end_date'=>'date','repeat_days'=>'array','holiday_exception'=>'boolean'];

    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function floor(): BelongsTo { return $this->belongsTo(Floor::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function devices(): HasMany { return $this->hasMany(ScheduleDevice::class); }
    public function equipment(): BelongsToMany { return $this->belongsToMany(Equipment::class, 'schedule_devices'); }
    public function runs(): HasMany { return $this->hasMany(ScheduleRun::class); }
}
