<?php

declare(strict_types=1);

namespace Crmleaf\Payroll\Laravel\View\Components;

final class ComplianceCalendar extends PayrollComponent
{
    public const TOOL = 'compliance-calendar';

    protected function defaultHeading(): string
    {
        return 'Statutory compliance calendar';
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'financial_year',
                'label' => 'Financial year',
                'type' => 'text',
                'default' => $this->currentFinancialYear(),
                'placeholder' => '2025-26',
                'hint' => 'Indian financial years run 1 April to 31 March.',
            ],
            [
                'name' => 'qrmp',
                'label' => 'On the QRMP scheme',
                'type' => 'checkbox',
                'default' => false,
                'hint' => 'GSTR-1 and GSTR-3B then fall quarterly, with PMT-06 payments in between.',
            ],
        ];
    }

    public function headline(): array
    {
        return [
            'Financial year' => 'financial_year',
            'Due dates' => 'event_count',
        ];
    }

    /**
     * The generated events, ready to list. Kept separate from the headline
     * because this is the whole point of the tool and deserves its own table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        $events = $this->result['events'] ?? [];

        return is_array($events) ? $events : [];
    }

    private function currentFinancialYear(): string
    {
        $now = new \DateTimeImmutable('today');
        $startYear = (int) $now->format('n') >= 4 ? (int) $now->format('Y') : (int) $now->format('Y') - 1;

        return sprintf('%d-%02d', $startYear, ($startYear + 1) % 100);
    }
}
