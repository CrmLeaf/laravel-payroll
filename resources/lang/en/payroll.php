<?php

declare(strict_types=1);

/*
 * Publish with --tag=payroll-lang to translate the components, and add a
 * sibling directory (resources/lang/vendor/payroll/hi, say) for another
 * language. Statutory terms are left in English on purpose: "EPS", "section
 * 87A" and "HSN" are what the forms, the challans and the portal say, and
 * translating them makes the payslip harder to reconcile rather than easier.
 */

return [

    'actions' => [
        'calculate' => 'Calculate',
        'show_working' => 'Show the working',
    ],

    'labels' => [
        'as_of' => 'Rates as on',
        'result' => 'Result',
        'working' => 'How this figure was arrived at',
        'citations' => 'Statutory basis',
        'step' => 'Step',
        'formula' => 'Formula',
        'amount' => 'Amount',
    ],

    'document' => [
        'payslip' => 'Payslip',
        'invoice' => 'Tax invoice',
        'earnings' => 'Earnings',
        'deductions' => 'Deductions',
        'gross_earnings' => 'Gross earnings',
        'total_deductions' => 'Total deductions',
        'net_pay' => 'Net pay',
        'amount_in_words' => 'Amount in words',
        'employer_contributions' => 'Employer contributions',
        'computer_generated' => 'This is a computer-generated document and does not require a signature.',
    ],

    'invoice' => [
        'billed_to' => 'Billed to',
        'shipped_to' => 'Shipped to',
        'place_of_supply' => 'Place of supply',
        'hsn' => 'HSN/SAC',
        'taxable_value' => 'Taxable value',
        'round_off' => 'Round off',
        'total_payable' => 'Total payable',
        'reverse_charge' => 'Tax payable on reverse charge basis.',
    ],

];
