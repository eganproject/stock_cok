<x-app-layout>
    <x-slot name="title">Inventory</x-slot>
    <x-slot name="subtitle">Data stok langsung dari API gudang</x-slot>

    @if ($divisions->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center">
            <p class="text-sm font-medium text-slate-700">Belum ada divisi</p>
            <p class="mt-1 text-xs text-slate-400">Tambahkan divisi & gudang lebih dulu di menu terkait.</p>
        </div>
    @else
        <div class="relative" id="invWrap">
            {{-- Indikator loading (di luar #invApp agar tidak ikut ter-swap) --}}
            <div id="invLoading" class="pointer-events-none absolute inset-0 z-30 hidden">
                <div class="sticky top-24 mx-auto flex w-max items-center gap-2 rounded-full border border-slate-200 bg-white/95 px-4 py-2 text-sm font-medium text-slate-600 shadow-lg">
                    <svg class="h-4 w-4 animate-spin text-slate-900" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Memuat data…
                </div>
            </div>

            <div id="invApp" class="transition-opacity duration-150">
                @include('inventory._app')
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            (function () {
                const BASE = @json(route('inventory.index'));
                const app = () => document.getElementById('invApp');
                const form = () => document.getElementById('invFilterForm');
                let ctrl = null;

                function debounce(fn, wait) {
                    let t;
                    return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), wait); };
                }

                function toggleLoading(on) {
                    const ov = document.getElementById('invLoading');
                    if (ov) ov.classList.toggle('hidden', !on);
                    const a = app();
                    if (a) { a.classList.toggle('opacity-40', on); a.classList.toggle('pointer-events-none', on); }
                }

                // Bangun query dari form (termasuk hidden division/sort/direction), lalu
                // override sebagian nilai. Nilai kosong dibuang agar URL rapi.
                function paramsFromForm(overrides) {
                    const p = new URLSearchParams();
                    const fd = new FormData(form());
                    for (const [k, v] of fd.entries()) { if (v !== '' && v != null) p.set(k, v); }
                    overrides = overrides || {};
                    for (const k in overrides) {
                        (overrides[k] === null || overrides[k] === '') ? p.delete(k) : p.set(k, overrides[k]);
                    }
                    return p;
                }

                function reload(overrides) {
                    const p = paramsFromForm(Object.assign({ page: '1' }, overrides || {}));
                    load(BASE + '?' + p.toString());
                }

                async function load(url, push = true) {
                    if (ctrl) ctrl.abort();
                    ctrl = new AbortController();
                    toggleLoading(true);
                    try {
                        const sep = url.includes('?') ? '&' : '?';
                        const res = await fetch(url + sep + 'partial=1', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            signal: ctrl.signal,
                        });
                        if (! res.ok) throw new Error('HTTP ' + res.status);
                        app().innerHTML = await res.text();
                        if (push) history.pushState({}, '', url);
                        initWidgets();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            toggleLoading(false);
                            alert('Gagal memuat data. Silakan coba lagi.');
                        }
                        return;
                    }
                    toggleLoading(false);
                }

                // (Re)inisialisasi select2 + flatpickr setiap kali konten diganti.
                function initWidgets() {
                    if (window.jQuery) {
                        jQuery('#invApp .filter-select').each(function () {
                            jQuery(this).select2({ minimumResultsForSearch: 8, width: '100%', allowClear: false });
                        });
                    }
                    const asOf = document.getElementById('asOf');
                    if (asOf && window.flatpickr) {
                        flatpickr(asOf, {
                            dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                            maxDate: 'today', locale: { firstDayOfWeek: 1 },
                            onClose: function (dates, str, inst) { if (str !== inst.input.defaultValue) reload(); },
                        });
                    }
                }

                // ---- Event delegation (dipasang sekali, tahan terhadap swap) ----
                document.addEventListener('DOMContentLoaded', function () {
                    initWidgets();

                    // Submit form (tombol Terapkan / Enter)
                    document.addEventListener('submit', function (e) {
                        if (e.target && e.target.id === 'invFilterForm') { e.preventDefault(); reload(); }
                    });

                    if (window.jQuery) {
                        // Ganti divisi = reset seluruh sub-filter divisi tsb.
                        jQuery(document).on('change', '#filterDivision', function () {
                            load(BASE + '?division=' + encodeURIComponent(this.value));
                        });
                        // Filter lain → muat ulang (mempertahankan divisi + sort).
                        jQuery(document).on('change',
                            '#invFilterForm [name="warehouse"], #invFilterForm [name="category"], #invFilterForm [name="status"], #invFilterForm [name="per_page"]',
                            function () { reload(); });
                        // Pencarian → debounce.
                        jQuery(document).on('input', '#invFilterForm [name="search"]', debounce(function () { reload(); }, 400));
                    }

                    // Klik link internal (sort header, pagination, refresh, reset) → AJAX.
                    document.addEventListener('click', function (e) {
                        const a = e.target.closest('#invApp a');
                        if (! a || a.hasAttribute('data-external')) return;
                        const href = a.getAttribute('href') || '';
                        if (! href.startsWith(BASE)) return; // link keluar → biarkan default
                        e.preventDefault();
                        load(href);
                    });

                    // Tombol back/forward browser.
                    window.addEventListener('popstate', function () { load(location.href, false); });
                });
            })();
        </script>
    @endpush
</x-app-layout>
