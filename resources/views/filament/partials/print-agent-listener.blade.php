{{--
    Puente entre Livewire y el Print Agent local (127.0.0.1:58432).
    Laravel corre en la nube y no puede llamar al agente directamente: el
    ESC/POS se genera server-side y se manda al navegador vía evento
    Livewire; el navegador (que sí corre en la PC del cliente) hace el
    fetch al agente.

    Este cliente solo necesita impresora de tickets de venta (no etiquetas
    Zebra, a diferencia de otros proyectos que usan este mismo agente).
--}}
@once
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-escpos-ticket', ({ content }) => {
                window.printEscposTicket(content);
            });
        });

        {{--
            Si el usuario ya eligió una impresora a mano, se respeta esa
            elección. Si no, se detecta sola por nombre (guessed_type que
            calcula el agente vía app/services/printer_classifier.py) en
            vez de obligar a configurar antes de poder imprimir.
        --}}
        window.resolveTicketPrinter = async function (agentUrl) {
            const saved = localStorage.getItem('ticket_printer_name');
            if (saved) {
                return saved;
            }

            try {
                const res = await fetch(`${agentUrl}/printers`);
                if (!res.ok) return null;
                const data = await res.json();
                const list = Array.isArray(data) ? data : (data.printers ?? []);
                const candidates = list.filter((p) => p.guessed_type === 'ticket');
                if (candidates.length === 0) return null;
                const preferred = candidates.find((p) => p.is_default) ?? candidates[0];
                return preferred.name;
            } catch (e) {
                return null;
            }
        };

        window.printEscposTicket = async function (content) {
            const agentUrl = localStorage.getItem('print_agent_url') || 'http://127.0.0.1:58432';
            const printer = await window.resolveTicketPrinter(agentUrl);

            if (!printer) {
                new FilamentNotification()
                    .title('No se detectó ninguna impresora de tickets')
                    .body('Conectá la impresora de tickets, o elegila a mano con la acción "Configurar impresora".')
                    .warning()
                    .send();

                return;
            }

            try {
                const res = await fetch(`${agentUrl}/print/ticket`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ printer, content }),
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                new FilamentNotification()
                    .title('Ticket enviado a la impresora')
                    .success()
                    .send();
            } catch (e) {
                new FilamentNotification()
                    .title('No se pudo conectar con el agente de impresión')
                    .body('Verificá que esté corriendo en esta PC (' + agentUrl + ') y que la impresora "' + printer + '" esté instalada.')
                    .danger()
                    .send();
            }
        };
    </script>
@endonce
