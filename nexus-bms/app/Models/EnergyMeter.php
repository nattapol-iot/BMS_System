<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnergyMeter extends Model
{
    protected $fillable = ['code','name','building_id','floor_id','type','unit'];
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function floor(): BelongsTo { return $this->belongsTo(Floor::class); }
    public function logs(): HasMany { return $this->hasMany(EnergyLog::class, 'meter_id'); }
}
