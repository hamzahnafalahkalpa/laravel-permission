# CLAUDE.md

This file provides guidance to Claude Code when working with the `hanafalah/laravel-permission` package.

## Package Overview

Laravel Permission is a role-based access control (RBAC) package for Laravel applications. It provides a hierarchical permission system with support for roles, permissions, menus, and modules. The package integrates with the Wellmed multi-tenant healthcare system through the `hanafalah/laravel-support` base package.

**Namespace:** `Hanafalah\LaravelPermission`

**Dependencies:**
- `hanafalah/laravel-support` (dev-main)

## Directory Structure

```
src/
├── Commands/              # Artisan commands
│   ├── EnvironmentCommand.php
│   └── InstallMakeCommand.php
├── Concerns/              # Traits for models
│   ├── HasPermission.php  # Attach to models that need permissions
│   ├── HasRole.php        # Attach to models that need roles
│   ├── PermissionMutator.php
│   └── RoleMutator.php
├── Contracts/             # Interfaces
│   ├── Data/
│   ├── Schemas/
│   └── LaravelPermission.php
├── Data/                  # DTOs using Spatie Laravel Data
│   ├── PermissionData.php
│   └── RoleData.php
├── Enums/                 # Permission enums
│   └── Permission/
│       ├── Access.php     # DENY (0), ALLOW (1)
│       ├── Type.php       # MENU, NAVIGATION, MODULE, PERMISSION
│       └── Visibility.php # VISIBLE (1), INVISIBLE (0)
├── Facades/
│   └── LaravelPermission.php
├── Models/
│   ├── Permission/
│   │   ├── Menu.php           # Menu-specific permission (extends Permission)
│   │   ├── ModelHasPermission.php  # Pivot table
│   │   └── Permission.php
│   └── Role/
│       ├── ModelHasRole.php   # Pivot table (polymorphic)
│       ├── Role.php
│       └── RoleHasPermission.php  # Pivot table
├── Providers/
│   └── CommandServiceProvider.php
├── Resources/             # API Resources
│   ├── Permission/
│   │   ├── ViewMenu.php
│   │   └── ViewPermission.php
│   └── Role/
│       ├── ShowRole.php
│       └── ViewRole.php
├── Schemas/               # Business logic layer
│   ├── Menu.php
│   ├── Module.php
│   ├── Permission.php
│   └── Role.php
├── Supports/
│   └── BaseLaravelPermission.php
├── LaravelPermission.php  # Main service class
└── LaravelPermissionServiceProvider.php

assets/
├── config/
│   └── config.php         # Package configuration
└── database/
    └── migrations/        # Database migrations
```

## Key Classes

### Models

**Role** (`Models/Role/Role.php`)
- Uses ULID as primary key
- Supports soft deletes
- Has `HasPermission` trait for permission management
- Relationships: `permissions`, `modelHasRoles`, `roleHasPermissions`

**Permission** (`Models/Permission/Permission.php`)
- Uses ULID as primary key
- Supports hierarchical structure via `parent_id`
- Types: `MENU`, `NAVIGATION`, `MODULE`, `PERMISSION`
- Auto-creates route metadata on creation (method, slug, prefix)
- Scopes: `asModule()`, `asMenu()`, `asPermission()`, `showInAcl()`, `showInData()`

**Menu** (`Models/Permission/Menu.php`)
- Extends Permission model
- Specifically for menu-type permissions
- Uses same `permissions` table

### Pivot Models

- **ModelHasRole** - Polymorphic pivot connecting any model to roles
- **ModelHasPermission** - Polymorphic pivot for direct model-permission assignments
- **RoleHasPermission** - Connects roles to permissions

### Traits

**HasRole** (`Concerns/HasRole.php`)
Add to any Eloquent model that needs role functionality:
```php
use Hanafalah\LaravelPermission\Concerns\HasRole;

class User extends Model {
    use HasRole;
}
```

Methods available:
- `roles()` - BelongsToMany relationship
- `role()` - HasOneThrough (current active role)
- `syncRoles(array $roles)` - Replace all roles
- `addRole($role, $attributes)` - Add a role
- `removeRole($role)` - Remove a role
- `flushRoles()` - Remove all roles
- `switchRoleTo($role)` - Switch active role
- `hasRole($role)` - Check if has role
- `hasRoles(array $roles)` - Check if has all roles

**HasPermission** (`Concerns/HasPermission.php`)
Add to any model that needs direct permission assignment:
```php
use Hanafalah\LaravelPermission\Concerns\HasPermission;

class Role extends Model {
    use HasPermission;
}
```

Methods available:
- `permissions()` - BelongsToMany relationship
- `syncPermissions(array $permissions)` - Replace all permissions
- `syncPermissionsById(array $ids)` - Sync by permission IDs
- `addPermission($permission)` - Add a permission
- `removePermission($permission)` - Remove a permission
- `flushPermissions()` - Remove all permissions
- `hasPermission($permission)` - Check if has permission
- `hasPermissions(array $permissions)` - Check if has all permissions

### Enums

**Type** (`Enums/Permission/Type.php`)
```php
enum Type: string {
    case MENU       = 'MENU';       // Menu items in UI
    case NAVIGATION = 'NAVIGATION'; // Navigation elements
    case MODULE     = 'MODULE';     // Feature modules
    case PERMISSION = 'PERMISSION'; // Action permissions
}
```

**Access** (`Enums/Permission/Access.php`)
```php
enum Access: int {
    case DENY  = 0;
    case ALLOW = 1;
}
```

**Visibility** (`Enums/Permission/Visibility.php`)
```php
enum Visibility: int {
    case VISIBLE   = 1;
    case INVISIBLE = 0;
}
```

### Service Class

**LaravelPermission** (`LaravelPermission.php`)
Main service class accessible via facade:
```php
use Hanafalah\LaravelPermission\Facades\LaravelPermission;

// Scan and import permissions from PHP files
LaravelPermission::scanPermissions('/path/to/permissions');

// Scan and import roles from PHP files
LaravelPermission::scanRoles('/path/to/roles');

// Set guard for API or web
LaravelPermission::setForApi(true);  // 'api' guard
LaravelPermission::setForApi(false); // 'web' guard
```

### Schema Classes (Business Logic)

**Role Schema** (`Schemas/Role.php`)
```php
// Store/update role
$roleSchema->prepareStoreRole(RoleData $dto);

// Query builder
$roleSchema->role($conditionals);

// Show with permissions
$roleSchema->showRole($model);
```

**Permission Schema** (`Schemas/Permission.php`)
```php
// Store permissions from array
$permissionSchema->prepareStorePermission($attributes);

// View permission list (hierarchical)
$permissionSchema->prepareViewPermissionList();

// Show single permission
$permissionSchema->prepareShowPermission($model);
```

## Installation

```bash
php artisan laravel-permission:install
```

This publishes:
- Config file to `config/laravel-permission.php`
- Migrations to `database/migrations/`

## Database Schema

### roles
| Column     | Type         | Description           |
|------------|--------------|----------------------|
| id         | ULID (PK)    | Primary key          |
| parent_id  | ULID (FK)    | Self-referencing     |
| name       | varchar(100) | Role name            |
| props      | JSON         | Additional properties|
| created_at | timestamp    |                      |
| updated_at | timestamp    |                      |
| deleted_at | timestamp    | Soft delete          |

### permissions
| Column     | Type          | Description                    |
|------------|---------------|--------------------------------|
| id         | ULID (PK)     | Primary key                    |
| parent_id  | ULID (FK)     | Self-referencing for hierarchy |
| name       | varchar(200)  | Display name                   |
| alias      | varchar(255)  | Route name / identifier        |
| type       | enum          | MENU, NAVIGATION, MODULE, PERMISSION |
| visibility | smallint      | 1=visible, 0=hidden            |
| ordering   | mediumint     | Sort order                     |
| guard_name | varchar(50)   | 'api' or 'web'                 |
| props      | JSON          | Additional properties          |

### model_has_roles
| Column     | Type         | Description                |
|------------|--------------|----------------------------|
| id         | ULID (PK)    | Primary key                |
| model_type | varchar      | Polymorphic type           |
| model_id   | string       | Polymorphic ID             |
| role_id    | ULID (FK)    | Reference to roles         |
| current    | timestamp    | Marks active/current role  |

### role_has_permissions
| Column        | Type      | Description            |
|---------------|-----------|------------------------|
| id            | bigint    | Primary key            |
| role_id       | ULID (FK) | Reference to roles     |
| permission_id | ULID (FK) | Reference to permissions|

### model_has_permissions
| Column        | Type      | Description            |
|---------------|-----------|------------------------|
| id            | bigint    | Primary key            |
| model_type    | varchar   | Polymorphic type       |
| model_id      | string    | Polymorphic ID         |
| permission_id | ULID (FK) | Reference to permissions|

## Usage Patterns

### Assigning Roles to Users
```php
// Add single role
$user->addRole('Admin');

// Sync multiple roles (replaces existing)
$user->syncRoles(['Admin', 'Doctor']);

// Remove role
$user->removeRole('Admin');

// Switch active role
$user->switchRoleTo('Doctor');

// Check role
if ($user->hasRole('Admin')) {
    // ...
}
```

### Managing Role Permissions
```php
// Get role
$role = Role::where('name', 'Admin')->first();

// Sync permissions by alias
$role->syncPermissions([
    'dashboard.index',
    'users.index',
    'users.store',
    'users.update'
]);

// Sync by IDs
$role->syncPermissionsById(['01HX...', '01HY...']);

// Add single permission
$role->addPermission('reports.export');

// Check permission
if ($role->hasPermission('users.delete')) {
    // ...
}
```

### Hierarchical Permissions
Permissions support wildcard matching for hierarchical structures:
```php
// Grant all user permissions
$role->syncPermissions(['users.*']);

// This includes: users.index, users.store, users.update, users.delete, etc.
```

### Permission File Scanning
The package can scan PHP files to auto-import permissions:
```php
// Permission file format (permissions/dashboard.php):
return [
    'type' => 'MODULE',
    'alias' => 'dashboard',
    'name' => 'Dashboard',
    'show_in_acl' => true,
    'childs' => [
        [
            'type' => 'MENU',
            'alias' => 'overview',
            'name' => 'Overview',
            'show_in_acl' => true,
            'childs' => [
                ['type' => 'PERMISSION', 'alias' => 'index', 'name' => 'View'],
                ['type' => 'PERMISSION', 'alias' => 'export', 'name' => 'Export'],
            ]
        ]
    ]
];

// Scan and import
LaravelPermission::scanPermissions(base_path('permissions'));
```

### Role File Scanning
```php
// Role file format (roles/Admin.php):
return [
    'dashboard.*',
    'users.*',
    'settings.index',
];

// Scan and import (creates role named "Admin" from filename)
LaravelPermission::scanRoles(base_path('roles'));
```

## Configuration

The package configuration (`config/laravel-permission.php`) defines:
- Namespace mapping
- Library paths (models, contracts, schemas, etc.)
- Command registration
- Policy mapping

To customize models, add to your application's `config/database.php`:
```php
'models' => [
    'Role' => \App\Models\CustomRole::class,
    'Permission' => \App\Models\CustomPermission::class,
],
```

## Integration with Wellmed

This package is used throughout the Wellmed multi-tenant healthcare system:
- Roles are tenant-scoped (each tenant has its own roles)
- Permissions define access to features, menus, and API endpoints
- The `guard_name` typically uses 'api' for backend services
- Props JSON field stores additional metadata like `show_in_acl`, `is_restricted`

## Common Pitfalls

1. **ULID Keys** - All primary keys use ULIDs, not auto-incrementing integers
2. **Polymorphic Relations** - `model_has_roles` and `model_has_permissions` are polymorphic
3. **Visibility Scope** - Permissions have a default global scope filtering `visibility = 1`
4. **Guard Name** - Always specify guard ('api' or 'web') when creating permissions
5. **Hierarchical Aliases** - Permission aliases use dot notation (e.g., `users.store`)
