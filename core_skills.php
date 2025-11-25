<?php

$numbers = [];

for($i = 0; $i < 10; $i ++) {
    $numbers[] = rand(1, 20);
}

$filtered = array_filetr($numbers, function(int $number){
    return $number < 10;
});

echo "Original array:\n";
print_r($numbers);

echo "\nFiltered array (numbers < 10):\n";

print_r(array_values($filtered));