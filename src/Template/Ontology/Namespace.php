<?php

namespace App\Template\Ontology;

use App\Ontology\Context;
use App\Template\Template;

?>
<?= "<?php\n"; ?>
<?php
$skipFields = [
    'const',
    'datatype',
    'literaltype',
    'name',
    'regex',
];

$hasValidation = false;
foreach ($properties as $property) {
    if ($hasValidation) {
        continue;
    }
    $hasValidation = $property['hasValidation'] ?? false;
}
?>

/**
 * OpenSKOS.
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 *
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace App\Ontology;
<?php if ($hasValidation) { ?>

use App\Annotation\Error;
use App\Rdf\Iri;
use App\Rdf\Literal\Literal;
<?php } /* if has validation */ ?>

final class <?= $name; ?>

{
    const NAME_SPACE = '<?= $namespace; ?>';
<?php foreach ($properties as $property) { ?>
    const <?= $property['const']; ?> = '<?= $namespace.$property['name']; ?>';
<?php } /* foreach properties as property */?>
<?= count($consts) ? "\n" : ''; ?>
<?php foreach ($consts as $key => $value) { ?>
    const <?= $key; ?> = '<?= $value; ?>';
<?php } /* foreach consts as key value */ ?>
<?= count($lists) ? "\n" : ''; ?>
<?php foreach ($lists as $key => $values) { ?>
    const <?= $key; ?> = [
<?php foreach ($values as $value) { ?>
        <?= $value; ?>,
<?php } /* foreach values as value */ ?>
    ];
<?php } /* foreach lists as key values */ ?>
<?php foreach ($properties as $property) { ?>
<?php if ($property['hasValidation']) { ?>

    /**
     * Returns the first encountered error for <?= $property['name']; ?>.
     * Returns null on success (a.k.a. no errors).
     *
     * @param Literal|Iri $value
     *
<?php if (isset($property['literaltype'])) { ?>
     * @Error(code="<?= strtolower($name); ?>-validate-<?= strtolower($property['name']); ?>-literal-type",
     *        status=422,
     *        fields={"expected","actual"},
     *        description="The object for the <?= strtolower($property['name']); ?> predicate has a different type than '<?= Context::fullUri($property['literaltype']); ?>'"
     *     )
<?php } /* if isset property literal type */ ?>
<?php if (isset($property['regex'])) { ?>
     * @Error(code="<?= strtolower($name); ?>-validate-<?= strtolower($property['name']); ?>-regex",
     *        status=422,
     *        fields={"regex","value"},
     *        description="The object for the <?= strtolower($property['name']); ?> predicate did not match the configured regex"
     *     )
<?php } /* if isset property regex */ ?>
     */
    public function validate<?= ucfirst($property['name']); ?>($property): ?array
    {
        $value = null;
        if ($property instanceof Iri) {
            $value = $property->getUri();
        }
        if ($property instanceof Literal) {
            $value = $property->value();
<?php if (isset($property['literaltype'])) { ?>

            if ('<?= Context::fullUri($property['literaltype']); ?>' !== $property->typeIri()->getUri()) {
                return [
                    'code' => '<?= strtolower($name); ?>-validate-<?= strtolower($property['name']); ?>-literal-type',
                    'data' => [
                        'expected' => '<?= Context::fullUri($property['literaltype']); ?>',
                        'actual' => $property->typeIri()->getUri(),
                    ],
                ];
            }
<?php } /* if isset property literal type */ ?>
        }
        if (is_null($value)) {
            return null;
        }

<?php if (isset($property['regex'])) { ?>
        $regex = '<?= str_replace('\\', '\\\\', $property['regex']); ?>';
        if (!preg_match($regex, $value)) {
            return [
                'code' => '<?= strtolower($name); ?>-validate-<?= strtolower($property['name']); ?>-regex',
                'data' => [
                    'regex' => $regex,
                    'value' => $value,
                ],
            ];
        }

<?php } /* if isset property regex */ ?>
        return null;
    }
<?php } /* if property has validation */ ?>
<?php } /* foreach property as properties */ ?>
<?php if (count($vocabulary)) { ?>

    public static function vocabulary(): \EasyRdf_Graph
    {
<?php foreach ($context as $descriptor) { ?>
        \EasyRdf_Namespace::set(<?= Template::quoteString($descriptor['prefix']); ?>, <?= $descriptor['name']; ?>::NAME_SPACE);
<?php } /* foreach */ ?>

        // Define graph structure
        $graph = new \EasyRdf_Graph('openskos.org');

        // Intro
        $openskos = $graph->resource(<?= Template::quoteString($namespace); ?>);
        $openskos->setType('owl:Ontology');
        $openskos->addLiteral('dc:title', <?= Template::quoteString($name.' vocabulary'); ?>);

<?php foreach ($vocabulary as $descriptor) { ?>
        $<?= $descriptor['name']; ?> = $graph->resource(<?= Template::quoteString($prefix.':'.$descriptor['name']); ?>);
        $<?= $descriptor['name']; ?>->setType('<?= ('class' === $descriptor['datatype']) ? 'rdfs:Class' : 'rdf:Property'; ?>');
        $<?= $descriptor['name']; ?>->addLiteral('openskos:datatype', '<?= $descriptor['datatype'] ?? 'literal'; ?>');
<?php foreach ($descriptor as $field => $values) {
    if (in_array($field, $skipFields, true)) {
        continue;
    }
    if (is_string($values)) {
        $values = [$values];
    } ?>
<?php foreach ($values as $value) { ?>
        $<?= $descriptor['name']; ?>->add<?= ucfirst($datatype[$field] ?? 'literal'); ?>(<?= Template::quoteString($field); ?>, <?= Template::quoteString($value); ?>);
<?php } /* foreach values as value */ ?>
<?php
} /* foreach descriptor as field => values */ ?>

<?php } /* foreach vocabulary as descriptor */ ?>
        return $graph;
    }
<?php } /* if count vocabulary */ ?>
}
