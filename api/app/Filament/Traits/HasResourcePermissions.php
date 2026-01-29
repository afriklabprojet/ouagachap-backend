<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasResourcePermissions
{
    /**
     * Get the permission name for this resource
     */
    protected static function getResourcePermissionName(): string
    {
        // Convert UserResource to 'users', OrderResource to 'orders', etc.
        $resourceName = class_basename(static::class);
        $name = str_replace('Resource', '', $resourceName);
        return strtolower($name) . 's';
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Super admin can do anything
        if ($user->hasRole('super_admin')) return true;
        
        return $user->can('view_' . static::getResourcePermissionName());
    }

    /**
     * Check if user can view a record
     */
    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        if ($user->hasRole('super_admin')) return true;
        
        return $user->can('create_' . static::getResourcePermissionName());
    }

    /**
     * Check if user can edit a record
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        if ($user->hasRole('super_admin')) return true;
        
        return $user->can('edit_' . static::getResourcePermissionName());
    }

    /**
     * Check if user can delete a record
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        if ($user->hasRole('super_admin')) return true;
        
        return $user->can('delete_' . static::getResourcePermissionName());
    }

    /**
     * Check if user can delete any records (bulk)
     */
    public static function canDeleteAny(): bool
    {
        return static::canDelete(new (static::getModel()));
    }
}
