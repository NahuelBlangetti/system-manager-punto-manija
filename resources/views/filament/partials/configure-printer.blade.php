{{--
    Selección de impresora de tickets para el Print Agent local
    (127.0.0.1:58432). Se guarda en localStorage: es por navegador/PC, no
    por usuario ni servidor, ya que el agente corre en la máquina del
    cliente, no en Laravel.

    Tickets de venta y etiquetas de código de barras usan la misma
    impresora térmica ESC/POS (ya no hay Zebra/ZPL en este proyecto).
--}}
<div
    x-data="{
        agentUrl: localStorage.getItem('print_agent_url') || 'http://127.0.0.1:58432',
        printerName: localStorage.getItem('ticket_printer_name') || '',
        printers: [],
        loading: false,
        error: null,
        async loadPrinters() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`${this.agentUrl}/printers`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                const list = Array.isArray(data) ? data : (data.printers ?? []);
                this.printers = list.map((p) => typeof p === 'string' ? p : p.name);

                if (!this.printerName) {
                    const candidates = list.filter((p) => typeof p === 'object' && p.guessed_type === 'ticket');
                    if (candidates.length > 0) {
                        const preferred = candidates.find((p) => p.is_default) ?? candidates[0];
                        this.printerName = preferred.name;
                    }
                }
            } catch (e) {
                this.printers = [];
                this.error = 'No se pudo conectar con el agente de impresión en esta PC. Verificá que esté corriendo en ' + this.agentUrl + '.';
            } finally {
                this.loading = false;
            }
        },
        save() {
            localStorage.setItem('print_agent_url', this.agentUrl);
            localStorage.setItem('ticket_printer_name', this.printerName);
            // Limpia claves viejas de cuando se usaba Zebra/ZPL.
            localStorage.removeItem('zebra_printer_name');
            localStorage.removeItem('zebra_print_agent_url');
            new FilamentNotification()
                .title('Impresora guardada en este navegador')
                .success()
                .send();
        },
    }"
    x-init="loadPrinters()"
    class="space-y-4"
>
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Dirección del agente</label>
        <input
            type="text"
            x-model="agentUrl"
            @change="loadPrinters()"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/15 dark:bg-white/5 dark:text-white"
        />
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Impresora de tickets</label>
            <button
                type="button"
                @click="loadPrinters()"
                class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
            >
                Actualizar lista
            </button>
        </div>

        <template x-if="loading">
            <p class="mt-1 text-xs text-gray-400">Buscando impresoras…</p>
        </template>

        <template x-if="!loading && error">
            <p class="mt-1 text-xs text-danger-600 dark:text-danger-400" x-text="error"></p>
        </template>

        <template x-if="!loading && !error">
            <select
                x-model="printerName"
                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/15 dark:bg-white/5 dark:text-white"
            >
                <option value="">Seleccioná una impresora…</option>
                <template x-for="name in printers" :key="name">
                    <option :value="name" x-text="name"></option>
                </template>
            </select>
        </template>

        <template x-if="!loading && !error && printerName && printers.length > 0">
            <p class="mt-1 text-xs text-gray-400">Tiene que ser la térmica de tickets (ESC/POS), no una Zebra.</p>
        </template>

        <template x-if="!loading && !error && printers.length === 0">
            <p class="mt-1 text-xs text-warning-600 dark:text-warning-400">
                No se encontraron impresoras instaladas en esta PC.
            </p>
        </template>
    </div>

    <button
        type="button"
        @click="save()"
        :disabled="!printerName"
        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-50"
    >
        <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
        Guardar en este navegador
    </button>
</div>
