<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    protected $fillable = [
        'code','name','name_th','building_id','floor_id','room_id','category_id',
        'manufacturer','model_number','serial_number','installation_date','warranty_expiry',
        'status','health_score','runtime_hours','ip_address','mac_address',
        'protocol','last_communication','notes'
    ];

    protected $casts = ['installation_date'=>'date','warranty_expiry'=>'date','last_communication'=>'datetime'];

    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function floor(): BelongsTo { return $this->belongsTo(Floor::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function category(): BelongsTo { return $this->belongsTo(EquipmentCategory::class, 'category_id'); }
    public function statusLogs(): HasMany { return $this->hasMany(EquipmentStatusLog::class); }
    public function alarms(): HasMany { return $this->hasMany(Alarm::class); }
    public function schedules(): BelongsToMany { return $this->belongsToMany(Schedule::class, 'schedule_devices'); }

    public function getHealthStatusAttribute(): string
    {
        if ($this->health_score >= 80) return 'healthy';
        if ($this->health_score >= 50) return 'warning';
        return 'critical';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'badge-success',
            'maintenance' => 'badge-warning',
            'offline' => 'badge-secondary',
            'inactive' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }
}
