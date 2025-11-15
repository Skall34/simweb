<?php
$fr = include 'fr.php';
$en = include 'en.php';
$es = include 'es.php';

$keys_fr = array_keys($fr);
$keys_en = array_keys($en);
$keys_es = array_keys($es);

echo "Nombre de clés:\n";
echo "FR: " . count($keys_fr) . "\n";
echo "EN: " . count($keys_en) . "\n";
echo "ES: " . count($keys_es) . "\n";
echo "\n";

$missing_en = array_diff($keys_fr, $keys_en);
$missing_es = array_diff($keys_fr, $keys_es);
$extra_en = array_diff($keys_en, $keys_fr);
$extra_es = array_diff($keys_es, $keys_fr);

if (!empty($missing_en)) {
    echo "Clés manquantes dans EN (" . count($missing_en) . "):\n";
    foreach (array_slice($missing_en, 0, 30) as $key) {
        echo "  - $key\n";
    }
    if (count($missing_en) > 30) echo "  ... et " . (count($missing_en) - 30) . " autres\n";
    echo "\n";
}

if (!empty($missing_es)) {
    echo "Clés manquantes dans ES (" . count($missing_es) . "):\n";
    foreach (array_slice($missing_es, 0, 30) as $key) {
        echo "  - $key\n";
    }
    if (count($missing_es) > 30) echo "  ... et " . (count($missing_es) - 30) . " autres\n";
    echo "\n";
}

if (!empty($extra_en)) {
    echo "Clés en trop dans EN (" . count($extra_en) . "):\n";
    foreach (array_slice($extra_en, 0, 20) as $key) {
        echo "  - $key\n";
    }
    if (count($extra_en) > 20) echo "  ... et " . (count($extra_en) - 20) . " autres\n";
    echo "\n";
}

if (!empty($extra_es)) {
    echo "Clés en trop dans ES (" . count($extra_es) . "):\n";
    foreach (array_slice($extra_es, 0, 20) as $key) {
        echo "  - $key\n";
    }
    if (count($extra_es) > 20) echo "  ... et " . (count($extra_es) - 20) . " autres\n";
    echo "\n";
}

if (empty($missing_en) && empty($missing_es) && empty($extra_en) && empty($extra_es)) {
    echo "✅ Tous les fichiers ont les mêmes clés!\n";
} else {
    echo "❌ Il y a des différences entre les fichiers.\n";
}
