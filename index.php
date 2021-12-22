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
$ciStatus = getTemplate($now);
$projectStatus = getTemplate($now, 'unknown');
$artifactStatus = $projectStatus;

$handle = @fopen("git.log", "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        $tmp = explode(" | ", $buffer);
        $date = trim($tmp[0]);
        $message = $tmp[1];
        $hash = $tmp[2];
        $time = strtotime($date);
        $key = date('H', $time) * 60 + floor(date('i', $time) / 5) * 5;
        $ciStatus[$key] = 'available';
        if (stripos($message, 'success') !== false) {
            $projectStatus[$key] = 'available';
            $artifactStatus[$key] = 'available';
            continue;
        }
        $testsuites = new SimpleXMLElement(file_get_contents('junit.xml'));
        foreach ($testsuites->testsuite[0] as $testsuite) {
            if ($testsuite['name'] == 'Tests\Acceptance\ArtifactTest') {
                $artifactStatus[$key] = $testsuite['errors'] == 0 ? 'available' : 'disruption';
            }
            if ($testsuite['name'] == 'Tests\Acceptance\IssueTest') {
                $projectStatus[$key] = $testsuite['errors'] == 0 ? 'available' : 'disruption';
            }
        }
    }
    fclose($handle);
}

$domain = file_exists('CNAME') ? file_get_contents('CNAME') : null;
$homeUrl = !empty($domain) ? 'https://' . $domain : '/';
require_once 'template.html';
