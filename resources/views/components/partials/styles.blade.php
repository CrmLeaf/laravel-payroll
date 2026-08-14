{{--
    Scoped styles for the calculator components.

    Everything is namespaced under .payroll-tool and written in plain CSS with
    custom properties - no Tailwind, no Bootstrap, no build step. These get
    embedded in other people's pages, and a component that only looks right
    when the host happens to use the same framework is not embeddable. Override
    any of the variables from your own stylesheet to reskin the lot.

    @once so twelve components on one page emit one <style>.
--}}
@once
<style>
    .payroll-tool {
        --payroll-fg: #1c2024;
        --payroll-muted: #5b6672;
        --payroll-bg: #ffffff;
        --payroll-panel: #f6f8fa;
        --payroll-border: #d7dee6;
        --payroll-accent: #0f5c4a;
        --payroll-accent-fg: #ffffff;
        --payroll-radius: 8px;
        --payroll-gap: 1rem;

        box-sizing: border-box;
        color: var(--payroll-fg);
        background: var(--payroll-bg);
        border: 1px solid var(--payroll-border);
        border-radius: var(--payroll-radius);
        padding: 1.25rem;
        max-width: 46rem;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 15px;
        line-height: 1.5;
    }

    .payroll-tool *,
    .payroll-tool *::before,
    .payroll-tool *::after { box-sizing: inherit; }

    .payroll-tool h2 {
        margin: 0 0 1rem;
        font-size: 1.15rem;
        line-height: 1.3;
        font-weight: 600;
    }

    .payroll-tool__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
        gap: var(--payroll-gap);
    }

    .payroll-tool__field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }

    .payroll-tool__field--check {
        flex-direction: row;
        align-items: flex-start;
        gap: 0.5rem;
        grid-column: 1 / -1;
    }

    .payroll-tool__field label { font-weight: 500; }

    .payroll-tool__hint {
        color: var(--payroll-muted);
        font-size: 0.8125rem;
        line-height: 1.4;
    }

    .payroll-tool input[type="text"],
    .payroll-tool input[type="number"],
    .payroll-tool input[type="date"],
    .payroll-tool input[type="month"],
    .payroll-tool select {
        width: 100%;
        padding: 0.5rem 0.625rem;
        font: inherit;
        color: inherit;
        background: var(--payroll-bg);
        border: 1px solid var(--payroll-border);
        border-radius: 6px;
    }

    .payroll-tool input:focus-visible,
    .payroll-tool select:focus-visible,
    .payroll-tool button:focus-visible {
        outline: 2px solid var(--payroll-accent);
        outline-offset: 1px;
    }

    .payroll-tool__actions { margin-top: 1.25rem; }

    .payroll-tool button {
        font: inherit;
        font-weight: 600;
        color: var(--payroll-accent-fg);
        background: var(--payroll-accent);
        border: 0;
        border-radius: 6px;
        padding: 0.6rem 1.25rem;
        cursor: pointer;
    }

    .payroll-tool__errors {
        margin: 0 0 1rem;
        padding: 0.75rem 1rem;
        border: 1px solid #d94b4b;
        border-left-width: 4px;
        border-radius: 6px;
        background: #fdf3f3;
        color: #7d1f1f;
    }

    .payroll-tool__errors ul { margin: 0; padding-left: 1.1rem; }

    .payroll-tool__result {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--payroll-border);
    }

    .payroll-tool__headline {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .payroll-tool__figure {
        background: var(--payroll-panel);
        border-radius: 6px;
        padding: 0.75rem 0.875rem;
    }

    .payroll-tool__figure dt {
        font-size: 0.8125rem;
        color: var(--payroll-muted);
        margin-bottom: 0.15rem;
    }

    .payroll-tool__figure dd {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .payroll-tool__formula {
        font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 0.8125rem;
        background: var(--payroll-panel);
        border-radius: 6px;
        padding: 0.6rem 0.75rem;
        overflow-x: auto;
    }

    .payroll-tool__working { margin-top: 1rem; }
    .payroll-tool__working summary { cursor: pointer; font-weight: 600; }

    .payroll-tool__scroll { overflow-x: auto; }

    .payroll-tool table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.75rem;
        font-size: 0.875rem;
    }

    .payroll-tool th,
    .payroll-tool td {
        text-align: left;
        padding: 0.4rem 0.5rem;
        border-bottom: 1px solid var(--payroll-border);
        vertical-align: top;
    }

    .payroll-tool td.payroll-tool__num,
    .payroll-tool th.payroll-tool__num {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .payroll-tool__citations {
        margin-top: 1rem;
        font-size: 0.8125rem;
        color: var(--payroll-muted);
    }

    .payroll-tool__citations li { margin-bottom: 0.2rem; }

    @media (prefers-color-scheme: dark) {
        .payroll-tool {
            --payroll-fg: #e7edf3;
            --payroll-muted: #9fb0c0;
            --payroll-bg: #14181c;
            --payroll-panel: #1c2228;
            --payroll-border: #2f3944;
            --payroll-accent: #3fae8f;
            --payroll-accent-fg: #08120f;
        }

        .payroll-tool__errors { background: #2a1616; color: #f6c9c9; }
    }
</style>
@endonce
