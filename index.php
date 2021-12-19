<?php

$dt = new DateTime("now", new DateTimeZone('Asia/Shanghai'));

$template = [];
$minute = $dt->format("H") * 60 + $dt->format("i");
for ($i = 0; $i < 1440; $i += 5) {
    if ($i > 1380 || $i > $minute) {
        $template[$i] = 'unknown';
        continue;
    }
    $template[$i] = 'disruption';
}
$ciData = $template;

$handle = @fopen("date.log", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $tmp = explode("   ", $buffer);
        $date = trim($tmp[1]);
        $thisTime = strtotime($date);
        $dt->setTimestamp($thisTime);
        $ciData[$dt->format("H") * 60 + floor($dt->format("i") / 5) * 5] = 'available';
    }
    fclose($handle);
}

$dt->setTimestamp(time());
require_once 'template.html';
