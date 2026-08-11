<?php

declare(strict_types=1);

/**
 * Verifies the catalog by pairing each local product with one source product:
 * title, price and photo always come from the same product record.
 *
 * Sources: https://dolamdichua.vn/ and https://phapduyen.com/
 * Run: php scripts/sync_dolam_product_images.php
 */

$projectRoot = dirname(__DIR__);
$config = require $projectRoot . '/config/database.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
    $config['user'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$onlyCategoryId = null;
foreach ($argv as $argument) {
    if (preg_match('/^--category=(11[1-6])$/', $argument, $matches)) {
        $onlyCategoryId = (int)$matches[1];
    }
}

function fetchUrl(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'follow_location' => 1,
            'max_redirects' => 5,
            'header' => "User-Agent: PaceupCatalogImageSync/2.0\r\nAccept: application/json,image/avif,image/webp,image/*,*/*;q=0.8\r\n",
        ],
        'https' => [
            'timeout' => 30,
            'follow_location' => 1,
            'max_redirects' => 5,
            'header' => "User-Agent: PaceupCatalogImageSync/2.0\r\nAccept: application/json,image/avif,image/webp,image/*,*/*;q=0.8\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false || $body === '') {
        throw new RuntimeException("Không tải được dữ liệu nguồn: $url");
    }

    return $body;
}

function fetchProducts(string $url): array
{
    $items = json_decode(fetchUrl($url), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($items)) {
        throw new RuntimeException("Nguồn không trả về danh sách sản phẩm: $url");
    }

    return $items;
}

function sourceProduct(array $item, string $sourceSite): array
{
    $name = trim(html_entity_decode((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $imageUrl = trim(html_entity_decode((string)($item['images'][0]['src'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $slug = trim((string)($item['slug'] ?? ''));
    $price = (int)($item['prices']['price'] ?? 0);

    if ($name === '' || $imageUrl === '' || $slug === '' || $price <= 0) {
        throw new RuntimeException("Một sản phẩm nguồn thiếu tên, giá, slug hoặc ảnh tại $sourceSite.");
    }

    return [
        'source_site' => $sourceSite,
        'source_product_id' => (int)($item['id'] ?? 0),
        'source_product_url' => (string)($item['permalink'] ?? $sourceSite),
        'source_name' => $name,
        'source_slug' => $slug,
        'source_image_url' => $imageUrl,
        'source_price' => $price,
    ];
}

function sourceCandidates(array $items, string $label): array
{
    $products = [];
    $seen = [];
    foreach ($items as $item) {
        $product = sourceProduct($item, $label);
        if (isset($seen[$product['source_product_id']])) {
            continue;
        }
        $seen[$product['source_product_id']] = true;
        $products[] = $product;
    }

    return $products;
}

function extensionFromImage(string $image): string
{
    if (str_starts_with($image, "\xFF\xD8\xFF")) {
        return 'jpg';
    }
    if (str_starts_with($image, "\x89PNG\r\n\x1A\n")) {
        return 'png';
    }
    if (substr($image, 0, 4) === 'RIFF' && substr($image, 8, 4) === 'WEBP') {
        return 'webp';
    }

    throw new RuntimeException('Nguồn trả về dữ liệu không phải ảnh JPEG, PNG hoặc WebP.');
}

function localSlug(string $sourceSlug, int $productId): string
{
    $sourceSlug = trim(preg_replace('/[^a-z0-9-]+/i', '-', $sourceSlug) ?? '', '-');
    return 'lam-' . substr($sourceSlug, 0, 220) . '-' . $productId;
}

function localDescription(string $name, string $categoryName): string
{
    return "$name. Sản phẩm thuộc nhóm $categoryName, phù hợp sử dụng khi đi chùa, lễ Phật hoặc thực hành thiền.";
}

$phapDuyen = 'https://phapduyen.com';
$doLamDiChua = 'https://dolamdichua.vn';

// Each pool contains products from the exact type shown by its local category.
$sourcePools = [
    112 => sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=111&per_page=100&page=1"), $phapDuyen),
    113 => sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=165&per_page=100&page=1"), $phapDuyen),
    115 => sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=120&per_page=100&page=1"), $phapDuyen),
    116 => sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=99&per_page=100&page=1"), $phapDuyen),
];

$doLamItems = array_merge(
    fetchProducts("$doLamDiChua/wp-json/wc/store/v1/products?per_page=100&page=1"),
    fetchProducts("$doLamDiChua/wp-json/wc/store/v1/products?per_page=100&page=2")
);
$bagItems = array_values(array_filter($doLamItems, static function (array $item): bool {
    return in_array('Túi Xách Đi Chùa', array_column($item['categories'] ?? [], 'name'), true);
}));
$robeItems = array_values(array_filter($doLamItems, static function (array $item): bool {
    return in_array('Áo Tràng/Áo Khoác', array_column($item['categories'] ?? [], 'name'), true);
}));

// Root category 103 also contains scarves and footwear, so only combine its actual robe/cassock children.
$sourcePools[111] = array_merge(
    sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=108&per_page=100&page=1"), $phapDuyen),
    sourceCandidates(fetchProducts("$phapDuyen/wp-json/wc/store/v1/products?category=109&per_page=100&page=1"), $phapDuyen),
    sourceCandidates($robeItems, $doLamDiChua)
);
$sourcePools[114] = sourceCandidates($bagItems, $doLamDiChua);

$localCategories = $pdo->query('SELECT id, name FROM categories WHERE id BETWEEN 111 AND 116')
    ->fetchAll(PDO::FETCH_KEY_PAIR);
if (count($localCategories) !== 6) {
    throw new RuntimeException('Không tìm thấy đủ sáu danh mục đồ lam trong database.');
}

$productSql = 'SELECT id, category_id, gender FROM product WHERE status = 1 AND category_id BETWEEN 111 AND 116';
if ($onlyCategoryId !== null) {
    $productSql .= ' AND category_id = ' . $onlyCategoryId;
}
$products = $pdo->query($productSql . ' ORDER BY category_id, id')->fetchAll(PDO::FETCH_ASSOC);
$expectedProductCount = $onlyCategoryId === null ? 150 : 25;
if (count($products) !== $expectedProductCount) {
    throw new RuntimeException("Cần đúng $expectedProductCount sản phẩm đang hoạt động trước khi đồng bộ.");
}

$destinationDir = $projectRoot . '/public/uploads/products/lam';
if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
    throw new RuntimeException("Không tạo được thư mục ảnh: $destinationDir");
}

$categoryCursors = [];
$mapping = [];
foreach ($products as $product) {
    $categoryId = (int)$product['category_id'];
    $source = null;
    while (($candidate = $sourcePools[$categoryId][$categoryCursors[$categoryId] ?? 0] ?? null) !== null) {
        $categoryCursors[$categoryId] = ($categoryCursors[$categoryId] ?? 0) + 1;
        try {
            $candidate['image_body'] = fetchUrl($candidate['source_image_url']);
            $candidate['image_extension'] = extensionFromImage($candidate['image_body']);
            $source = $candidate;
            break;
        } catch (Throwable) {
            // A stale remote image must never create a mismatched local record.
        }
    }
    if ($source === null) {
        throw new RuntimeException("Không đủ ảnh nguồn còn hoạt động cho danh mục {$localCategories[$categoryId]}.");
    }

    $gender = match ($categoryId) {
        112 => 'women',
        113 => 'men',
        default => null,
    };
    $mapping[] = $source + [
        'product_id' => (int)$product['id'],
        'category_id' => $categoryId,
        'category_name' => $localCategories[$categoryId],
        'name' => $source['source_name'],
        'slug' => localSlug($source['source_slug'], (int)$product['id']),
        'description' => localDescription($source['source_name'], $localCategories[$categoryId]),
        'gender' => $gender,
    ];
}

// Write every downloaded file before changing the database. This avoids a DB row that points to a missing image.
foreach ($mapping as &$record) {
    $relativePath = 'public/uploads/products/lam/lam-' . $record['product_id'] . '.' . $record['image_extension'];
    $temporaryPath = $destinationDir . '/.lam-' . $record['product_id'] . '.' . $record['image_extension'] . '.tmp';
    if (file_put_contents($temporaryPath, $record['image_body']) === false) {
        throw new RuntimeException("Không ghi được ảnh tạm: $temporaryPath");
    }
    $record['image_url'] = $relativePath;
    $record['temporary_path'] = $temporaryPath;
}
unset($record);

foreach ($mapping as &$record) {
    foreach (glob($destinationDir . '/lam-' . $record['product_id'] . '.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $oldFile) {
        if (!unlink($oldFile)) {
            throw new RuntimeException("Không thay được ảnh cũ: $oldFile");
        }
    }
    $finalPath = $projectRoot . '/' . $record['image_url'];
    if (!rename($record['temporary_path'], $finalPath)) {
        throw new RuntimeException("Không đổi tên ảnh mới: $finalPath");
    }
    unset($record['image_body'], $record['image_extension'], $record['temporary_path']);
}
unset($record);

$pdo->beginTransaction();
try {
    $updateProduct = $pdo->prepare(
        'UPDATE product SET name = :name, slug = :slug, description = :description, base_price = :base_price, gender = :gender WHERE id = :product_id'
    );
    $updateImage = $pdo->prepare('UPDATE product_images SET image_url = :image_url, is_primary = 1 WHERE product_id = :product_id');

    foreach ($mapping as $record) {
        $updateProduct->execute([
            'name' => $record['name'],
            'slug' => $record['slug'],
            'description' => $record['description'],
            'base_price' => $record['source_price'],
            'gender' => $record['gender'],
            'product_id' => $record['product_id'],
        ]);
        $updateImage->execute(['image_url' => $record['image_url'], 'product_id' => $record['product_id']]);
        if ($updateImage->rowCount() === 0) {
            $imageExists = $pdo->prepare('SELECT 1 FROM product_images WHERE product_id = :product_id LIMIT 1');
            $imageExists->execute(['product_id' => $record['product_id']]);
            if (!$imageExists->fetchColumn()) {
                throw new RuntimeException("Sản phẩm {$record['product_id']} chưa có dòng product_images.");
            }
        }
    }

    $pdo->prepare("INSERT INTO schema_migrations (version) VALUES ('paceup_lam_catalog_alignment_v2') ON DUPLICATE KEY UPDATE version = VALUES(version)")
        ->execute();
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

$exportRows = $pdo->query(
    'SELECT p.id, p.name, p.slug, p.description, p.base_price, p.gender, pi.image_url '
    . 'FROM product p JOIN product_images pi ON pi.product_id = p.id '
    . 'WHERE p.status = 1 AND p.category_id BETWEEN 111 AND 116 ORDER BY p.category_id, p.id'
)->fetchAll(PDO::FETCH_ASSOC);
if (count($exportRows) !== 150) {
    throw new RuntimeException('Không thể xuất mapping: catalog không đủ 150 sản phẩm có ảnh.');
}

$sql = [
    '-- Generated by scripts/sync_dolam_product_images.php',
    '-- Each product title, base price and photo originate from the same source product.',
    'USE `paceup_db`;',
    'START TRANSACTION;',
];
foreach ($exportRows as $record) {
    $sql[] = sprintf(
        'UPDATE `product` SET `name`=%s, `slug`=%s, `description`=%s, `base_price`=%s, `gender`=%s WHERE `id`=%d;',
        $pdo->quote((string)$record['name']),
        $pdo->quote($record['slug']),
        $pdo->quote($record['description']),
        number_format((float)$record['base_price'], 2, '.', ''),
        $record['gender'] === null ? 'NULL' : $pdo->quote($record['gender']),
        (int)$record['id']
    );
    $sql[] = sprintf(
        'UPDATE `product_images` SET `image_url`=%s, `is_primary`=1 WHERE `product_id`=%d;',
        $pdo->quote($record['image_url']),
        (int)$record['id']
    );
}
$sql[] = "INSERT INTO `schema_migrations` (`version`) VALUES ('paceup_lam_catalog_alignment_v2') ON DUPLICATE KEY UPDATE `version`=VALUES(`version`);";
$sql[] = 'COMMIT;';
file_put_contents($projectRoot . '/Database/paceup_lam_product_images.sql', implode(PHP_EOL, $sql) . PHP_EOL);
if ($onlyCategoryId === null) {
    file_put_contents(
        $projectRoot . '/Database/paceup_lam_product_image_sources.json',
        json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );
}

echo 'Đã đồng bộ ' . count($mapping) . ' cặp tên, giá và ảnh sản phẩm khớp nhau.' . PHP_EOL;
