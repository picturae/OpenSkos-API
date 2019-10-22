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
}
