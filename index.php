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
$gitStatus = $projectStatus;

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
            $gitStatus[$key] = 'available';
            continue;
        }
        $testsuites = new SimpleXMLElement(file_get_contents('junit.xml'));
        foreach ($testsuites->testsuite[0] as $testsuite) {
            $status = $testsuite['errors'] == 0 ? 'available' : 'disruption';
            switch ($testsuite['name']) {
                case 'Tests\Acceptance\ArtifactTest':
                    $artifactStatus[$key] = $status;
                    break;
                case 'Tests\Acceptance\IssueTest':
                    $projectStatus[$key] = $status;
                    break;
                case 'Tests\Acceptance\GitBranchTest':
                    $gitStatus[$key] = $status;
                    break;
            }
        }
    }
    fclose($handle);
}

$domain = file_exists('CNAME') ? file_get_contents('CNAME') : null;
$homeUrl = !empty($domain) ? 'https://' . $domain : '/';
$allStatus = [
    '持续集成' => $ciStatus,
    '项目协同' => $projectStatus,
    '代码仓库' => $gitStatus,
    '制品仓库' => $artifactStatus,
];
require_once 'template.html';
