{{--
    The shared body of every calculator component.

    Works with JavaScript switched off: the form posts to the tool's route, the
    controller redirects back, and the result renders from the session. The
    data-payroll-* attributes are what @crmleaf/payroll-js binds to when it is
    loaded, so the same markup upgrades to an in-browser calculation without a
    round trip. Nothing here depends on the bundle being present.
--}}
@if ($styles)
    @include('payroll::components.partials.styles')
@endif

<div
    class="payroll-tool"
    id="{{ $id }}"
    @if ($enhance) data-payroll-tool="{{ $tool }}" @endif
    {{ $attributes->except(['result', 'action', 'heading']) }}
>
    <h2>{{ $heading }}</h2>

    {{-- $errors is shared by the web middleware group; guard it so the
         component still renders when it is not (a bare view render, a test). --}}
    @if (isset($errors) && $errors->any())
        <div class="payroll-tool__errors" role="alert">
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" data-payroll-form>
        @csrf

        <div class="payroll-tool__grid">
            @foreach ($fields() as $field)
                @php
                    $name = $field['name'];
                    $fieldId = $id.'-'.\Illuminate\Support\Str::slug(str_replace(['[', ']'], '-', $name));
                    $current = $old($name, $field['default'] ?? null);
                    $step = $field['step'] ?? match ($field['type'] ?? 'text') {
                        'money' => '0.01',
                        'integer' => '1',
                        'number' => 'any',
                        default => null,
                    };
                @endphp

                @if (($field['type'] ?? 'text') === 'checkbox')
                    <div class="payroll-tool__field payroll-tool__field--check">
                        {{-- The hidden 0 makes an unticked box post a value, so
                             "employer does not restrict to the ceiling" is a
                             statement rather than a silence the server has to
                             guess at. --}}
                        <input type="hidden" name="{{ $name }}" value="0">
                        <input
                            type="checkbox"
                            id="{{ $fieldId }}"
                            name="{{ $name }}"
                            value="1"
                            @checked(filter_var($current, FILTER_VALIDATE_BOOLEAN))
                        >
                        <span>
                            <label for="{{ $fieldId }}">{{ $field['label'] }}</label>
                            @isset($field['hint'])
                                <span class="payroll-tool__hint">{{ $field['hint'] }}</span>
                            @endisset
                        </span>
                    </div>
                @else
                    <div class="payroll-tool__field">
                        <label for="{{ $fieldId }}">
                            {{ $field['label'] }}@if ($field['required'] ?? false)<span aria-hidden="true"> *</span>@endif
                        </label>

                        @if (($field['type'] ?? 'text') === 'select')
                            <select
                                id="{{ $fieldId }}"
                                name="{{ $name }}"
                                @required($field['required'] ?? false)
                            >
                                @unless ($field['required'] ?? false)
                                    <option value="">-</option>
                                @endunless
                                @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                    <option
                                        value="{{ $optionValue }}"
                                        @selected((string) $current === (string) $optionValue)
                                    >{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="{{ match ($field['type'] ?? 'text') {
                                    'money', 'number', 'integer' => 'number',
                                    'date' => 'date',
                                    'month' => 'month',
                                    default => 'text',
                                } }}"
                                id="{{ $fieldId }}"
                                name="{{ $name }}"
                                value="{{ $current }}"
                                @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                                @if ($step) step="{{ $step }}" @endif
                                @if (($field['type'] ?? '') === 'money') inputmode="decimal" @endif
                                @if (in_array($field['type'] ?? '', ['money', 'integer'], true) && !isset($field['min'])) min="0" @endif
                                @isset($field['min']) min="{{ $field['min'] }}" @endisset
                                @isset($field['max']) max="{{ $field['max'] }}" @endisset
                                @required($field['required'] ?? false)
                            >
                        @endif

                        @isset($field['hint'])
                            <span class="payroll-tool__hint">{{ $field['hint'] }}</span>
                        @endisset
                    </div>
                @endif
            @endforeach
        </div>

        <div class="payroll-tool__actions">
            <button type="submit">Calculate</button>
        </div>
    </form>

    <div class="payroll-tool__result" data-payroll-result @unless ($result) hidden @endunless>
        @if ($result)
            <dl class="payroll-tool__headline">
                @foreach ($headline() as $label => $key)
                    <div class="payroll-tool__figure">
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value($key) }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($explanation())
                <p class="payroll-tool__formula">{{ $explanation() }}</p>
            @endif

            @if ($steps())
                <details class="payroll-tool__working">
                    <summary>Show the working</summary>
                    <div class="payroll-tool__scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Step</th>
                                    <th scope="col">Formula</th>
                                    <th scope="col" class="payroll-tool__num">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($steps() as $step)
                                    <tr>
                                        <td>{{ $step['label'] ?? '' }}</td>
                                        <td>{{ $step['formula'] ?? '' }}</td>
                                        <td class="payroll-tool__num">{{ $step['formatted'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif

            @if ($citations())
                <ul class="payroll-tool__citations">
                    @foreach ($citations() as $citation)
                        <li>{{ $citation }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</div>
