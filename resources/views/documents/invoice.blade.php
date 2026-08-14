{{--
    GST tax invoice template.

    Carries what rule 46 of the CGST Rules asks for: supplier and recipient
    names, addresses and GSTINs, a consecutive invoice number and date, the
    place of supply, the HSN or SAC against each line, the taxable value, the
    tax split by rate, the total in words, and a reverse-charge declaration
    where it applies.

    As with the payslip, dompdf renders this, so the layout is tables and the
    colours are literals.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax invoice {{ $number }}</title>
    <style>
        @page {
            margin: {{ $margins['top'] }}mm {{ $margins['right'] }}mm {{ $margins['bottom'] }}mm {{ $margins['left'] }}mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1c2024;
            line-height: 1.45;
            margin: 0;
        }

        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 10px; margin: 12px 0 4px; text-transform: uppercase; letter-spacing: 0.04em; }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; vertical-align: top; }

        .head td { border: 0; padding: 0 0 8px; }
        .logo { max-height: 46px; }
        .meta { font-size: 9px; color: #5b6672; }

        .parties td {
            border: 1px solid #d7dee6;
            width: 50%;
        }
        .parties .who { font-size: 9px; color: #5b6672; text-transform: uppercase; letter-spacing: 0.04em; }

        .lines th {
            background: #f1f4f7;
            border: 1px solid #d7dee6;
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .lines td { border: 1px solid #e6ebf0; }
        .money { text-align: right; white-space: nowrap; }
        .num { text-align: right; white-space: nowrap; }

        .totals td { border-bottom: 1px solid #eef1f4; }
        .totals tr.grand td {
            border-top: 1px solid #b9c4cf;
            border-bottom: 0;
            font-weight: bold;
            font-size: 11px;
        }

        .words {
            border: 1px solid #d7dee6;
            background: #f7f9fb;
            padding: 6px 8px;
            margin-top: 8px;
        }

        .flag {
            border: 1px solid #9a6b00;
            background: #fff6e2;
            color: #6b4c00;
            padding: 5px 8px;
            margin: 8px 0;
            font-size: 9px;
        }

        .foot {
            margin-top: 16px;
            font-size: 8px;
            color: #5b6672;
            border-top: 1px solid #d7dee6;
            padding-top: 6px;
        }
    </style>
</head>
<body>

<table class="head">
    <tr>
        <td style="width: 62%;">
            @if (!empty($supplier['logo']))
                <img src="{{ $supplier['logo'] }}" alt="" class="logo"><br>
            @endif
            <h1>{{ $supplier['name'] ?? '' }}</h1>
            <div class="meta">
                {!! nl2br(e($supplier['address'] ?? '')) !!}
                @if (!empty($supplier['gstin']))<br>GSTIN: {{ $supplier['gstin'] }}@endif
                @if (!empty($supplier['pan']))<br>PAN: {{ $supplier['pan'] }}@endif
                @if ($supplier_state)<br>State: {{ $supplier_state }} ({{ $supplier_state_code }})@endif
            </div>
        </td>
        <td style="width: 38%; text-align: right;">
            <h1>Tax invoice</h1>
            <div class="meta">
                No. <strong>{{ $number }}</strong><br>
                Dated {{ $date->format('d M Y') }}<br>
                Place of supply: {{ $place_of_supply ?? '-' }} ({{ $place_of_supply_code ?? '-' }})<br>
                Tax charged as {{ $tax_type }}
            </div>
        </td>
    </tr>
</table>

@if ($reverse_charge)
    <div class="flag">
        Tax payable on reverse charge basis - the recipient is liable to pay the tax on this supply under
        section 9(3)/9(4) of the CGST Act.
    </div>
@endif

@if ($place_of_supply_assumed)
    <div class="flag">
        No state was recorded for the recipient, so the place of supply has been taken as the supplier's own
        state and the tax split into CGST and SGST accordingly. Record the recipient's state to be certain.
    </div>
@endif

<table class="parties">
    <tr>
        <td>
            <div class="who">Billed to</div>
            <strong>{{ $recipient['name'] ?? '' }}</strong><br>
            <span class="meta">
                {!! nl2br(e($recipient['address'] ?? '')) !!}
                @if (!empty($recipient['gstin']))<br>GSTIN: {{ $recipient['gstin'] }}@endif
                @if (!empty($recipient['state']))<br>State: {{ $recipient['state'] }}@endif
            </span>
        </td>
        <td>
            <div class="who">Shipped to</div>
            <strong>{{ $recipient['ship_to_name'] ?? ($recipient['name'] ?? '') }}</strong><br>
            <span class="meta">
                {!! nl2br(e($recipient['ship_to_address'] ?? ($recipient['address'] ?? ''))) !!}
            </span>
        </td>
    </tr>
</table>

<h2>Particulars</h2>

<table class="lines">
    <thead>
        <tr>
            <th style="width: 3%;">#</th>
            <th>Description</th>
            <th style="width: 9%;">HSN/SAC</th>
            <th style="width: 8%;" class="num">Qty</th>
            <th style="width: 11%;" class="money">Rate</th>
            <th style="width: 12%;" class="money">Taxable</th>
            <th style="width: 7%;" class="num">GST %</th>
            @if ($inter_state)
                <th style="width: 12%;" class="money">IGST</th>
            @else
                <th style="width: 11%;" class="money">CGST</th>
                <th style="width: 11%;" class="money">SGST</th>
            @endif
            <th style="width: 12%;" class="money">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $line->description }}</td>
                <td>{{ $line->hsn }}</td>
                <td class="num">{{ rtrim(rtrim(number_format($line->quantity, 3, '.', ''), '0'), '.') }} {{ $line->unit }}</td>
                <td class="money">{{ $line->unitPrice->format() }}</td>
                <td class="money">{{ $line->taxableValue->format() }}</td>
                <td class="num">{{ $line->gstRate }}</td>
                @if ($inter_state)
                    <td class="money">{{ $line->igst->format() }}</td>
                @else
                    <td class="money">{{ $line->cgst->format() }}</td>
                    <td class="money">{{ $line->sgst->format() }}</td>
                @endif
                <td class="money">{{ $line->total->format() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>Rate-wise summary</h2>

<table class="lines">
    <thead>
        <tr>
            <th>HSN/SAC</th>
            <th class="num">GST %</th>
            <th class="money">Taxable value</th>
            @if ($inter_state)
                <th class="money">IGST</th>
            @else
                <th class="money">CGST</th>
                <th class="money">SGST</th>
            @endif
            <th class="money">Total tax</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($hsn_summary as $row)
            <tr>
                <td>{{ $row['hsn'] }}</td>
                <td class="num">{{ $row['gst_rate'] }}</td>
                <td class="money">{{ $row['taxable_value']->format() }}</td>
                @if ($inter_state)
                    <td class="money">{{ $row['igst']->format() }}</td>
                @else
                    <td class="money">{{ $row['cgst']->format() }}</td>
                    <td class="money">{{ $row['sgst']->format() }}</td>
                @endif
                <td class="money">{{ $row['total_tax']->format() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="margin-top: 10px;">
    <tr>
        <td style="width: 55%; padding: 0 8px 0 0;">
            <div class="words">
                <strong>Amount in words</strong><br>
                {{ $amount_in_words }}
            </div>

            @if ($terms)
                <h2>Terms</h2>
                <div class="meta">{!! nl2br(e($terms)) !!}</div>
            @endif

            @if (!empty($notes))
                <h2>Notes</h2>
                <ul style="margin: 0; padding-left: 14px;" class="meta">
                    @foreach ($notes as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            @endif
        </td>
        <td style="width: 45%; padding: 0;">
            <table class="totals">
                <tr>
                    <td>Taxable value</td>
                    <td class="money">{{ $taxable_value->format() }}</td>
                </tr>
                @if ($total_discount->isPositive())
                    <tr>
                        <td>Discount allowed</td>
                        <td class="money">− {{ $total_discount->format() }}</td>
                    </tr>
                @endif
                @if ($inter_state)
                    <tr>
                        <td>IGST</td>
                        <td class="money">{{ $igst->format() }}</td>
                    </tr>
                @else
                    <tr>
                        <td>CGST</td>
                        <td class="money">{{ $cgst->format() }}</td>
                    </tr>
                    <tr>
                        <td>SGST</td>
                        <td class="money">{{ $sgst->format() }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Total tax</td>
                    <td class="money">{{ $total_tax->format() }}</td>
                </tr>
                @unless ($round_off->isZero())
                    <tr>
                        <td>Round off</td>
                        <td class="money">{{ $round_off->format() }}</td>
                    </tr>
                @endunless
                <tr class="grand">
                    <td>Total payable</td>
                    <td class="money">{{ $grand_total->format() }}</td>
                </tr>
            </table>

            <div style="margin-top: 24px; text-align: right;" class="meta">
                For <strong>{{ $supplier['name'] ?? '' }}</strong><br><br><br>
                Authorised signatory
            </div>
        </td>
    </tr>
</table>

<div class="foot">
    Tax split as {{ $tax_type }} because the place of supply
    ({{ $place_of_supply_code ?? '-' }}) {{ $inter_state ? 'differs from' : 'matches' }}
    the supplier's state ({{ $supplier_state_code ?? '-' }}).
    Computer-generated invoice; no signature is required where digitally issued.
</div>

</body>
</html>
