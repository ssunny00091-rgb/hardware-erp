<?php

declare(strict_types=1);

function gst_state_label(string $gst): string
{
    $code = substr(trim($gst), 0, 2);
    $states = [
        '01' => 'Jammu & Kashmir',
        '02' => 'Himachal Pradesh',
        '03' => 'Punjab',
        '04' => 'Chandigarh',
        '05' => 'Uttarakhand',
        '06' => 'Haryana',
        '07' => 'Delhi',
        '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh',
        '10' => 'Bihar',
        '11' => 'Sikkim',
        '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland',
        '14' => 'Manipur',
        '15' => 'Mizoram',
        '16' => 'Tripura',
        '17' => 'Meghalaya',
        '18' => 'Assam',
        '19' => 'West Bengal',
        '20' => 'Jharkhand',
        '21' => 'Odisha',
        '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh',
        '24' => 'Gujarat',
        '27' => 'Maharashtra',
        '29' => 'Karnataka',
        '32' => 'Kerala',
        '33' => 'Tamil Nadu',
        '36' => 'Telangana',
        '37' => 'Andhra Pradesh',
    ];

    if ($code === '') {
        return '';
    }

    return isset($states[$code]) ? $code . '-' . $states[$code] : $code;
}

function two_digit_words(int $n): string
{
    $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
    ];
    $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    if ($n < 20) {
        return $ones[$n];
    }

    $t = intdiv($n, 10);
    $o = $n % 10;
    return trim($tens[$t] . ($o ? ' ' . $ones[$o] : ''));
}

function indian_number_to_words(int $number): string
{
    if ($number === 0) {
        return 'Zero';
    }

    $parts = [];
    $crore = intdiv($number, 10000000);
    $number %= 10000000;
    $lakh = intdiv($number, 100000);
    $number %= 100000;
    $thousand = intdiv($number, 1000);
    $number %= 1000;
    $hundred = intdiv($number, 100);
    $rest = $number % 100;

    if ($crore > 0) {
        $parts[] = indian_number_to_words($crore) . ' Crore';
    }
    if ($lakh > 0) {
        $parts[] = two_digit_words($lakh) . ' Lakh';
    }
    if ($thousand > 0) {
        $parts[] = two_digit_words($thousand) . ' Thousand';
    }
    if ($hundred > 0) {
        $parts[] = two_digit_words($hundred) . ' Hundred';
    }
    if ($rest > 0) {
        $parts[] = two_digit_words($rest);
    }

    return implode(' ', $parts);
}

function amount_in_words(float|int|string $amount): string
{
    $amount = round((float) $amount, 2);
    $rupees = (int) floor($amount + 0.00001);
    $paise = (int) round(($amount - $rupees) * 100);
    $text = indian_number_to_words($rupees) . ' Rupees';
    if ($paise > 0) {
        $text .= ' and ' . two_digit_words($paise) . ' Paise';
    }
    return $text . ' Only';
}

function peek_next_invoice_number(PDO $pdo): string
{
    $maxNumeric = 0;
    try {
        $maxNumeric = (int) $pdo->query(
            "SELECT COALESCE(MAX(CAST(invoice_no AS UNSIGNED)), 0)
             FROM sales
             WHERE invoice_no REGEXP '^[0-9]+$'"
        )->fetchColumn();
    } catch (Throwable $e) {
        $maxNumeric = 0;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn();

    return (string) (max($maxNumeric, $count) + 1);
}

function generate_invoice_number(?PDO $pdo = null): string
{
    $pdo = $pdo ?? db();
    $pdo->query("SELECT GET_LOCK('hardware_erp_invoice_no', 10)");
    try {
        $n = (int) peek_next_invoice_number($pdo);
        $check = $pdo->prepare('SELECT id FROM sales WHERE invoice_no = :no LIMIT 1');
        while (true) {
            $check->execute(['no' => (string) $n]);
            if (!$check->fetch()) {
                return (string) $n;
            }
            $n++;
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('hardware_erp_invoice_no')");
    }
}
