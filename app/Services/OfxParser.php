<?php

namespace App\Services;

class OfxParser
{
    /**
     * Parse OFX content and return an array of transactions.
     * Supports OFX 1.x (SGML-like) and OFX 2.x (proper XML).
     *
     * @return array<int, array{date: string, amount: float, description: string, reference: string, type: string}>
     */
    public function parse(string $content): array
    {
        $content = trim($content);

        if (empty($content)) {
            return [];
        }

        // Detect OFX 2.x (proper XML with <?xml or <OFX> with xmlns)
        if (str_contains($content, '<?xml') && str_contains($content, 'xmlns')) {
            return $this->parseOfx2($content);
        }

        return $this->parseOfx1($content);
    }

    /**
     * Parse OFX 1.x SGML-like format using regex.
     */
    private function parseOfx1(string $content): array
    {
        $transactions = [];

        // Extract STMTTRN blocks
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $content, $blocks);

        if (empty($blocks[1])) {
            // Try without closing tags (some OFX 1.x files omit them)
            preg_match_all('/<STMTTRN>(.*?)(?=<STMTTRN>|<\/BANKTRANSLIST>|<\/STMTTRNRS>|$)/si', $content, $blocks);
        }

        if (empty($blocks[1])) {
            return [];
        }

        foreach ($blocks[1] as $block) {
            try {
                $tx = $this->parseOfx1Block($block);
                if ($tx !== null) {
                    $transactions[] = $tx;
                }
            } catch (\Throwable $e) {
                // Skip malformed entries
                continue;
            }
        }

        return $transactions;
    }

    /**
     * Parse a single OFX 1.x STMTTRN block.
     */
    private function parseOfx1Block(string $block): ?array
    {
        $date = $this->extractTag($block, 'DTPOSTED') ?? $this->extractTag($block, 'DTUSER');
        $amount = $this->extractTag($block, 'TRNAMT');
        $name = $this->extractTag($block, 'NAME');
        $memo = $this->extractTag($block, 'MEMO');
        $fitid = $this->extractTag($block, 'FITID');
        $trntype = strtoupper($this->extractTag($block, 'TRNTYPE') ?? '');

        if ($amount === null) {
            return null;
        }

        $amountFloat = (float) $amount;
        $parsedDate = $this->parseOfxDate($date ?? '');

        // Build description from NAME and MEMO
        $description = trim(($name ?? '') . ($memo ? ' - ' . $memo : ''));
        if (empty($description)) {
            $description = $trntype ?: 'Bank Transaction';
        }

        // Determine type: use TRNTYPE if available, otherwise infer from amount sign
        $type = $this->determineType($amountFloat, $trntype);

        return [
            'date' => $parsedDate,
            'amount' => abs($amountFloat),
            'description' => mb_substr($description, 0, 255),
            'reference' => $fitid ?? '',
            'type' => $type,
        ];
    }

    /**
     * Parse OFX 2.x XML format using SimpleXML.
     */
    private function parseOfx2(string $content): array
    {
        $transactions = [];

        // Suppress XML warnings for malformed content
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();

        if ($xml === false) {
            // Fall back to OFX 1.x parser
            return $this->parseOfx1($content);
        }

        // Register namespaces if present
        $namespaces = $xml->getNamespaces(true);

        // Find all STMTTRN elements (try direct and namespaced)
        $stmttrns = $xml->xpath('//STMTTRN');
        if (empty($stmttrns) && !empty($namespaces)) {
            foreach ($namespaces as $prefix => $uri) {
                $xml->registerXPathNamespace($prefix, $uri);
                $stmttrns = $xml->xpath("//{$prefix}:STMTTRN");
                if (!empty($stmttrns)) {
                    break;
                }
            }
        }

        if (empty($stmttrns)) {
            return [];
        }

        foreach ($stmttrns as $trn) {
            try {
                $date = (string) ($trn->DTPOSTED ?? $trn->DTUSER ?? '');
                $amount = (string) ($trn->TRNAMT ?? '');
                $name = (string) ($trn->NAME ?? '');
                $memo = (string) ($trn->MEMO ?? '');
                $fitid = (string) ($trn->FITID ?? '');
                $trntype = strtoupper((string) ($trn->TRNTYPE ?? ''));

                if ($amount === '') {
                    continue;
                }

                $amountFloat = (float) $amount;
                $parsedDate = $this->parseOfxDate($date);

                $description = trim($name . ($memo ? ' - ' . $memo : ''));
                if (empty($description)) {
                    $description = $trntype ?: 'Bank Transaction';
                }

                $type = $this->determineType($amountFloat, $trntype);

                $transactions[] = [
                    'date' => $parsedDate,
                    'amount' => abs($amountFloat),
                    'description' => mb_substr($description, 0, 255),
                    'reference' => $fitid,
                    'type' => $type,
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $transactions;
    }

    /**
     * Extract a tag value from SGML-like OFX content.
     */
    private function extractTag(string $block, string $tag): ?string
    {
        // Try with closing tag first
        if (preg_match('/<' . $tag . '>\s*(.*?)\s*<\/' . $tag . '>/si', $block, $m)) {
            return trim($m[1]);
        }
        // OFX 1.x often omits closing tags
        if (preg_match('/<' . $tag . '>\s*([^\r\n<]+)/si', $block, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Parse OFX date format (YYYYMMDD or YYYYMMDDHHMMSS).
     */
    private function parseOfxDate(string $date): string
    {
        $date = trim($date);

        if (empty($date)) {
            return now()->toDateString();
        }

        // Remove timezone bracket info like [GMT]
        $date = preg_replace('/\[.*\]/', '', $date);

        // YYYYMMDDHHMMSS or YYYYMMDD
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $date, $m)) {
            return sprintf('%s-%s-%s', $m[1], $m[2], $m[3]);
        }

        // Try Carbon as fallback
        try {
            return \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    }

    /**
     * Determine transaction type from amount and OFX TRNTYPE.
     */
    private function determineType(float $amount, string $trntype): string
    {
        // Credit types in OFX
        $creditTypes = ['CREDIT', 'DEP', 'DIRECTDEP', 'PAYMENT', 'REVERSAL', 'XFER'];

        if (in_array($trntype, $creditTypes, true)) {
            return 'income';
        }

        // Debit types in OFX
        $debitTypes = ['DEBIT', 'CHECK', 'ATM', 'POS', 'FEE', 'SRVCHG', 'XFER'];

        if (in_array($trntype, $debitTypes, true) && $amount <= 0) {
            return 'expense';
        }

        // Fall back to amount sign
        return $amount >= 0 ? 'income' : 'expense';
    }
}
