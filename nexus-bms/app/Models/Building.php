<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Building extends Model
{
    protected $fillable = [
        'code','name','name_th','address','city','country','floors_count',
        'total_area','year_built','image','status','latitude','longitude',
        'occupancy_count','occupancy_capacity','created_by'
    ];

    public function floors(): HasMany { return $this->hasMany(Floor::class); }
    public function equipment(): HasMany { return $this->hasMany(Equipment::class); }
    public function alarms(): HasMany { return $this->hasMany(Alarm::class); }
    public function energyMeters(): HasMany { return $this->hasMany(EnergyMeter::class); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getOccupancyPercentAttribute(): int
    {
        if ($this->occupancy_capacity <= 0) return 0;
        return (int) round(($this->occupancy_count / $this->occupancy_capacity) * 100);
    }

    public function getActiveAlarmsCountAttribute(): int
    {
        return $this->alarms()->whereIn('status', ['active','acknowledged'])->count();
    }
}
