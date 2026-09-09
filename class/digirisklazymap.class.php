<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/digirisklazymap.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for a keyed collection whose entries are loaded on first access
 */

/**
 * Read-only keyed collection that loads each entry the first time it is read.
 *
 * Risk lists used to preload every user and every risk assessment of the database only to
 * display the handful of them the rows on screen actually reference, which costs a query per
 * user and instantiates the whole riskassessment table on every page. This collection keeps
 * the `$map[$key]` syntax the templates already use, but calls its loader only for the keys
 * that are really read, and only once per key.
 */
class DigiriskLazyMap implements ArrayAccess
{
    /**
     * @var callable Loader receiving the requested key and returning the value to memoize
     */
    private $loader;

    /**
     * @var array Values already loaded, keyed by the key they were requested with
     */
    private array $loaded = [];

    /**
     * Constructor
     *
     * @param callable $loader Called with the requested key, returns the value to memoize
     */
    public function __construct(callable $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Return the value for a key, loading it on first access
     *
     * @param  mixed $offset Key to read
     * @return mixed         Value returned by the loader
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $key = is_scalar($offset) ? (string) $offset : '';

        if (!array_key_exists($key, $this->loaded)) {
            $this->loaded[$key] = call_user_func($this->loader, $offset);
        }

        return $this->loaded[$key];
    }

    /**
     * Tell whether a key holds a value, loading it on first access
     *
     * The loader is expected to return an empty value rather than to fail, so a key is
     * "set" when its loaded value is not null.
     *
     * @param  mixed $offset Key to test
     * @return bool          True when the loaded value is not null
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    /**
     * Store a value for a key, bypassing the loader
     *
     * @param  mixed $offset Key to write, null to append
     * @param  mixed $value  Value to memoize
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->loaded[] = $value;
        } else {
            $this->loaded[is_scalar($offset) ? (string) $offset : ''] = $value;
        }
    }

    /**
     * Forget the value memoized for a key, so the next read loads it again
     *
     * @param  mixed $offset Key to forget
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->loaded[is_scalar($offset) ? (string) $offset : '']);
    }
}
