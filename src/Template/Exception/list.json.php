{
<?php foreach ($usages as $usage) { ?>
    "<?= $usage['code']; ?>" : <?= str_replace("\n", "\n    ", json_encode($usage, JSON_PRETTY_PRINT)); ?>,
<?php } /* foreach usages as usage */ ?>
    "EOF": null
}
