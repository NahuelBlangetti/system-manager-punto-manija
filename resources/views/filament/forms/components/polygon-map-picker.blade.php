@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        wire:ignore
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            map: null,
            polygon: null,
            drawingManager: null,
            mapsKey: @js(config('services.google_maps.key')),
            centerLat: @js((float) config('store.lat')),
            centerLng: @js((float) config('store.lng')),
            init() {
                if (! this.mapsKey) return;
                this.loadGoogleMaps().then(() => this.initMap());
            },
            loadGoogleMaps() {
                if (window.google?.maps?.drawing) return Promise.resolve();
                if (! window.__gmapsAdminPromise) {
                    window.__gmapsAdminPromise = new Promise((resolve) => {
                        window.__onGmapsAdminReady = () => resolve();
                        const script = document.createElement('script');
                        script.src = `https://maps.googleapis.com/maps/api/js?key=${this.mapsKey}&libraries=drawing&language=es&region=AR&callback=__onGmapsAdminReady`;
                        script.defer = true;
                        document.head.appendChild(script);
                    });
                }
                return window.__gmapsAdminPromise;
            },
            initMap() {
                const hasExisting = Array.isArray(this.state) && this.state.length >= 3;

                this.map = new google.maps.Map(this.$refs.mapEl, {
                    center: { lat: this.centerLat, lng: this.centerLng },
                    zoom: 12,
                    mapTypeControl: false,
                    streetViewControl: false,
                });

                this.drawingManager = new google.maps.drawing.DrawingManager({
                    drawingMode: hasExisting ? null : google.maps.drawing.OverlayType.POLYGON,
                    drawingControl: ! hasExisting,
                    drawingControlOptions: { drawingModes: ['polygon'] },
                    polygonOptions: {
                        fillColor: '#ef4444',
                        fillOpacity: 0.3,
                        strokeColor: '#dc2626',
                        strokeWeight: 2,
                        editable: true,
                        draggable: true,
                    },
                });
                this.drawingManager.setMap(this.map);

                if (hasExisting) {
                    this.polygon = new google.maps.Polygon({
                        paths: this.state.map((p) => ({ lat: Number(p.lat), lng: Number(p.lng) })),
                        fillColor: '#ef4444',
                        fillOpacity: 0.3,
                        strokeColor: '#dc2626',
                        strokeWeight: 2,
                        editable: true,
                        draggable: true,
                    });
                    this.polygon.setMap(this.map);
                    this.bindPolygonEvents();
                    this.fitToPolygon();
                }

                google.maps.event.addListener(this.drawingManager, 'polygoncomplete', (poly) => {
                    if (this.polygon) this.polygon.setMap(null);
                    this.polygon = poly;
                    this.drawingManager.setDrawingMode(null);
                    this.drawingManager.setOptions({ drawingControl: false });
                    this.bindPolygonEvents();
                    this.syncState();
                });
            },
            bindPolygonEvents() {
                const path = this.polygon.getPath();
                ['insert_at', 'remove_at', 'set_at'].forEach((evt) => {
                    google.maps.event.addListener(path, evt, () => this.syncState());
                });
                google.maps.event.addListener(this.polygon, 'dragend', () => this.syncState());
            },
            syncState() {
                if (! this.polygon) {
                    this.state = [];
                    return;
                }

                const path = this.polygon.getPath();
                const points = [];

                for (let i = 0; i < path.getLength(); i++) {
                    const point = path.getAt(i);
                    points.push({ lat: point.lat(), lng: point.lng() });
                }

                this.state = points;
            },
            fitToPolygon() {
                if (! this.polygon) return;

                const bounds = new google.maps.LatLngBounds();
                this.polygon.getPath().forEach((point) => bounds.extend(point));
                this.map.fitBounds(bounds);
            },
            clearPolygon() {
                if (this.polygon) {
                    this.polygon.setMap(null);
                    this.polygon = null;
                }

                this.state = [];

                if (this.drawingManager) {
                    this.drawingManager.setOptions({ drawingControl: true });
                    this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
                }
            },
        }"
        class="space-y-2"
    >
        <template x-if="! mapsKey">
            <p class="text-xs text-danger-600">
                Configurá <code>GOOGLE_MAPS_API_KEY</code> en el .env para poder dibujar zonas en el mapa.
            </p>
        </template>

        <template x-if="mapsKey">
            <div class="space-y-2">
                <div x-ref="mapEl" style="height: 420px; border-radius: 0.5rem; overflow: hidden;" class="border border-gray-300 dark:border-gray-600"></div>
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span x-show="! state || state.length < 3">Usá la herramienta del mapa (ícono de polígono) para dibujar la zona.</span>
                    <span x-show="state && state.length >= 3" x-text="`${state.length} puntos definidos`"></span>
                    <button type="button" x-on:click="clearPolygon()" class="text-danger-600 hover:underline">Borrar polígono</button>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
