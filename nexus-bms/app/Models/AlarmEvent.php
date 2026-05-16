<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmEvent extends Model
{
    protected $fillable = ['alarm_id','event_type','performed_by','note'];
    public function alarm(): BelongsTo { return $this->belongsTo(Alarm::class); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}
