<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;

class ExcelService
{
    /**
     * Prevent CSV/Excel formula injection (DDE attacks)
     * Prepends a single quote if the cell begins with =, +, -, @, \t, or \r
     */
    public static function sanitize(mixed $val): string
    {
        $str = (string) $val;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Export transactions to CSV
     */
    public static function exportCsv(int $userId, array $filters = []): void
    {
        $filename = 'transactions_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");

        // Headers
        fputcsv($out, [
            'ID',
            'Date',
            'Type',
            'Account',
            'To Account',
            'Category',
            'Amount',
            'Person',
            'Note',
            'Tags'
        ]);

        $transactions = self::getExportTransactions($userId, $filters);

        foreach ($transactions as $t) {
            $amountMajor = number_format(((int)$t['amount']) / 100.0, 2, '.', '');
            fputcsv($out, [
                $t['id'],
                $t['tx_date'],
                ucfirst($t['type']),
                self::sanitize($t['account_name'] ?? 'Account'),
                self::sanitize($t['to_account_name'] ?? ''),
                self::sanitize($t['category_name'] ?? 'Uncategorized'),
                $amountMajor,
                self::sanitize($t['person_name'] ?? ''),
                self::sanitize($t['note'] ?? ''),
                self::sanitize($t['tags'] ?? '')
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Export transactions to XLSX using OpenSpout if available, falling back to CSV
     */
    public static function exportXlsx(int $userId, array $filters = []): void
    {
        $filename = 'transactions_' . date('Ymd_His') . '.xlsx';

        if (class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            try {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Pragma: no-cache');
                header('Expires: 0');

                $writer = new \OpenSpout\Writer\XLSX\Writer();
                $writer->openToFile('php://output');

                // Header row
                $headerRow = \OpenSpout\Common\Entity\Row::fromValues([
                    'ID', 'Date', 'Type', 'Account', 'To Account', 'Category', 'Amount', 'Person', 'Note', 'Tags'
                ]);
                $writer->addRow($headerRow);

                $transactions = self::getExportTransactions($userId, $filters);
                foreach ($transactions as $t) {
                    $amountMajor = number_format(((int)$t['amount']) / 100.0, 2, '.', '');
                    $row = \OpenSpout\Common\Entity\Row::fromValues([
                        $t['id'],
                        $t['tx_date'],
                        ucfirst($t['type']),
                        self::sanitize($t['account_name'] ?? 'Account'),
                        self::sanitize($t['to_account_name'] ?? ''),
                        self::sanitize($t['category_name'] ?? 'Uncategorized'),
                        $amountMajor,
                        self::sanitize($t['person_name'] ?? ''),
                        self::sanitize($t['note'] ?? ''),
                        self::sanitize($t['tags'] ?? '')
                    ]);
                    $writer->addRow($row);
                }

                $writer->close();
                exit;
            } catch (\Throwable $e) {
                Logger::error("OpenSpout XLSX export error: " . $e->getMessage() . " - falling back to CSV");
            }
        }

        // Fallback to CSV
        self::exportCsv($userId, $filters);
    }

    /**
     * Fetch all transactions for export (unpaginated, respects date/category/account filters)
     */
    private static function getExportTransactions(int $userId, array $filters): array
    {
        $sql = "
            SELECT t.*, 
                   a.name AS account_name, 
                   to_a.name AS to_account_name, 
                   c.name AS category_name, 
                   p.name AS person_name
            FROM transactions t
            LEFT JOIN accounts a ON a.id = t.account_id
            LEFT JOIN accounts to_a ON to_a.id = t.to_account_id
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN people p ON p.id = t.person_id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
        ";
        $params = [$userId];

        if (!empty($filters['start_date'])) {
            $sql .= " AND t.tx_date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND t.tx_date <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['account_id'])) {
            $sql .= " AND t.account_id = ?";
            $params[] = (int)$filters['account_id'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= " ORDER BY t.tx_date DESC, t.id DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Parse uploaded file (.csv or .xlsx) and return headers and raw rows
     */
    public static function parseImportFile(string $filePath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = [];
        $headers = [];

        if ($ext === 'xlsx' && class_exists(\OpenSpout\Reader\XLSX\Reader::class)) {
            try {
                $reader = new \OpenSpout\Reader\XLSX\Reader();
                $reader->open($filePath);

                $rowIndex = 0;
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cells = $row->toArray();
                        if ($rowIndex === 0) {
                            $headers = array_map('strval', $cells);
                        } else {
                            if (!empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                                $rows[] = $cells;
                            }
                        }
                        $rowIndex++;
                        if ($rowIndex > 5001) { // cap at 5,000 rows
                            break 2;
                        }
                    }
                    break; // Read first sheet only
                }
                $reader->close();
            } catch (\Throwable $e) {
                Logger::error("OpenSpout read error: " . $e->getMessage());
            }
        }

        // If not XLSX or XLSX parsing produced no rows, try CSV
        if (empty($headers) && empty($rows)) {
            $handle = fopen($filePath, 'r');
            if ($handle !== false) {
                // Check BOM
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                // Detect delimiter
                $sampleLine = fgets($handle);
                rewind($handle);
                if ($bom === "\xEF\xBB\xBF") {
                    fread($handle, 3);
                }

                $delimiter = ',';
                if ($sampleLine !== false) {
                    $commaCount = substr_count($sampleLine, ',');
                    $semiCount = substr_count($sampleLine, ';');
                    $tabCount = substr_count($sampleLine, "\t");
                    if ($semiCount > $commaCount && $semiCount > $tabCount) {
                        $delimiter = ';';
                    } elseif ($tabCount > $commaCount && $tabCount > $semiCount) {
                        $delimiter = "\t";
                    }
                }

                $rowIndex = 0;
                while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
                    if ($rowIndex === 0) {
                        $headers = array_map('trim', $data);
                    } else {
                        if (!empty(array_filter($data, fn($c) => trim((string)$c) !== ''))) {
                            $rows[] = array_map('trim', $data);
                        }
                    }
                    $rowIndex++;
                    if ($rowIndex > 5001) {
                        break;
                    }
                }
                fclose($handle);
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Preview mapped import rows (detect duplicates and validate columns)
     */
    public static function preview(int $userId, array $rawRows, array $mapping, int $defaultAccountId): array
    {
        $dateCol = (int)($mapping['date_col'] ?? 0);
        $amountCol = (int)($mapping['amount_col'] ?? 1);
        $typeCol = isset($mapping['type_col']) ? (int)$mapping['type_col'] : -1;
        $catCol = isset($mapping['cat_col']) ? (int)$mapping['cat_col'] : -1;
        $noteCol = isset($mapping['note_col']) ? (int)$mapping['note_col'] : -1;

        $validCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;
        $previewSample = [];

        $existingHashes = Database::fetchAll(
            "SELECT dedupe_hash FROM transactions WHERE user_id = ? AND dedupe_hash IS NOT NULL AND deleted_at IS NULL",
            [$userId]
        );
        $hashSet = array_flip(array_column($existingHashes, 'dedupe_hash'));

        $userAccounts = Account::getAllForUser($userId);
        $userCategories = Category::getAllForUser($userId);

        $catMap = [];
        foreach ($userCategories as $c) {
            $catMap[strtolower(trim($c['name']))] = (int)$c['id'];
        }

        foreach ($rawRows as $idx => $row) {
            $dateRaw = $row[$dateCol] ?? '';
            $amountRaw = $row[$amountCol] ?? '';

            // Clean amount
            $rawStr = trim((string)$amountRaw);
            $isNegative = str_starts_with($rawStr, '-');
            $cleanAmountStr = preg_replace('/[^\d.]/', '', $rawStr);
            $amountVal = $isNegative ? -(float)$cleanAmountStr : (float)$cleanAmountStr;
            $amountPaisa = (int) round($amountVal * 100);

            // Parse date
            $parsedDate = self::parseDate((string)$dateRaw);

            if ($amountPaisa <= 0 || !$parsedDate) {
                $invalidCount++;
                if (count($previewSample) < 50) {
                    $previewSample[] = [
                        'status' => 'invalid',
                        'date' => (string)$dateRaw,
                        'amount' => (string)$amountRaw,
                        'type' => 'expense',
                        'category' => 'Invalid date or amount',
                        'note' => '',
                    ];
                }
                continue;
            }

            // Determine type
            $type = 'expense';
            if ($typeCol >= 0 && isset($row[$typeCol])) {
                $typeRaw = strtolower(trim((string)$row[$typeCol]));
                if (str_contains($typeRaw, 'income') || str_contains($typeRaw, 'deposit') || str_contains($typeRaw, 'credit')) {
                    $type = 'income';
                }
            }

            $catName = ($catCol >= 0 && isset($row[$catCol])) ? trim((string)$row[$catCol]) : 'General';
            $catId = $catMap[strtolower($catName)] ?? null;

            $note = ($noteCol >= 0 && isset($row[$noteCol])) ? trim((string)$row[$noteCol]) : '';

            // Check deduplication
            $dedupeHash = sha1("{$userId}_{$parsedDate}_{$amountPaisa}_{$defaultAccountId}_{$note}");
            $isDuplicate = isset($hashSet[$dedupeHash]);

            if ($isDuplicate) {
                $duplicateCount++;
            } else {
                $validCount++;
            }

            if (count($previewSample) < 50) {
                $previewSample[] = [
                    'status' => $isDuplicate ? 'duplicate' : 'valid',
                    'date' => $parsedDate,
                    'amount' => number_format($amountPaisa / 100.0, 2),
                    'type' => $type,
                    'category' => $catName,
                    'note' => $note,
                ];
            }
        }

        return [
            'valid_count' => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count' => $invalidCount,
            'sample_rows' => $previewSample,
        ];
    }

    /**
     * Atomically commit imported transactions with batch tracking
     */
    public static function commitImport(int $userId, string $filename, array $rawRows, array $mapping, int $defaultAccountId): array
    {
        $dateCol = (int)($mapping['date_col'] ?? 0);
        $amountCol = (int)($mapping['amount_col'] ?? 1);
        $typeCol = isset($mapping['type_col']) ? (int)$mapping['type_col'] : -1;
        $catCol = isset($mapping['cat_col']) ? (int)$mapping['cat_col'] : -1;
        $noteCol = isset($mapping['note_col']) ? (int)$mapping['note_col'] : -1;
        $skipDuplicates = !empty($mapping['skip_duplicates']);

        $existingHashes = Database::fetchAll(
            "SELECT dedupe_hash FROM transactions WHERE user_id = ? AND dedupe_hash IS NOT NULL AND deleted_at IS NULL",
            [$userId]
        );
        $hashSet = array_flip(array_column($existingHashes, 'dedupe_hash'));

        $userCategories = Category::getAllForUser($userId);
        $catMap = [];
        foreach ($userCategories as $c) {
            $catMap[strtolower(trim($c['name']))] = (int)$c['id'];
        }

        // Create import batch record
        $sqlBatch = "INSERT INTO import_batches (user_id, filename, rows_total, rows_imported, rows_skipped, status, created_at) VALUES (?, ?, ?, ?, ?, 'committed', ?)";
        $now = date('Y-m-d H:i:s');
        Database::query($sqlBatch, [$userId, $filename, count($rawRows), 0, 0, $now]);
        $batchId = (int)Database::lastInsertId();

        $imported = 0;
        $skipped = 0;

        foreach ($rawRows as $row) {
            $dateRaw = $row[$dateCol] ?? '';
            $amountRaw = $row[$amountCol] ?? '';

            $rawStr = trim((string)$amountRaw);
            $isNegative = str_starts_with($rawStr, '-');
            $cleanAmountStr = preg_replace('/[^\d.]/', '', $rawStr);
            $amountVal = $isNegative ? -(float)$cleanAmountStr : (float)$cleanAmountStr;
            $amountPaisa = (int) round($amountVal * 100);
            $parsedDate = self::parseDate((string)$dateRaw);

            if ($amountPaisa <= 0 || !$parsedDate) {
                $skipped++;
                continue;
            }

            $type = 'expense';
            if ($typeCol >= 0 && isset($row[$typeCol])) {
                $typeRaw = strtolower(trim((string)$row[$typeCol]));
                if (str_contains($typeRaw, 'income') || str_contains($typeRaw, 'deposit') || str_contains($typeRaw, 'credit')) {
                    $type = 'income';
                }
            }

            $catName = ($catCol >= 0 && isset($row[$catCol])) ? trim((string)$row[$catCol]) : '';
            $catId = null;
            if (!empty($catName)) {
                $lowerCat = strtolower($catName);
                if (isset($catMap[$lowerCat])) {
                    $catId = $catMap[$lowerCat];
                } else {
                    // Auto-create category
                    try {
                        $catId = Category::create($userId, [
                            'name' => $catName,
                            'type' => $type,
                            'color' => '#64748B',
                        ]);
                        $catMap[$lowerCat] = $catId;
                    } catch (\Throwable) {
                        $catId = null;
                    }
                }
            }

            $note = ($noteCol >= 0 && isset($row[$noteCol])) ? trim((string)$row[$noteCol]) : '';
            $dedupeHash = sha1("{$userId}_{$parsedDate}_{$amountPaisa}_{$defaultAccountId}_{$note}");

            if (isset($hashSet[$dedupeHash]) && $skipDuplicates) {
                $skipped++;
                continue;
            }

            // Insert transaction with import_batch_id
            $sqlTx = "
                INSERT INTO transactions (
                    user_id, type, account_id, category_id, amount, tx_date,
                    note, tags, import_batch_id, dedupe_hash, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, 'import', ?, ?, ?, ?
                )
            ";

            Database::query($sqlTx, [
                $userId, $type, $defaultAccountId, $catId, $amountPaisa, $parsedDate,
                $note, $batchId, $dedupeHash, $now, $now
            ]);

            $hashSet[$dedupeHash] = true;
            $imported++;
        }

        // Update batch stats
        Database::query("UPDATE import_batches SET rows_imported = ?, rows_skipped = ? WHERE id = ?", [$imported, $skipped, $batchId]);

        return [
            'ok' => true,
            'batch_id' => $batchId,
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Rollback (Undo) an imported batch
     */
    public static function rollbackImport(int $userId, int $batchId): bool
    {
        // Delete all transactions linked to this batch
        Database::query("DELETE FROM transactions WHERE import_batch_id = ? AND user_id = ?", [$batchId, $userId]);
        // Update batch status to rolled_back
        return Database::execute("UPDATE import_batches SET status = 'rolled_back' WHERE id = ? AND user_id = ?", [$batchId, $userId]) > 0;
    }

    /**
     * Get recent import batches for a user
     */
    public static function getBatches(int $userId): array
    {
        $sql = "SELECT * FROM import_batches WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Parse flexible date strings into YYYY-MM-DD
     */
    public static function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (empty($raw)) return null;

        // Try Y-m-d format directly
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        // Try standard PHP strtotime
        $timestamp = strtotime($raw);
        if ($timestamp !== false && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        // Try d/m/Y or d-m-Y (common in Bangladesh/UK)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        return null;
    }
}
