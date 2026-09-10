{{--
    Puente entre Livewire y el Print Agent local (127.0.0.1:58432).
    Laravel corre en la nube y no puede llamar al agente directamente: el
    ESC/POS se genera server-side y se manda al navegador vía evento
    Livewire; el navegador (que sí corre en la PC del cliente) hace el
    fetch al agente. Tickets de venta y etiquetas de producto usan el mismo
    evento y la misma impresora de tickets — no hay impresora Zebra/ZPL en
    este proyecto.

    La impresora está hardcodeada a "POS-80C" (nombre exacto tal cual
    aparece instalada en Windows): no hay selección manual ni
    autodetección por guessed_type. Si el modelo de impresora cambia de
    nuevo, hay que actualizar el nombre acá.
--}}
@once
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-escpos-ticket', ({ content }) => {
                window.printEscposTicket(content);
            });
        });

        {{--
            Impresora fija: no hay selección manual (se sacó la pantalla
            "Configurar impresora") ni autodetección por guessed_type. Tiene
            que coincidir exactamente con el nombre con el que Windows tiene
            instalada la impresora.
        --}}
        window.resolveTicketPrinter = async function () {
            return 'POS-80C';
        };

        {{--
            El POST a /print/label o /print/ticket sólo confirma que el
            trabajo quedó encolado (status "queued"), no que se imprimió: el
            agente lo procesa en background y recién ahí puede fallar (papel,
            impresora offline, error de Windows, etc). Por eso hay que
            sondear /print/job/{id} para saber el resultado real.
        --}}
        window.pollPrintJob = async function (agentUrl, jobId, { intervalMs = 400, timeoutMs = 8000 } = {}) {
            const start = Date.now();

            while (Date.now() - start < timeoutMs) {
                await new Promise((resolve) => setTimeout(resolve, intervalMs));

                try {
                    const res = await fetch(`${agentUrl}/print/job/${jobId}`);
                    if (!res.ok) continue;

                    const job = await res.json();
                    if (job.status === 'done' || job.status === 'failed') {
                        return job;
                    }
                } catch (e) {
                    return null;
                }
            }

            return null;
        };

        window.sendToPrintAgent = async function ({ endpoint, resolvePrinter, content, notFoundTitle, notFoundBody, successTitle }) {
            const agentUrl = localStorage.getItem('print_agent_url') || 'http://127.0.0.1:58432';
            const printer = await resolvePrinter(agentUrl);

            if (!printer) {
                new FilamentNotification().title(notFoundTitle).body(notFoundBody).warning().send();

                return;
            }

            try {
                const res = await fetch(`${agentUrl}${endpoint}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ printer, content }),
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                const job = await res.json();
                const finalJob = await window.pollPrintJob(agentUrl, job.job_id);

                if (finalJob?.status === 'failed') {
                    new FilamentNotification()
                        .title('La impresora rechazó el trabajo')
                        .body(finalJob.detail ?? ('No se pudo imprimir en "' + printer + '".'))
                        .danger()
                        .send();

                    return;
                }

                if (finalJob === null) {
                    new FilamentNotification()
                        .title('Trabajo enviado, sin confirmación')
                        .body('El agente lo encoló pero no se pudo confirmar que haya terminado de imprimir en "' + printer + '".')
                        .warning()
                        .send();

                    return;
                }

                new FilamentNotification().title(successTitle).success().send();
            } catch (e) {
                new FilamentNotification()
                    .title('No se pudo conectar con el agente de impresión')
                    .body('Verificá que esté corriendo en esta PC (' + agentUrl + ') y que la impresora "' + printer + '" esté instalada.')
                    .danger()
                    .send();
            }
        };

        window.printEscposTicket = async function (content) {
            window.sendToPrintAgent({
                endpoint: '/print/ticket',
                resolvePrinter: window.resolveTicketPrinter,
                content,
                notFoundTitle: 'No se detectó ninguna impresora de tickets',
                notFoundBody: 'Conectá la impresora de tickets a esta PC y asegurate de que el Print Agent esté corriendo.',
                successTitle: 'Enviado a la impresora',
            });
        };
    </script>
@endonce
