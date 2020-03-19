<?php

namespace App\Template;

class Template
{
    public static function render(string $templateName, array $data = null): string
    {
        ob_start();
        if (is_array($data)) {
            extract($data);
        }
        require __DIR__.DIRECTORY_SEPARATOR."${templateName}.php";

        return ob_get_clean();
    }

    public static function quoteString(string $str): string
    {
        return "'".str_replace("'", "\\'", $str)."'";
    }

    /**
     * Translates a camel case string into a string with
     * underscores (e.g. firstName -> first_name).
     *
     * @param string $str       String in camel case format
     * @param string $separator Token separator
     *
     * @return string $str Translated into underscore format
     */
    public static function from_camel_case($str, string $separator = '_'): string
    {
        $str  = preg_replace_callback('/([a-z])([A-Z])/', function (array $c) use ($separator) {
            return strtolower($c[1]).$separator.strtolower($c[2]);
        }, $str);

        $str  = lcfirst($str);

        return $str;
    }

    /**
     * Translates a string with underscores
     * into camel case (e.g. first_name -> firstName).
     *
     * @param string $str                   String in underscore format
     * @param bool   $capitalise_first_char If true, capitalise the first char in $str
     * @param string $separator             Token separator
     *
     * @return string $str translated into camel caps
     */
    public static function to_camel_case($str, $capitalise_first_char = false, string $separator = '_')
    {
        if ($capitalise_first_char) {
            $str = ucfirst($str);
        }

        return preg_replace_callback('/_([a-z])/', function (array $c) {
            return strtoupper($c[1]);
        }, $str);
    }
}
