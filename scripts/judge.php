<?php

$tests = file_get_contents('test_output.txt');
$exitCode = trim(file_get_contents('exit_code.txt'));

if ($exitCode !== "0") {
    echo "❌ JUDGE: Tests failed. Fix before continuing.\n";
    exit(1);
}

if (strpos($tests, 'TODO') !== false) {
    echo "❌ JUDGE: TODO found. Feature incomplete.\n";
    exit(1);
}

echo "✅ JUDGE: Feature acceptable. You may continue.\n";
