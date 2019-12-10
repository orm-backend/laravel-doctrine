<?php

if (! function_exists('oldd')) {
    /**
     * Retrieve an old input item.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function oldd($key = null, $default = null)
    {
        if ($key) {
            $key = str_replace('.', '_', $key);
        }
        
        return app('request')->old($key, $default);
    }
}
