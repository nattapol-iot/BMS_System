<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['floor_id','name','name_th','type','area','occupancy_limit'];

    public function floor(): BelongsTo { return $this->belongsTo(Floor::class); }
    public function equipment(): HasMany { return $this->hasMany(Equipment::class); }
}
