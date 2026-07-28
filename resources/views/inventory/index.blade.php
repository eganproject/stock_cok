<x-app-layout>
    <x-slot name="title">Inventory</x-slot>
    <x-slot name="subtitle">Data stok langsung dari API gudang</x-slot>

    @if ($divisions->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center">
            <p class="text-sm font-medium text-slate-700">Belum ada divisi</p>
            <p class="mt-1 text-xs text-slate-400">Tambahkan divisi & gudang lebih dulu di menu terkait.</p>
        </div>
    @else
        <style>
            /* Indikator loading Inventory — smooth & modern (dikendalikan .is-loading) */
            #invBar {
                position: absolute; inset-inline: 0; top: -6px; height: 3px; z-index: 40;
                overflow: hidden; border-radius: 9999px; pointer-events: none;
                opacity: 0; transition: opacity .25s ease;
            }
            #invBar > span {
                display: block; height: 100%; width: 40%; border-radius: 9999px;
                background: linear-gradient(90deg, rgba(15,23,42,0) 0%, #0f172a 50%, rgba(15,23,42,0) 100%);
            }
            #invLoading {
                position: absolute; inset: 0; z-index: 30; pointer-events: none;
                opacity: 0; visibility: hidden; transition: opacity .25s ease, visibility .25s ease;
            }
            #invLoading > div {
                position: sticky; top: 6rem; margin-inline: auto; width: max-content;
                display: flex; align-items: center; gap: .625rem;
                padding: .5rem 1rem; border-radius: 9999px;
                border: 1px solid rgb(226 232 240 / .8); background: rgb(255 255 255 / .75);
                backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
                box-shadow: 0 10px 25px -8px rgb(15 23 42 / .25);
                font-size: .875rem; font-weight: 500; color: rgb(71 85 105);
                transform: translateY(-4px); transition: transform .25s ease;
            }
            #invLoading .inv-spinner {
                width: 1rem; height: 1rem; border-radius: 9999px;
                border: 2px solid rgb(203 213 225); border-top-color: #0f172a;
                animation: invSpin .6s linear infinite;
            }
            #invApp { transition: opacity .25s ease, filter .25s ease; }
            /* --- keadaan loading --- */
            #invWrap.is-loading #invBar { opacity: 1; }
            #invWrap.is-loading #invBar > span { animation: invBar 1.15s cubic-bezier(.4,0,.2,1) infinite; }
            #invWrap.is-loading #invLoading { opacity: 1; visibility: visible; }
            #invWrap.is-loading #invLoading > div { transform: translateY(0); }
            #invWrap.is-loading #invApp { opacity: .5; filter: blur(1.5px); pointer-events: none; }
            @keyframes invBar { 0% { transform: translateX(-120%); } 100% { transform: translateX(320%); } }
            @keyframes invSpin { to { transform: rotate(360deg); } }
            @media (prefers-reduced-motion: reduce) {
                #invBar > span, #invLoading .inv-spinner { animation-duration: 0s; }
                #invApp, #invLoading, #invLoading > div, #invBar { transition: none; }
            }
        </style>

        <div class="relative" id="invWrap">
            {{-- Progress bar tipis di atas konten (di luar #invApp agar tak ter-swap) --}}
            <div id="invBar"><span></span></div>

            {{-- Pill spinner mengambang --}}
            <div id="invLoading">
                <div><span class="inv-spinner"></span> Memuat data…</div>
            </div>

            <div id="invApp">
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
                    const wrap = document.getElementById('invWrap');
                    if (wrap) wrap.classList.toggle('is-loading', on);
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
                    const mySignal = ctrl.signal;
                    // Tunda tampilnya indikator agar muat cepat (cache) tidak berkedip.
                    const showTimer = setTimeout(() => toggleLoading(true), 120);
                    try {
                        const sep = url.includes('?') ? '&' : '?';
                        const res = await fetch(url + sep + 'partial=1', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            signal: mySignal,
                        });
                        if (! res.ok) throw new Error('HTTP ' + res.status);
                        const html = await res.text();
                        clearTimeout(showTimer);
                        app().innerHTML = html;
                        if (push) history.pushState({}, '', url);
                        initWidgets();
                        toggleLoading(false);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } catch (err) {
                        clearTimeout(showTimer);
                        // Abort = request digantikan yang baru; biarkan indikator dikelola request itu.
                        if (err.name !== 'AbortError') {
                            toggleLoading(false);
                            alert('Gagal memuat data. Silakan coba lagi.');
                        }
                    }
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
