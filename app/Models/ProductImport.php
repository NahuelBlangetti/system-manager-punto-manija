<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImport extends Model
{
    protected $fillable = [
        'user_id',
        'supplier_id',
        'filename',
        'file_path',
        'file_hash',
        'status',
        'products',
        'error_message',
        'product_count',
        'processed_at',
    ];

    protected $casts = [
        'products' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /** Importación lista para revisar en pantalla (done o cancelada con datos). */
    public function isReviewable(): bool
    {
        return $this->isDone()
            || ($this->isCancelled() && ! empty($this->products));
    }

    /** Elimina del panel las notificaciones de "Revisar" de esta importación. */
    public function dismissReviewNotifications(): void
    {
        $user = $this->user;

        if (! $user) {
            return;
        }

        $needle = 'validar-import?id='.$this->id;

        $user->notifications()
            ->where('data', 'like', '%'.$needle.'%')
            ->delete();
    }

    /** Limpia notificaciones de importaciones ya confirmadas o canceladas. */
    public static function dismissResolvedNotificationsFor(User $user): void
    {
        static::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['validated', 'cancelled'])
            ->each(fn (self $import) => $import->dismissReviewNotifications());
    }
}
