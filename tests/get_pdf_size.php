<?php
$content = file_get_contents('c:/xampp/htdocs/deped/leave card.pdf');
preg_match_all('/\/MediaBox\s*\[(.*?)\]/', $content, $matches);
print_r($matches);
