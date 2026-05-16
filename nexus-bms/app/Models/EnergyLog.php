<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyLog extends Model
{
    protected $fillable = ['meter_id','value','peak_demand','power_factor','cost','logged_at'];
    protected $casts = ['logged_at' => 'datetime'];
    public function meter(): BelongsTo { return $this->belongsTo(EnergyMeter::class, 'meter_id'); }
}
