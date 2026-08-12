<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Editable overrides for config/business.php.
 *
 * Keys mirror the dot path inside that config file ("phone", "address.city"),
 * so applying a row is just config(['business.'.$key => $value]). Values live in
 * the database rather than .env because the deploy target has a read-only
 * filesystem — see AppServiceProvider::applyBusinessSettings().
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Every stored override, keyed by its config dot path. */
    public static function allValues(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /** Write the given key => value pairs, replacing any existing rows. */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
