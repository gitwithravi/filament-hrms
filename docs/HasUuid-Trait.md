# HasUuid Trait Documentation

The `HasUuid` trait provides UUID functionality to Laravel Eloquent models while maintaining the primary key as an auto-incrementing integer `id`. This allows you to use UUIDs for public-facing routes and APIs while keeping the performance benefits of integer primary keys for database operations.

## Features

- ✅ **Automatic UUID Generation**: UUIDs are automatically generated when creating new models
- ✅ **Route Model Binding**: Models are resolved by UUID in routes instead of ID
- ✅ **Primary Key Preservation**: The `id` column remains as the primary key for database performance
- ✅ **Helper Methods**: Convenient methods for finding models by UUID
- ✅ **Fillable Integration**: UUID column is automatically added to fillable attributes

## Installation

### 1. Add the Trait to Your Model

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use HasUuid;
    
    // Your model code...
}
```

### 2. Create Migration for UUID Column

Create a migration to add the UUID column to your table:

```bash
php artisan make:migration add_uuid_to_your_table --table=your_table
```

Update the migration file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add uuid column only if it doesn't exist
        if (!Schema::hasColumn('your_table', 'uuid')) {
            Schema::table('your_table', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Generate UUIDs for existing records
        $records = \DB::table('your_table')->whereNull('uuid')->orWhere('uuid', '')->get();
        
        foreach ($records as $record) {
            \DB::table('your_table')
                ->where('id', $record->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Add unique constraint and index
        $indexExists = collect(\DB::select("SHOW INDEX FROM your_table WHERE Column_name = 'uuid'"))->isNotEmpty();
        
        if (!$indexExists) {
            Schema::table('your_table', function (Blueprint $table) {
                $table->unique('uuid');
                $table->index('uuid');
            });
        }
    }

    public function down(): void
    {
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
```

### 3. Run the Migration

```bash
php artisan migrate
```

## Usage

### Route Model Binding

With the trait applied, your routes will automatically use UUIDs:

```php
// routes/web.php
Route::get('/users/{user}', function (User $user) {
    return $user;
});

// This route will now accept UUIDs like:
// /users/c6b55378-17c7-44c7-ad75-d32c30737eaa
```

### Finding Models by UUID

```php
// Find by UUID
$user = User::findByUuid('c6b55378-17c7-44c7-ad75-d32c30737eaa');

// Find by UUID or fail
$user = User::findByUuidOrFail('c6b55378-17c7-44c7-ad75-d32c30737eaa');

// Using query scope
$users = User::whereUuid('c6b55378-17c7-44c7-ad75-d32c30737eaa')->get();
```

### Getting the UUID

```php
$user = User::first();
echo $user->uuid; // c6b55378-17c7-44c7-ad75-d32c30737eaa
echo $user->getRouteKey(); // c6b55378-17c7-44c7-ad75-d32c30737eaa
```

### Creating New Models

UUIDs are automatically generated:

```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password'
]);

echo $user->uuid; // Automatically generated UUID
```

## API Methods

### Available Methods

| Method | Description |
|--------|-------------|
| `getUuidColumn()` | Returns the UUID column name (default: 'uuid') |
| `getRouteKeyName()` | Returns the route key column name (uses UUID) |
| `findByUuid($uuid)` | Find model by UUID, returns null if not found |
| `findByUuidOrFail($uuid)` | Find model by UUID, throws exception if not found |
| `scopeWhereUuid($query, $uuid)` | Query scope for filtering by UUID |
| `getUuidAttribute()` | Accessor for the UUID attribute |

### Customizing UUID Column Name

You can customize the UUID column name by overriding the `getUuidColumn()` method:

```php
class YourModel extends Model
{
    use HasUuid;
    
    public function getUuidColumn(): string
    {
        return 'custom_uuid_column';
    }
}
```

## Examples

### User Model with HasUuid

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUuid;
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

### Book Model with HasUuid

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasUuid;
    
    protected $fillable = [
        'title',
        'author',
        'description',
    ];
}
```

## Benefits

1. **Security**: UUIDs don't expose sequential IDs that could be enumerated
2. **Performance**: Integer primary keys maintain database performance
3. **Compatibility**: Works with existing Laravel features like route model binding
4. **Flexibility**: Easy to implement on existing models with data
5. **Standards**: Uses Laravel's built-in UUID generation

## Notes

- The primary key remains as `id` (auto-incrementing integer)
- UUIDs are used for public-facing identification (routes, APIs)
- The trait automatically handles UUID generation on model creation
- Existing records get UUIDs assigned during migration
- The UUID column has a unique constraint for data integrity
