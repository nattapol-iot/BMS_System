<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alarm extends Model
{
    protected $fillable = [
        'code','equipment_id','building_id','floor_id','severity','category',
        'description','description_th','recommended_action','assigned_to',
        'status','silenced_until','resolved_at','acknowledged_at','triggered_at'
    ];

    protected $casts = [
        'triggered_at'=>'datetime','silenced_until'=>'datetime',
        'resolved_at'=>'datetime','acknowledged_at'=>'datetime'
    ];

    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function floor(): BelongsTo { return $this->belongsTo(Floor::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function events(): HasMany { return $this->hasMany(AlarmEvent::class); }

    public function getSeverityBadgeClassAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'badge-danger',
            'warning' => 'badge-warning',
            'info' => 'badge-info',
            default => 'badge-secondary',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'badge-danger',
            'acknowledged' => 'badge-warning',
            'silenced' => 'badge-secondary',
            'resolved' => 'badge-success',
            default => 'badge-secondary',
        };
    }
}
