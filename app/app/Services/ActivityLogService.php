<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(string $logType, string $description, ?array $properties = null): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'log_type' => $logType,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    public static function transactionCreated(int $transactionId, string $note, ?array $properties = null): void
    {
        self::log(
            'transaction_created',
            "Created transaction #{$transactionId}: {$note}",
            $properties,
        );
    }

    public static function transactionUpdated(int $transactionId, array $changes): void
    {
        self::log(
            'transaction_updated',
            "Updated transaction #{$transactionId}",
            $changes,
        );
    }

    public static function transactionDeleted(int $transactionId, array $data): void
    {
        self::log(
            'transaction_deleted',
            "Deleted transaction #{$transactionId}",
            $data,
        );
    }

    public static function categoryCreated(int $categoryId, string $name): void
    {
        self::log(
            'category_created',
            "Created category #{$categoryId}: {$name}",
        );
    }

    public static function categoryUpdated(int $categoryId, string $name, array $changes): void
    {
        self::log(
            'category_updated',
            "Updated category #{$categoryId}: {$name}",
            $changes,
        );
    }

    public static function categoryMerged(int $sourceId, string $sourceName, int $targetId, string $targetName): void
    {
        self::log(
            'category_merged',
            "Merged category #{$sourceId} ({$sourceName}) into #{$targetId} ({$targetName})",
            ['source_id' => $sourceId, 'source_name' => $sourceName, 'target_id' => $targetId, 'target_name' => $targetName],
        );
    }

    public static function categoryDeleted(int $categoryId, string $name): void
    {
        self::log(
            'category_deleted',
            "Deleted category #{$categoryId}: {$name}",
        );
    }

    public static function categoryTypeCreated(int $categoryTypeId, string $name): void
    {
        self::log(
            'category_type_created',
            "Created category type #{$categoryTypeId}: {$name}",
        );
    }

    public static function categoryTypeUpdated(int $categoryTypeId, string $name, array $changes): void
    {
        self::log(
            'category_type_updated',
            "Updated category type #{$categoryTypeId}: {$name}",
            $changes,
        );
    }

    public static function categoryTypeDeleted(int $categoryTypeId, string $name): void
    {
        self::log(
            'category_type_deleted',
            "Deleted category type #{$categoryTypeId}: {$name}",
        );
    }

    public static function accountCreated(int $accountId, string $name): void
    {
        self::log(
            'account_created',
            "Created account #{$accountId}: {$name}",
        );
    }

    public static function accountUpdated(int $accountId, string $name, array $changes): void
    {
        self::log(
            'account_updated',
            "Updated account #{$accountId}: {$name}",
            $changes,
        );
    }

    public static function accountDeleted(int $accountId, string $name): void
    {
        self::log(
            'account_deleted',
            "Deleted account #{$accountId}: {$name}",
        );
    }

    public static function accountTypeCreated(int $accountTypeId, string $name): void
    {
        self::log(
            'account_type_created',
            "Created account type #{$accountTypeId}: {$name}",
        );
    }
}
