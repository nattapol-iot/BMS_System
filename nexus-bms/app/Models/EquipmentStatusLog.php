<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentStatusLog extends Model
{
    protected $fillable = ['equipment_id','status','health_score','value','unit','note','logged_at'];
    protected $casts = ['logged_at' => 'datetime'];
    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }
}
