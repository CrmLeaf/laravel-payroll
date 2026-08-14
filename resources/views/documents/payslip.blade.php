{{--
    Payslip template.

    Written for dompdf, which is a 2011-era CSS engine: no flexbox, no grid, no
    custom properties. Tables and floats are not nostalgia here, they are the
    only things that render. It is also why every colour is a literal - a var()
    resolves to nothing and the text comes out black on black.

    Publishing payroll-views forks this file into your application. Do that to
    change the branding; do not do it to change the arithmetic, which lives in
    the calculators where it can be tested.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $employee['name'] ?? '' }} - {{ $month }}</title>
    <style>
        @page {
            margin: {{ $margins['top'] }}mm {{ $margins['right'] }}mm {{ $margins['bottom'] }}mm {{ $margins['left'] }}mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1c2024;
            line-height: 1.45;
            margin: 0;
        }

        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 14px 0 4px; text-transform: uppercase; letter-spacing: 0.04em; }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; vertical-align: top; }

        .head td { border: 0; padding: 0 0 8px; }
        .logo { max-height: 46px; }

        .meta { font-size: 9px; color: #5b6672; }

        .band {
            background: #f1f4f7;
            border: 1px solid #d7dee6;
            padding: 6px 8px;
            margin: 8px 0 12px;
        }

        .grid td { width: 25%; border-bottom: 1px solid #e6ebf0; font-size: 9.5px; }
        .grid .label { color: #5b6672; }

        .money { text-align: right; white-space: nowrap; }

        .ledger { border: 1px solid #d7dee6; }
        .ledger th {
            background: #f1f4f7;
            text-align: left;
            border-bottom: 1px solid #d7dee6;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .ledger td { border-bottom: 1px solid #eef1f4; }
        .ledger tr.total td { border-top: 1px solid #b9c4cf; border-bottom: 0; font-weight: bold; }

        .net {
            border: 1px solid #0f5c4a;
            background: #eef6f3;
            padding: 8px 10px;
            margin-top: 12px;
            font-size: 12px;
        }
        .net .words { font-size: 9px; color: #33413c; font-weight: normal; }

        .working { margin-top: 14px; font-size: 8.5px; color: #4b5560; }
        .working td { border-bottom: 1px solid #eef1f4; }

        .foot {
            margin-top: 18px;
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
        <td style="width: 65%;">
            @if (!empty($company['logo']))
                <img src="{{ $company['logo'] }}" alt="" class="logo"><br>
            @endif
            <h1>{{ $company['name'] ?? '' }}</h1>
            <div class="meta">
                {!! nl2br(e($company['address'] ?? '')) !!}
                @if (!empty($company['gstin']))<br>GSTIN: {{ $company['gstin'] }}@endif
                @if (!empty($company['pan']))<br>PAN: {{ $company['pan'] }}@endif
            </div>
        </td>
        <td style="width: 35%; text-align: right;">
            <h1>Payslip</h1>
            <div class="meta">
                {{ $month }}<br>
                {{ $period_start->format('d M Y') }} - {{ $period_end->format('d M Y') }}
            </div>
        </td>
    </tr>
</table>

<div class="band">
    <table class="grid">
        <tr>
            <td class="label">Employee</td>
            <td><strong>{{ $employee['name'] ?? '' }}</strong></td>
            <td class="label">Employee code</td>
            <td>{{ $employee['code'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Designation</td>
            <td>{{ $employee['designation'] ?? '-' }}</td>
            <td class="label">Department</td>
            <td>{{ $employee['department'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Date of joining</td>
            <td>{{ $employee['date_of_joining'] ?? '-' }}</td>
            <td class="label">Location</td>
            <td>{{ $employee['location'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">PAN</td>
            <td>{{ $employee['pan'] ?? '-' }}</td>
            <td class="label">UAN</td>
            <td>{{ $employee['uan'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">ESIC number</td>
            <td>{{ $employee['esic_number'] ?? '-' }}</td>
            <td class="label">Bank account</td>
            <td>{{ $employee['bank_account'] ?? '-' }}{{ !empty($employee['ifsc']) ? ' / '.$employee['ifsc'] : '' }}</td>
        </tr>
        <tr>
            <td class="label">Working days</td>
            <td>{{ $attendance['working_days'] ?? '-' }}</td>
            <td class="label">Paid days / LOP</td>
            <td>{{ $attendance['paid_days'] ?? '-' }} / {{ $attendance['lop_days'] ?? 0 }}</td>
        </tr>
    </table>
</div>

<table>
    <tr>
        <td style="width: 50%; padding: 0 6px 0 0;">
            <h2>Earnings</h2>
            <table class="ledger">
                <tr><th>Head</th><th class="money">Amount</th></tr>
                @foreach ($earnings as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="money">{{ $row['amount']->format() }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Gross earnings</td>
                    <td class="money">{{ $gross_earnings->format() }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 50%; padding: 0 0 0 6px;">
            <h2>Deductions</h2>
            <table class="ledger">
                <tr><th>Head</th><th class="money">Amount</th></tr>
                @forelse ($deductions as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="money">{{ $row['amount']->format() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">No deductions this month.</td></tr>
                @endforelse
                <tr class="total">
                    <td>Total deductions</td>
                    <td class="money">{{ $total_deductions->format() }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="net">
    <table>
        <tr>
            <td><strong>Net pay</strong></td>
            <td class="money"><strong>{{ $net_pay->format() }}</strong></td>
        </tr>
        <tr>
            <td colspan="2" class="words">{{ $net_pay_in_words }}</td>
        </tr>
    </table>
</div>

@if (!empty($employer_contributions))
    <h2>Employer contributions</h2>
    {{-- Shown for transparency and explicitly not netted off the take-home:
         these are the employer's cost, not the employee's deduction. --}}
    <table class="ledger">
        <tr><th>Head</th><th class="money">Amount</th></tr>
        @foreach ($employer_contributions as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="money">{{ $row['amount']->format() }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if (!empty($workings))
    <h2>How these figures were arrived at</h2>
    <table class="working">
        @foreach ($workings as $step)
            <tr>
                <td>{{ $step['label'] ?? '' }}</td>
                <td>{{ $step['formula'] ?? '' }}</td>
                <td class="money">{{ $step['formatted'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if (!empty($notes))
    <h2>Notes</h2>
    <ul style="margin: 0; padding-left: 14px; font-size: 9px;">
        @foreach ($notes as $note)
            <li>{{ $note }}</li>
        @endforeach
    </ul>
@endif

<div class="foot">
    @foreach ($citations ?? [] as $citation)
        {{ $citation }}<br>
    @endforeach
    This is a computer-generated payslip and does not require a signature.
</div>

</body>
</html>
