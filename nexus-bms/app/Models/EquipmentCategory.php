<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCategory extends Model
{
    protected $fillable = ['name','name_th','icon','color'];
    public function equipment(): HasMany { return $this->hasMany(Equipment::class, 'category_id'); }
}
