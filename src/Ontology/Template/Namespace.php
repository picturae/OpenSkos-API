<?= "<?php\n"; ?>

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
<?php if (count($consts)) {
    echo "\n";
} ?>
<?php foreach ($consts as $name => $value) { ?>
    const <?= $name; ?> = '<?= $value; ?>';
<?php } ?>
<?php if (count($lists)) {
    echo "\n";
} ?>
<?php foreach ($lists as $name => $values) { ?>
    const <?= $name; ?> = [
<?php foreach ($values as $value) { ?>
        self::<?= $value; ?>,
<?php } ?>
    ];
<?php } ?>
<?/* TODO:

    const STATUS_CANDIDATE = 'candidate';
    const STATUS_APPROVED = 'approved';
    const STATUS_REDIRECTED = 'redirected';
    const STATUS_NOT_COMPLIANT = 'not_compliant';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OBSOLETE = 'obsolete';
    const STATUS_DELETED = 'deleted';

    const STATUSES = [
        self::STATUS_CANDIDATE,
        self::STATUS_APPROVED,
        self::STATUS_REDIRECTED,
        self::STATUS_NOT_COMPLIANT,
        self::STATUS_REJECTED,
        self::STATUS_OBSOLETE,
        self::STATUS_DELETED,
    ];
*/?>
}
