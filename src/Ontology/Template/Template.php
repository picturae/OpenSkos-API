<?php

namespace App\Ontology\Template;

class Template
{
    /**
     * @param string     $templateName
     * @param array|null $data
     *
     * @return string
     */
    public static function render(string $templateName, array $data = null): string
    {
        ob_start();
        if (is_array($data)) {
            extract($data);
        }
        require __DIR__.DIRECTORY_SEPARATOR."${templateName}.php";

        return ob_get_clean();
    }

    /**
     * Translates a camel case string into a string with
     * underscores (e.g. firstName -> first_name).
     *
     * @param string $str String in camel case format
     *
     * @return string $str Translated into underscore format
     */
    public static function from_camel_case($str): string
    {
        $func = create_function('$c', 'return strtolower($c[1]) . "_" . strtolower($c[2]);');
        $str = preg_replace_callback('/([a-z])([A-Z])/', $func, $str);
        $str[0] = strtolower($str[0]);
        /** @var string $str */
        return $str;
    }

    /**
     * Translates a string with underscores
     * into camel case (e.g. first_name -> firstName).
     *
     * @param string $str                   String in underscore format
     * @param bool   $capitalise_first_char If true, capitalise the first char in $str
     *
     * @return string $str translated into camel caps
     */
    public static function to_camel_case($str, $capitalise_first_char = false)
    {
        if ($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}
