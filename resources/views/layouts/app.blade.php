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
<div class="app-shell">

    <nav class="navbar navbar-expand-lg navbar-dark navbar-brand-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="{{ url('/') }}">Faculty Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('trends*') || request()->is('peers*') ? 'active' : '' }}" href="{{ url('/trends') }}">Peer Trends</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('imports*') ? 'active' : '' }}" href="{{ url('/imports') }}">Imports</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="page-frame">
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

    window.enhanceSearchSelects();
    </script>

    @stack('scripts')
</body>
</html>
