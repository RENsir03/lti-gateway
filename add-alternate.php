<?php
$content = PHP_EOL . '$CFG->wwwrootalternates = array("http://moodle:8080");' . PHP_EOL;
file_put_contents('/var/www/html/config.php', $content, FILE_APPEND);
echo "Added wwwrootalternates config\n";
