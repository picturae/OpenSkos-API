<?= "<?php\n"; ?>

namespace App\Annotation;

/**
 * @Annotation
 */
class Error extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $code = '';

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var string
     */
    public $description = '';

    /**
     * Returns a list of all found usages of the error annotation throughout the application.
     */
    public function usages(): array
    {
        return [
<?php foreach ($usages as $usage) { ?>
            '<?= $usage['error']->code; ?>' => [
                'class' => '<?= $usage['method']->class; ?>',
                'method' => '<?= $usage['method']->name; ?>',
                'code' => '<?= $usage['error']->code; ?>',
                'description' => '<?= str_replace("'", "\\'", $usage['error']->description); ?>',
                'fields' => [
<?php foreach ($usage['error']->fields as $field) { ?>
                    '<?= $field; ?>',
<?php } /* foreach usage error fields as field */ ?>
                ],
            ],
<?php } /* foreach usages as usage */ ?>
        ];
    }
}
