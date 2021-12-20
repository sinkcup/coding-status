<?php

$dt = new DateTime("now", new DateTimeZone('Asia/Shanghai'));

function getTemplate($time, $defaultStatus = 'disruption') {
    $template = [];
    $minute = $time->format("H") * 60 + $time->format("i");
    for ($i = 0; $i < 1440; $i += 5) {
        if ($i > 1380 || $i > $minute) {
            $template[$i] = 'todo';
            continue;
        }
        $template[$i] = $defaultStatus;
    }
    return $template;
}
$ciData = getTemplate($dt);
$projectData = getTemplate($dt, 'unknown');

$handle = @fopen("git.log", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $tmp = explode(" | ", $buffer);
        $date = trim($tmp[0]);
        $thisTime = strtotime($date);
        $dt->setTimestamp($thisTime);
        $key = $dt->format("H") * 60 + floor($dt->format("i") / 5) * 5;
        $ciData[$key] = 'available';
        $projectData[$key] = stripos($tmp[1], 'success') !== false ? 'available' : 'disruption';
    }
    fclose($handle);
}

$dt->setTimestamp(time());
require_once 'template.html';
