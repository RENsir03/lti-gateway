<?php
$configFile = '/var/www/html/config.php';
$content = file_get_contents($configFile);

// Replace wwwroot line to use HTTP_HOST
$content = preg_replace(
    '/\$CFG->wwwroot\s*=\s*[\'"][^\'"]+[\'"];/',
    '$CFG->wwwroot = \'http://\' . $_SERVER[\'HTTP_HOST\'];',
    $content
);

file_put_contents($configFile, $content);
echo "Updated wwwroot to use HTTP_HOST\n";
