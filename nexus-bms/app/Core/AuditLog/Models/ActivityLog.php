<?php
namespace App\Core\AuditLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id','action','module','description','ip_address','user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
