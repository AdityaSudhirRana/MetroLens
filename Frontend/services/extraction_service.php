<?php
/**
 * AI Package Inspector - Pattern-Based Text Extraction Service
 * File: services/extraction_service.php
 */

class ExtractionService {

    /**
     * Parse raw OCR text into structured Legal Metrology JSON fields
     * 
     * @param string $rawText
     * @return array Extracted fields payload with confidence scores & extraction summary
     */
    public static function extractFields($rawText) {
        $lines = explode("\n", $rawText);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);

        $fields = [
            'product_name' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'brand' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'category' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'manufacturer' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'packer' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'importer' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'net_quantity' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'mrp' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'manufacturing_date' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'packing_date' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'import_date' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'batch_number' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'consumer_care_name' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'consumer_care_phone' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'consumer_care_email' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'consumer_care_address' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing'],
            'country_of_origin' => ['value' => '', 'confidence' => 0, 'source' => 'ocr', 'status' => 'missing']
        ];

        // 1. Extract Manufacturer / Packer / Importer
        foreach ($lines as $line) {
            if (preg_match('/(?:MFR|Manufactured|Mfg\.|Manufacturer|Manufactured\s*&\s*Marketed)\s*(?:by|:|-)?\s*(.+)/i', $line, $m)) {
                $fields['manufacturer']['value'] = trim($m[1]);
                $fields['manufacturer']['confidence'] = 94;
                $fields['manufacturer']['status'] = 'verified';
            } elseif (preg_match('/(?:Packed|Pkd|Packer)\s*(?:by|:|-)?\s*(.+)/i', $line, $m)) {
                $fields['packer']['value'] = trim($m[1]);
                $fields['packer']['confidence'] = 92;
                $fields['packer']['status'] = 'verified';
            } elseif (preg_match('/(?:Imported|Importer)\s*(?:by|:|-)?\s*(.+)/i', $line, $m)) {
                $fields['importer']['value'] = trim($m[1]);
                $fields['importer']['confidence'] = 91;
                $fields['importer']['status'] = 'verified';
            }
        }

        // 2. Extract Net Quantity
        foreach ($lines as $line) {
            if (preg_match('/(?:Net\s*(?:Wt|Weight|Quantity|Qty|Vol|Volume))\s*(?::|-)?\s*([0-9\.]+\s*(?:g|kg|ml|l|grams|kilograms|litres|pcs|units|N))/i', $line, $m)) {
                $fields['net_quantity']['value'] = trim($m[1]);
                $fields['net_quantity']['confidence'] = 98;
                $fields['net_quantity']['status'] = 'verified';
                break;
            } elseif (preg_match('/([0-9\.]+\s*(?:g|kg|ml|l|grams|litres))\b/i', $line, $m)) {
                if (empty($fields['net_quantity']['value'])) {
                    $fields['net_quantity']['value'] = trim($m[1]);
                    $fields['net_quantity']['confidence'] = 75;
                    $fields['net_quantity']['status'] = 'verified';
                }
            }
        }

        // 3. Extract Maximum Retail Price (MRP)
        foreach ($lines as $line) {
            if (preg_match('/(?:MRP|Maximum\s*Retail\s*Price)\s*(?:Rs\.|₹|:|-)?\s*([0-9\.,]+)/i', $line, $m)) {
                $fields['mrp']['value'] = '₹' . trim($m[1]);
                $fields['mrp']['confidence'] = 97;
                $fields['mrp']['status'] = 'verified';
                break;
            } elseif (preg_match('/(?:Rs\.|₹)\s*([0-9\.,]+)/i', $line, $m)) {
                if (empty($fields['mrp']['value'])) {
                    $fields['mrp']['value'] = '₹' . trim($m[1]);
                    $fields['mrp']['confidence'] = 82;
                    $fields['mrp']['status'] = 'verified';
                }
            }
        }

        // 4. Extract Manufacturing / Packing Date
        foreach ($lines as $line) {
            if (preg_match('/(?:Pkd|Packing|Mfg|Manufactured|Mfd)\s*(?:Date|:|-)?\s*([0-9]{1,2}[\/\.-][0-9]{2,4})/i', $line, $m)) {
                $fields['packing_date']['value'] = trim($m[1]);
                $fields['packing_date']['confidence'] = 94;
                $fields['packing_date']['status'] = 'verified';

                $fields['manufacturing_date']['value'] = trim($m[1]);
                $fields['manufacturing_date']['confidence'] = 94;
                $fields['manufacturing_date']['status'] = 'verified';
                break;
            }
        }

        // 5. Extract Batch Number
        foreach ($lines as $line) {
            if (preg_match('/(?:Batch|B\.No|Lot)\s*(?:No|:|-)?\s*([A-Z0-9\/-]+)/i', $line, $m)) {
                $fields['batch_number']['value'] = trim($m[1]);
                $fields['batch_number']['confidence'] = 93;
                $fields['batch_number']['status'] = 'verified';
                break;
            }
        }

        // 6. Extract Consumer Care Phone & Email
        foreach ($lines as $line) {
            if (preg_match('/(?:1800[-s]?[0-9]{2,3}[-s]?[0-9]{4}|[0-9]{10})/i', $line, $m)) {
                $fields['consumer_care_phone']['value'] = trim($m[0]);
                $fields['consumer_care_phone']['confidence'] = 96;
                $fields['consumer_care_phone']['status'] = 'verified';
            }
            if (preg_match('/([a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,6})/i', $line, $m)) {
                $fields['consumer_care_email']['value'] = trim($m[1]);
                $fields['consumer_care_email']['confidence'] = 97;
                $fields['consumer_care_email']['status'] = 'verified';
            }
        }

        // 7. Extract Country of Origin
        foreach ($lines as $line) {
            if (preg_match('/(?:Country\s*of\s*Origin|Made\s*in)\s*(?::|-)?\s*(.+)/i', $line, $m)) {
                $fields['country_of_origin']['value'] = trim($m[1]);
                $fields['country_of_origin']['confidence'] = 98;
                $fields['country_of_origin']['status'] = 'verified';
                break;
            }
        }

        // 8. Extract Product Name & Brand from Header lines
        if (!empty($lines)) {
            $firstLine = $lines[0];
            if (strlen($firstLine) > 3 && !preg_match('/(COMMODITY|UNREADABLE|OCR)/i', $firstLine)) {
                $fields['product_name']['value'] = $firstLine;
                $fields['product_name']['confidence'] = 95;
                $fields['product_name']['status'] = 'verified';

                $parts = explode(' ', $firstLine);
                $fields['brand']['value'] = $parts[0] ?? '';
                $fields['brand']['confidence'] = 90;
                $fields['brand']['status'] = 'verified';
            }
        }

        // Calculate Counts
        $detectedCount = 0;
        $missingCount = 0;
        $highConfCount = 0;
        $medConfCount = 0;
        $lowConfCount = 0;

        foreach ($fields as $key => $f) {
            if (!empty($f['value'])) {
                $detectedCount++;
                if ($f['confidence'] >= 90) $highConfCount++;
                elseif ($f['confidence'] >= 70) $medConfCount++;
                else $lowConfCount++;
            } else {
                $missingCount++;
            }
        }

        return [
            'fields' => $fields,
            'summary' => [
                'total_fields' => count($fields),
                'detected_fields' => $detectedCount,
                'missing_fields' => $missingCount,
                'high_confidence' => $highConfCount,
                'medium_confidence' => $medConfCount,
                'low_confidence' => $lowConfCount
            ]
        ];
    }
}
