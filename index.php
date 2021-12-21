<?php
date_default_timezone_set('Asia/Shanghai');
$now = time();

function getTemplate($time, $defaultStatus = 'disruption') {
    $template = [];
    $minute = date('H', $time) * 60 + date('i', $time);
    for ($i = 0; $i < 1440; $i += 5) {
        if ($i > 1380 || $i > $minute) {
            $template[$i] = 'todo';
            continue;
        }
        $template[$i] = $defaultStatus;
    }
    return $template;
}
$ciData = getTemplate($now);
$projectData = getTemplate($now, 'unknown');

$handle = @fopen("git.log", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $tmp = explode(" | ", $buffer);
        $date = trim($tmp[0]);
        $time = strtotime($date);
        $key = date('H', $time) * 60 + floor(date('i', $time) / 5) * 5;
        $ciData[$key] = 'available';
        $projectData[$key] = stripos($tmp[1], 'success') !== false ? 'available' : 'disruption';
    }
    fclose($handle);
}

$domain = file_exists('CNAME') ? file_get_contents('CNAME') : null;
$homeUrl = !empty($domain) ? 'https://' . $domain : '/';
require_once 'template.html';
