<?php
/*
 * Copyright (c) 2014 Joachim Basmaison This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */
namespace WpCustomFieldChart;

class ErrorMissingAttribute extends \ErrorException
{
}
;

class Field
{

    public $name = Null;
    public $required = Null;
    public $default = Null;
    public $match = Null;
    public $info = Null;
    public $callback = Null;

    function __construct($name, $required, $default = Null, $match = Null,
        $info = Null, $callback=Null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->default = $default;
        $this->match = $match;
        $this->info = $info;
        $this->callback = $callback;
    }

    function validate($value)
    {
        if ($value == '') {
            $value = Null;
        }
        if (is_null($value) && $this->required) {
            if (is_null($this->default)) {
                throw new ErrorMissingAttribute($this->name);
            }
            return $this->default;
        }
        $cb = $this->callback;
        if (!is_null($cb)) {
            $value = $cb($value);
        }
        return $value;
    }

    function make_error_message()
    {
        $out = '<div class="cfc-error" style="background-color: black; ' .
            'color:white; padding: 0.5em 1em; font-family: Arial; ' .
            'border-style: solid; border-width:2px; border-color: red">';
        $out .= '<h2>Wordpress Extension Error / Custom Field Chart</h2>';
        $out .= '<b>Missing attribute:</b>&nbsp;' . $this->name . '<br>';
        $out .= '<b>Attribute information:</b>&nbsp;' . $this->info . '';
        $out .= '</div>';
        return $out;
    }
}