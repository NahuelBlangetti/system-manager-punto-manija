<?php

namespace App\Filament\Support;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

/**
 * Sobreescribe las notificaciones de fallo de los BulkActions estándar de Filament
 * para evitar llamadas a Number::format(), que requiere la extensión PHP intl.
 */
class BulkActionHelpers
{
    public static function safeDelete(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->failureNotificationTitle(fn (int $successCount, int $totalCount) => $successCount
                ? "Se eliminaron {$successCount} de {$totalCount} registros."
                : "No se pudo eliminar ninguno de los {$totalCount} registros.")
            ->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount) => "Sin permiso para eliminar {$failureCount} registro(s).")
            ->missingBulkProcessingFailureNotificationMessage(fn (int $failureCount) => "Error al procesar {$failureCount} registro(s).");
    }

    public static function safeForceDelete(): ForceDeleteBulkAction
    {
        return ForceDeleteBulkAction::make()
            ->failureNotificationTitle(fn (int $successCount, int $totalCount) => $successCount
                ? "Se eliminaron permanentemente {$successCount} de {$totalCount} registros."
                : "No se pudo eliminar permanentemente ninguno de los {$totalCount} registros.")
            ->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount) => "Sin permiso para eliminar permanentemente {$failureCount} registro(s).")
            ->missingBulkProcessingFailureNotificationMessage(fn (int $failureCount) => "Error al procesar {$failureCount} registro(s).");
    }

    public static function safeRestore(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
            ->failureNotificationTitle(fn (int $successCount, int $totalCount) => $successCount
                ? "Se restauraron {$successCount} de {$totalCount} registros."
                : "No se pudo restaurar ninguno de los {$totalCount} registros.")
            ->missingBulkAuthorizationFailureNotificationMessage(fn (int $failureCount) => "Sin permiso para restaurar {$failureCount} registro(s).")
            ->missingBulkProcessingFailureNotificationMessage(fn (int $failureCount) => "Error al procesar {$failureCount} registro(s).");
    }
}
