<?php

namespace App;

enum RolesEnum: string
{
    case SYSTEM_ADMIN = 'system-administrator';
    case ASSOCIATION_ADMIN = 'association-administrator';
    case VENDOR = 'vendor';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_ADMIN => 'System Administrator',
            self::ASSOCIATION_ADMIN => 'Association Administrator',
            self::VENDOR => 'Vendor',
            self::CUSTOMER => 'Customer',
        };
    }
}
