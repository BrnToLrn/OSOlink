<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll {{ $from }} - {{ $to }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #ddd; padding:6px; text-align:left; }
        th { background:#f3f3f3; }
        .right { text-align:right; }
    </style>
</head>
<body>
    <h2>Payroll Report</h2>
    <p>Period: {{ $from }} to {{ $to }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Account</th>
                <th class="right">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payslips as $i => $p)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $p->employee->name ?? 'N/A' }}</td>
                <td>{{ $p->employee->bank_account ?? '' }}</td>
                <td class="right">{{ number_format($p->net_pay, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="right"><strong>Total Net:</strong></td>
                <td class="right"><strong>{{ number_format($totalNet, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>