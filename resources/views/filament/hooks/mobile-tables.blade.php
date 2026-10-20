<script>
    (() => {
        const movil = window.matchMedia('(max-width: 1023px)');

        const tituloDe = (celda) => {
            if (celda.classList.contains('fi-ta-selection-cell')) return false;
            if (celda.classList.contains('fi-ta-cell-image')) return false;
            if (celda.querySelector('.fi-ta-actions')) return false;

            return /(?:^|\s)fi-ta-cell-(?:name|sale-number|codigo|code|title|titulo|numero)(?:\s|$)/.test(celda.className);
        };

        const marcarTablas = () => {
            document.querySelectorAll('.fi-ta-table').forEach((tabla) => {
                const etiquetas = [...tabla.querySelectorAll(':scope > thead th')].map((th) =>
                    (th.innerText || '').replace(/\s+/g, ' ').trim(),
                );

                tabla.querySelectorAll(':scope > tbody > tr').forEach((fila) => {
                    const celdas = [...fila.children];
                    let titulo = celdas.find((celda) => tituloDe(celda));

                    if (! titulo) {
                        titulo = celdas.find((celda, indice) => {
                            return etiquetas[indice]
                                && ! celda.classList.contains('fi-ta-selection-cell')
                                && ! celda.classList.contains('fi-ta-cell-image')
                                && ! celda.querySelector('.fi-ta-actions');
                        });
                    }

                    celdas.forEach((celda, indice) => {
                        const etiqueta = etiquetas[indice] || '';

                        if (etiqueta) {
                            celda.dataset.label = etiqueta;
                        } else {
                            delete celda.dataset.label;
                        }

                        if (celda === titulo) {
                            celda.dataset.mobileTitle = '1';
                        } else {
                            delete celda.dataset.mobileTitle;
                        }
                    });
                });
            });
        };

        let pedido = 0;

        const programar = () => {
            if (! movil.matches) return;
            cancelAnimationFrame(pedido);
            pedido = requestAnimationFrame(marcarTablas);
        };

        let observer;

        const observar = () => {
            observer?.disconnect();
            observer = new MutationObserver(programar);
            observer.observe(document.querySelector('.fi-main') ?? document.body, {
                childList: true,
                subtree: true,
            });
        };

        programar();
        observar();
        movil.addEventListener('change', programar);
        document.addEventListener('livewire:navigated', () => {
            programar();
            observar();
        });
    })();
</script>
