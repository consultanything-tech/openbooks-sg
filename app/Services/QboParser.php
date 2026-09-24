<?php

namespace App\Services;

class QboParser
{
    /**
     * Known QBO/CSV header mappings to standard fields.
     */
    private const HEADER_MAP = [
        'date' => ['date', 'txn date', 'transaction date', 'posted date', 'posting date'],
        'description' => ['description', 'payee', 'name', 'memo', 'details', 'narration', 'payee name', 'vendor', 'customer'],
        'amount' => ['amount', 'amount ($)', 'amount($)', 'total', 'value', 'transaction amount'],
        'debit' => ['debit', 'debit amount', 'withdrawal', 'withdrawals', 'debits', 'paid out', 'outflow'],
        'credit' => ['credit', 'credit amount', 'deposit', 'deposits', 'credits', 'paid in', 'inflow'],
        'reference' => ['reference', 'ref', 'ref number', 'check number', 'cheque number', 'id', 'transaction id', 'fitid'],
        'category' => ['category', 'account', 'type'],
    ];

    /**
     * Parse QBO (QuickBooks Online CSV export) content.
     *
     * @return array<int, array{date: string, amount: float, description: string, reference: string, type: string}>
     */
    public function parse(string $content): array
    {
        $content = trim($content);

        if (empty($content)) {
            return [];
        }

        $lines = $this->splitLines($content);
        if (count($lines) < 2) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines);
        $headerIndex = $this->findHeaderRow($lines, $delimiter);

        if ($headerIndex === null) {
            return [];
        }

        $rawHeaders = str_getcsv($lines[$headerIndex], $delimiter);
        $headers = $this->mapHeaders($rawHeaders);

        if (empty($headers['date']) || (empty($headers['amount']) && empty($headers['debit']) && empty($headers['credit']))) {
            return [];
        }

        $transactions = [];

        for ($i = $headerIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }

            try {
                $tx = $this->parseRow($line, $delimiter, $rawHeaders, $headers);
                if ($tx !== null) {
                    $transactions[] = $tx;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $transactions;
    }

    /**
     * Parse a single CSV row into a transaction.
     */
    private function parseRow(string $line, string $delimiter, array $rawHeaders, array $headers): ?array
    {
        $fields = str_getcsv($line, $delimiter);
        $row = [];

        foreach ($rawHeaders as $idx => $header) {
            $row[strtolower(trim($header))] = isset($fields[$idx]) ? trim($fields[$idx]) : '';
        }

        // Extract date
        $dateKey = $headers['date'];
        $dateRaw = $dateKey !== null ? ($row[$dateKey] ?? '') : '';
        if (empty($dateRaw)) {
            return null;
        }

        try {
            $date = \Carbon\Carbon::parse($dateRaw)->toDateString();
        } catch (\Throwable $e) {
            // Try common QBO date formats
            $date = $this->parseQboDate($dateRaw);
            if ($date === null) {
                return null;
            }
        }

        // Extract amount
        $amount = 0.0;
        $type = null;

        if ($headers['amount'] !== null) {
            $amountRaw = $row[$headers['amount']] ?? '0';
            $amount = $this->parseAmount($amountRaw);

            if ($amount >= 0) {
                $type = 'income';
            } else {
                $type = 'expense';
            }
            $amount = abs($amount);
        } elseif ($headers['debit'] !== null || $headers['credit'] !== null) {
            $debit = $headers['debit'] !== null ? $this->parseAmount($row[$headers['debit']] ?? '0') : 0;
            $credit = $headers['credit'] !== null ? $this->parseAmount($row[$headers['credit']] ?? '0') : 0;

            if ($credit > 0 && $debit == 0) {
                $amount = $credit;
                $type = 'income';
            } elseif ($debit > 0) {
                $amount = $debit;
                $type = 'expense';
            } else {
                return null; // No amount data
            }
        } else {
            return null;
        }

        if ($amount <= 0) {
            return null;
        }

        // Extract description
        $description = '';
        if ($headers['description'] !== null) {
            $description = $row[$headers['description']] ?? '';
        }
        if (empty($description)) {
            $description = 'Bank Transaction';
        }

        // Extract reference
        $reference = '';
        if ($headers['reference'] !== null) {
            $reference = $row[$headers['reference']] ?? '';
        }

        return [
            'date' => $date,
            'amount' => $amount,
            'description' => mb_substr($description, 0, 255),
            'reference' => $reference,
            'type' => $type,
        ];
    }

    /**
     * Map raw CSV headers to standard field keys.
     *
     * @return array<string, string|null>
     */
    private function mapHeaders(array $rawHeaders): array
    {
        $normalized = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);
        $mapped = [];

        foreach (self::HEADER_MAP as $field => $aliases) {
            $mapped[$field] = null;
            foreach ($normalized as $norm) {
                if (in_array($norm, $aliases, true)) {
                    $mapped[$field] = $norm;
                    break;
                }
            }
        }

        return $mapped;
    }

    /**
     * Detect the CSV delimiter by examining the header line.
     */
    private function detectDelimiter(array $lines): string
    {
        $sample = $lines[0] ?? '';
        $delimiters = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($sample, $delim);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $delim;
            }
        }

        return $best;
    }

    /**
     * Find the header row index (skip preamble lines common in QBO exports).
     */
    private function findHeaderRow(array $lines, string $delimiter): ?int
    {
        $keywords = ['date', 'amount', 'payee', 'description', 'debit', 'credit', 'memo', 'name'];

        foreach ($lines as $idx => $line) {
            $lower = strtolower($line);
            $matchCount = 0;
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $matchCount++;
                }
            }
            if ($matchCount >= 2) {
                return $idx;
            }
            // Only scan first 15 lines for header
            if ($idx >= 14) {
                break;
            }
        }

        return null;
    }

    /**
     * Parse an amount string, handling currency symbols, commas, parentheses.
     */
    private function parseAmount(string $raw): float
    {
        $raw = trim($raw);

        // Handle parentheses for negatives: (100.00) => -100.00
        $negative = false;
        if (preg_match('/^\((.+)\)$/', $raw, $m)) {
            $raw = $m[1];
            $negative = true;
        }

        // Remove currency symbols and commas
        $raw = preg_replace('/[^0-9.\-]/', '', $raw);

        $amount = (float) $raw;

        return $negative ? -abs($amount) : $amount;
    }

    /**
     * Try parsing QBO-specific date formats.
     */
    private function parseQboDate(string $raw): ?string
    {
        $formats = [
            'm/d/Y', 'mm/dd/yyyy', 'd/m/Y', 'dd/mm/yyyy',
            'Y-m-d', 'M d, Y', 'd-M-Y', 'm/d/y',
        ];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, trim($raw));
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Split content into lines, handling different line endings.
     */
    private function splitLines(string $content): array
    {
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Remove BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        return explode("\n", $content);
    }
}
