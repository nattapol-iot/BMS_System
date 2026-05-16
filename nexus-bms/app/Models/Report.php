<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = ['name','type','parameters','generated_by','file_path','format','status'];
    protected $casts = ['parameters' => 'array'];
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
}
