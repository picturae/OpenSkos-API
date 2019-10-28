<?php

namespace App\Ontology\Template;

?>
<?= "<?php\n"; ?>
<?php
$skipFields = [
    'name',
    'dataType',
];
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

final class <?= $name; ?>

{
    const NAME_SPACE = '<?= $namespace; ?>';
<?php foreach ($properties as $property) { ?>
    const <?= $property['const']; ?> = '<?= $namespace.$property['name']; ?>';
<?php } ?>
<?= count($consts) ? "\n" : ''; ?>
<?php foreach ($consts as $key => $value) { ?>
    const <?= $key; ?> = '<?= $value; ?>';
<?php } ?>
<?= count($lists) ? "\n" : ''; ?>
<?php foreach ($lists as $key => $values) { ?>
    const <?= $key; ?> = [
<?php foreach ($values as $value) { ?>
        <?= $value; ?>,
<?php } ?>
    ];
<?php } ?>
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
        $<?= $descriptor['name']; ?>->setType('rdf:Property');
        $<?= $descriptor['name']; ?>->addLiteral('openskos:dataType', '<?= $descriptor['dataType'] ?? 'literal'; ?>');
<?php foreach ($descriptor as $field => $values) {
    if (in_array($field, $skipFields, true)) {
        continue;
    }
    if (is_string($values)) {
        $values = [$values];
    } ?>
<?php foreach ($values as $value) { ?>
        $<?= $descriptor['name']; ?>->add<?= ucfirst($dataType[$field] ?? 'literal'); ?>(<?= Template::quoteString($field); ?>, <?= Template::quoteString($value); ?>);
<?php } /* foreach values as value */ ?>
<?php
} /* foreach descriptor as field => values */ ?>

<?php } /* foreach vocabulary as descriptor */ ?>
        return $graph;
    }
<?php } /* if count vocabulary */ ?>
}
