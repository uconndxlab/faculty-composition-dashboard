<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Faculty Dashboard') — UConn Academic Operations</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=rethink-sans:400,500,600,700,800" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body>
@php
    $navItems = [
        ['label' => 'Workspace', 'short' => 'W', 'href' => url('/'), 'active' => request()->is('/') || request()->is('trends')],
        ['label' => 'Modeling', 'short' => 'M', 'href' => url('/modeling'), 'active' => request()->is('modeling')],
        ['label' => 'Imports', 'short' => 'I', 'href' => url('/imports'), 'active' => request()->is('imports')],
    ];
@endphp
<div class="app-shell">

    <header class="app-mobile-bar">
        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#appMobileNav" aria-controls="appMobileNav">
            Menu
        </button>
        <a class="app-mobile-brand" href="{{ url('/') }}">Faculty Dashboard</a>
    </header>

    <aside class="app-sidebar" aria-label="Primary navigation">
        <button class="sidebar-collapse-toggle app-sidebar-toggle" type="button" data-app-sidebar-toggle aria-label="Collapse app navigation">
            ‹
        </button>
        <a class="app-brand" href="{{ url('/') }}">
            <span class="app-brand-mark">FD</span>
            <span class="app-brand-copy">
                <span class="app-brand-title">Faculty Dashboard</span>
                <span class="app-brand-subtitle">UConn Academic Operations</span>
            </span>
        </a>
        <nav class="app-nav">
            @foreach($navItems as $item)
                <a class="app-nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}" data-short-label="{{ $item['short'] }}" title="{{ $item['label'] }}">
                    <span class="app-nav-text">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="app-sidebar-footer">
            <div class="app-sidebar-label">Workspace</div>
            <div class="app-sidebar-note">Institution snapshots, peer comparisons, and import workflows.</div>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="appMobileNav" aria-labelledby="appMobileNavLabel">
        <div class="offcanvas-header">
            <div>
                <div class="app-brand-title" id="appMobileNavLabel">Faculty Dashboard</div>
                <div class="app-brand-subtitle">UConn Academic Operations</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="app-nav">
                @foreach($navItems as $item)
                    <a class="app-nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    <main class="app-main page-frame">
        @if(isset($header))
            <div class="mb-4">
                <h1 class="h3">{{ $header }}</h1>
            </div>
        @endif

        @yield('content')
    </main>
</div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.4/dist/htmx.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <script>
    class SearchSelect {
        constructor(select) {
            this.select = select;
            this.options = [];
            this.activeIndex = 0;
            this.wrapper = document.createElement('div');
            this.button = document.createElement('button');
            this.dropdown = document.createElement('div');
            this.input = document.createElement('input');
            this.list = document.createElement('div');

            this.build();
            this.refresh();
        }

        build() {
            this.wrapper.className = 'search-select';
            this.button.type = 'button';
            this.button.className = 'search-select-button';
            this.button.setAttribute('aria-haspopup', 'listbox');
            this.button.setAttribute('aria-expanded', 'false');

            this.dropdown.className = 'search-select-menu';
            this.input.type = 'search';
            this.input.className = 'form-control form-control-sm search-select-input';
            this.input.placeholder = this.select.dataset.searchPlaceholder || 'Search institutions';
            this.input.setAttribute('aria-label', this.input.placeholder);
            this.list.className = 'search-select-list';
            this.list.setAttribute('role', 'listbox');

            this.dropdown.append(this.input, this.list);
            this.wrapper.append(this.button, this.dropdown);
            this.select.after(this.wrapper);
            this.select.classList.add('search-select-native');

            this.button.addEventListener('click', () => this.toggle());
            this.input.addEventListener('input', () => this.renderList());
            this.input.addEventListener('keydown', (event) => this.handleInputKeydown(event));
            this.button.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    this.open();
                }
            });

            document.addEventListener('click', (event) => {
                if (! this.wrapper.contains(event.target) && event.target !== this.select) {
                    this.close();
                }
            });
        }

        refresh() {
            this.options = [...this.select.options].map((option) => ({
                value: option.value,
                label: option.textContent.trim(),
                disabled: option.disabled,
            }));
            this.updateButton();
            this.renderList();
        }

        selectedOption() {
            return this.options.find((option) => option.value === this.select.value) || this.options.find((option) => ! option.disabled);
        }

        updateButton() {
            const selected = this.selectedOption();
            this.button.textContent = selected?.label || this.select.dataset.emptyLabel || 'Choose institution';
        }

        toggle() {
            this.wrapper.classList.contains('is-open') ? this.close() : this.open();
        }

        open() {
            this.wrapper.classList.add('is-open');
            this.button.setAttribute('aria-expanded', 'true');
            this.input.value = '';
            this.renderList();
            this.input.focus();
        }

        close() {
            this.wrapper.classList.remove('is-open');
            this.button.setAttribute('aria-expanded', 'false');
        }

        filteredOptions() {
            const query = this.input.value.trim().toLowerCase();

            return this.options.filter((option) => ! query || option.label.toLowerCase().includes(query));
        }

        renderList() {
            const options = this.filteredOptions();
            this.activeIndex = Math.min(this.activeIndex, Math.max(options.length - 1, 0));
            this.list.innerHTML = '';

            if (options.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'search-select-empty';
                empty.textContent = 'No institutions found';
                this.list.appendChild(empty);
                return;
            }

            options.forEach((option, index) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'search-select-option';
                item.textContent = option.label;
                item.disabled = option.disabled;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.value === this.select.value ? 'true' : 'false');
                if (index === this.activeIndex) {
                    item.classList.add('is-active');
                }
                item.addEventListener('click', () => this.choose(option.value));
                this.list.appendChild(item);
            });
        }

        choose(value) {
            this.select.value = value;
            this.updateButton();
            this.close();
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.button.focus();
        }

        handleInputKeydown(event) {
            const options = this.filteredOptions().filter((option) => ! option.disabled);

            if (event.key === 'Escape') {
                this.close();
                this.button.focus();
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.activeIndex = Math.min(this.activeIndex + 1, Math.max(options.length - 1, 0));
                this.renderList();
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeIndex = Math.max(this.activeIndex - 1, 0);
                this.renderList();
            }

            if (event.key === 'Enter' && options[this.activeIndex]) {
                event.preventDefault();
                this.choose(options[this.activeIndex].value);
            }
        }
    }

    window.enhanceSearchSelects = function enhanceSearchSelects(root = document) {
        root.querySelectorAll('select[data-search-select]').forEach((select) => {
            if (! select.searchSelect) {
                select.searchSelect = new SearchSelect(select);
            } else {
                select.searchSelect.refresh();
            }
        });
    };

    window.refreshSearchSelect = function refreshSearchSelect(select) {
        select?.searchSelect?.refresh();
    };

    const savedAppSidebarState = window.localStorage.getItem('appSidebarCollapsed');
    if (savedAppSidebarState === 'true') {
        document.body.classList.add('app-sidebar-collapsed');
    }

    document.querySelectorAll('[data-app-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            document.body.classList.toggle('app-sidebar-collapsed');
            window.localStorage.setItem('appSidebarCollapsed', document.body.classList.contains('app-sidebar-collapsed') ? 'true' : 'false');
        });
    });

    const savedContextSidebarState = window.localStorage.getItem('contextSidebarCollapsed')
        ?? window.localStorage.getItem('dashboardSidebarCollapsed');
    if (savedContextSidebarState === 'true') {
        document.body.classList.add('context-sidebar-collapsed');
    }

    document.querySelectorAll('[data-context-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            document.body.classList.toggle('context-sidebar-collapsed');
            window.localStorage.setItem('contextSidebarCollapsed', document.body.classList.contains('context-sidebar-collapsed') ? 'true' : 'false');
        });
    });

    window.enhanceSearchSelects();
    </script>

    @stack('scripts')
</body>
</html>
