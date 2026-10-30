<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Helper pembatasan data berdasarkan outlet pengguna (roadmap Prioritas 1.1).
 *
 * Aturan:
 * - Owner/Admin (atau user tanpa outlet_id) => melihat seluruh outlet.
 * - Cashier/Manager => hanya data milik outlet-nya sendiri.
 */
class OutletScope
{
    /**
     * Apakah user saat ini bebas melihat semua outlet?
     */
    public static function isGlobal($user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // User tanpa outlet_id (mis. owner pusat) dianggap global.
        if (empty($user->outlet_id)) {
            return true;
        }

        foreach (['Owner', 'Admin'] as $role) {
            try {
                if ($user->hasRole($role)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Trait HasRoles tidak tersedia — abaikan.
            }
        }

        return false;
    }

    /**
     * Batasi query sesuai outlet user aktif.
     *
     * @param  Builder|QueryBuilder $query
     * @return Builder|QueryBuilder
     */
    public static function apply($query, string $column = 'outlet_id')
    {
        $user = auth()->user();

        if (self::isGlobal($user)) {
            return $query;
        }

        return $query->where($column, $user->outlet_id);
    }

    /**
     * Outlet_id user aktif, atau null bila boleh melihat semua outlet.
     */
    public static function current(): ?int
    {
        $user = auth()->user();

        return self::isGlobal($user) ? null : ($user->outlet_id ?? null);
    }
}
