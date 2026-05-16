<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    protected $fillable = ['building_id','floor_number','name','name_th','area','floor_plan_image'];

    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
    public function rooms(): HasMany { return $this->hasMany(Room::class); }
    public function equipment(): HasMany { return $this->hasMany(Equipment::class); }
    public function alarms(): HasMany { return $this->hasMany(Alarm::class); }
}
