<?php
$path = "resources/views/erp/roles/index.blade.php";
$content = file_get_contents($path);

// Replace WAREHOUSE block
$start = "{{-- WAREHOUSE --}}";
$end = "</div>\n                                <!-- /END WAREHOUSE -->";
// Wait, they might not have closing comments. Let's just wrap the inner col-md-4
$content = str_replace(
    '<div class="col-md-4">' . "\n                                    " . '<div class="fw-semibold mb-2">Warehouse</div>',
    '@if(isset($groups[\'warehouse\']) && count($groups[\'warehouse\']) > 0)' . "\n                                <div class=\"col-md-4\">\n                                    <div class=\"fw-semibold mb-2\">Warehouse</div>",
    $content
);
$content = str_replace(
    '<div class="col-md-4">' . "\n                                    " . '<div class="fw-semibold mb-2 text-primary">Warehouse</div>',
    '@if(isset($groups[\'warehouse\']) && count($groups[\'warehouse\']) > 0)' . "\n                                <div class=\"col-md-4\">\n                                    <div class=\"fw-semibold mb-2 text-primary\">Warehouse</div>",
    $content
);
$content = str_replace(
    '<div class="col-md-4">' . "\n                                    " . '<div class="fw-semibold mb-2 text-warning">Warehouse</div>',
    '@if(isset($groups[\'warehouse\']) && count($groups[\'warehouse\']) > 0)' . "\n                                <div class=\"col-md-4\">\n                                    <div class=\"fw-semibold mb-2 text-warning\">Warehouse</div>",
    $content
);

file_put_contents($path, $content);
?>
