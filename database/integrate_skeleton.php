<?php
/** Connect the supplied skeleton asset without replacing existing learning content. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/connection.php';
$asset = 'male_human_skeleton_-_zbrush_-_anatomy_study.glb';
$file = __DIR__ . '/../system_model/' . $asset;
if (!is_file($file)) throw new RuntimeException('The supplied skeleton model is missing.');
$stream = fopen($file, 'rb');
$header = unpack('Vmagic/Vversion/Vlength/VjsonLength/Vtype', fread($stream, 20));
if ($header['magic'] !== 0x46546c67 || $header['version'] !== 2 || $header['type'] !== 0x4e4f534a) throw new RuntimeException('Expected a GLB 2.0 model.');
$gltf = json_decode(fread($stream, $header['jsonLength']), true, 512, JSON_THROW_ON_ERROR);
fclose($stream);
$extras = $gltf['asset']['extras'] ?? [];
$source = implode("\n", array_filter([$extras['title'] ?? '', 'Author: ' . ($extras['author'] ?? ''), 'License: ' . ($extras['license'] ?? ''), 'Source: ' . ($extras['source'] ?? '')]));
$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
    $system = $db->fetchOne("SELECT system_id FROM body_systems WHERE system_code = 'skeletal'");
    $id = $system ? (int)$system['system_id'] : $db->insert('body_systems', [
        'system_code'=>'skeletal', 'system_name'=>'Skeletal System', 'color_hex'=>'#1a6b4a',
        'description'=>'Explore the supplied human skeleton model. Rotate, zoom, and reset the view to study its overall form. This asset is a single combined mesh; individual bones cannot be selected separately.',
        'sort_order'=>1, 'is_active'=>1
    ]);
    $db->query('UPDATE body_systems SET is_active=1 WHERE system_id=?', [$id]);
    $existing = $db->fetchOne('SELECT source_text FROM anatomy_content WHERE system_id=?', [$id]);
    $previous = trim($existing['source_text'] ?? '');
    if ($previous !== '' && !str_contains($previous, $source)) $source = $previous . "\n\n" . $source;
    elseif ($previous !== '') $source = $previous;
    $db->query('INSERT INTO anatomy_content (system_id, model_url, key_facts, structures, source_text) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE model_url=VALUES(model_url), source_text=VALUES(source_text)', [$id, '../system_model/' . $asset, '{}', '[]', $source]);
    $pdo->commit();
    echo "Skeletal System connected and visible to students. Existing facts and structures preserved.\n";
} catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
