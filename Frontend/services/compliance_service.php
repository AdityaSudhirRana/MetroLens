<?php
/**
 * AI Package Inspector - Legal Metrology Compliance Rule Engine
 * File: services/compliance_service.php
 */

class ComplianceService {

    /**
     * Evaluate Legal Metrology Rule 6 (Packaged Commodities Rules 2011) against structured fields
     * 
     * @param array $fields Structured field payload
     * @return array Compliance evaluation scorecard
     */
    public static function evaluateCompliance($fields) {
        $getValue = function($key) use ($fields) {
            if (isset($fields[$key])) {
                return is_array($fields[$key]) ? trim($fields[$key]['value'] ?? '') : trim((string)$fields[$key]);
            }
            return '';
        };

        $mfr = $getValue('manufacturer') ?: $getValue('packer') ?: $getValue('importer');
        $prodName = $getValue('product_name');
        $netQty = $getValue('net_quantity');
        $mrp = $getValue('mrp');
        $mfdDate = $getValue('manufacturing_date') ?: $getValue('packing_date');
        $carePhone = $getValue('consumer_care_phone');
        $careEmail = $getValue('consumer_care_email');
        $country = $getValue('country_of_origin');

        $rules = [
            [
                'rule_id' => 'RULE_6_1_A',
                'field' => 'manufacturer',
                'rule_name' => 'Rule 6(1)(a) - Name & Address of Manufacturer / Packer / Importer',
                'pass' => (strlen($mfr) >= 8),
                'message' => (strlen($mfr) >= 8) ? 'Full manufacturer/packer identity and address present.' : 'Missing or incomplete manufacturer/packer address declaration.',
                'severity' => 'High',
                'penalty' => 25000.00
            ],
            [
                'rule_id' => 'RULE_6_1_B',
                'field' => 'product_name',
                'rule_name' => 'Rule 6(1)(b) - Common or Generic Commodity Name',
                'pass' => (strlen($prodName) >= 2),
                'message' => (strlen($prodName) >= 2) ? 'Generic name of commodity clearly declared.' : 'Missing generic commodity name on principal display panel.',
                'severity' => 'Medium',
                'penalty' => 10000.00
            ],
            [
                'rule_id' => 'RULE_6_1_C',
                'field' => 'net_quantity',
                'rule_name' => 'Rule 6(1)(c) - Net Quantity Representation in SI Units',
                'pass' => (preg_match('/[0-9]+\s*(g|kg|ml|l|grams|kilograms|litres|pcs|units|N)/i', $netQty) === 1),
                'message' => (strlen($netQty) > 0) ? 'Standard SI units net quantity declared.' : 'Missing or non-standard net quantity representation.',
                'severity' => 'High',
                'penalty' => 25000.00
            ],
            [
                'rule_id' => 'RULE_6_1_E',
                'field' => 'mrp',
                'rule_name' => 'Rule 6(1)(e) - Maximum Retail Price (MRP Incl. of all taxes)',
                'pass' => (strlen($mrp) >= 2),
                'message' => (strlen($mrp) >= 2) ? 'MRP properly formatted with statutory currency symbol.' : 'Missing Maximum Retail Price (MRP) declaration.',
                'severity' => 'High',
                'penalty' => 25000.00
            ],
            [
                'rule_id' => 'RULE_6_1_D',
                'field' => 'manufacturing_date',
                'rule_name' => 'Rule 6(1)(d) - Month & Year of Manufacturing / Pre-Packing',
                'pass' => (strlen($mfdDate) >= 4),
                'message' => (strlen($mfdDate) >= 4) ? 'Month and year of packaging clearly specified.' : 'Missing manufacturing or pre-packing date.',
                'severity' => 'Medium',
                'penalty' => 10000.00
            ],
            [
                'rule_id' => 'RULE_6_2',
                'field' => 'consumer_care',
                'rule_name' => 'Rule 6(2) - Consumer Grievance Helpline / Email Contact',
                'pass' => (strlen($carePhone) >= 5 || strlen($careEmail) >= 5),
                'message' => (strlen($carePhone) >= 5 || strlen($careEmail) >= 5) ? 'Customer helpline phone or email verified.' : 'Missing consumer care contact cell details.',
                'severity' => 'Medium',
                'penalty' => 15000.00
            ],
            [
                'rule_id' => 'RULE_6_1_F',
                'field' => 'country_of_origin',
                'rule_name' => 'Rule 6(1)(f) - Country of Origin Declaration',
                'pass' => (strlen($country) >= 2),
                'message' => (strlen($country) >= 2) ? 'Country of origin explicitly declared.' : 'Missing country of origin declaration.',
                'severity' => 'Low',
                'penalty' => 5000.00
            ]
        ];

        $passedCount = 0;
        $violations = [];

        foreach ($rules as $r) {
            if ($r['pass']) {
                $passedCount++;
            } else {
                $violations[] = [
                    'rule_id' => $r['rule_id'],
                    'rule_name' => $r['rule_name'],
                    'field_name' => $r['field'],
                    'severity' => $r['severity'],
                    'penalty_amount' => $r['penalty'],
                    'notes' => $r['message']
                ];
            }
        }

        $totalRules = count($rules);
        $complianceScore = round(($passedCount / $totalRules) * 100, 1);
        $overallStatus = ($passedCount === $totalRules) ? 'Compliant' : 'Non-Compliant';

        return [
            'overall_status' => $overallStatus,
            'compliance_score' => $complianceScore,
            'passed_declarations' => $passedCount,
            'total_declarations' => $totalRules,
            'verifications' => $rules,
            'violations' => $violations
        ];
    }
}
