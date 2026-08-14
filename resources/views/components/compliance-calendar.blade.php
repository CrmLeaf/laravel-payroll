{{--
    The compliance calendar. Same shared body as the calculators, plus the
    table of dated events - which is the whole output here, so it does not
    belong hidden behind the "show the working" fold.
--}}
@include('payroll::components.partials.tool')

@if ($result && $events())
    <div class="payroll-tool" aria-label="Due dates">
        <h2>Due dates for {{ $result['financial_year'] ?? '' }}</h2>

        <div class="payroll-tool__scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Due</th>
                        <th scope="col">Obligation</th>
                        <th scope="col">Authority</th>
                        <th scope="col">Period</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events() as $event)
                        <tr>
                            <td>{{ $event['date'] ?? '' }}</td>
                            <td>
                                {{ $event['title'] ?? ($event['code'] ?? '') }}
                                @if (!empty($event['description']))
                                    <span class="payroll-tool__hint">{{ $event['description'] }}</span>
                                @endif
                            </td>
                            <td>{{ $event['authority'] ?? '' }}</td>
                            <td>{{ $event['period'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (!empty($result['unscheduled']))
            <ul class="payroll-tool__citations">
                @foreach ($result['unscheduled'] as $item)
                    <li>{{ $item['code'] ?? '' }} - {{ $item['reason'] ?? '' }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
