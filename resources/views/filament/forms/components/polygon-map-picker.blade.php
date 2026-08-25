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
            clickListener: null,
            drawing: false,
            drawingPointCount: 0,
            mapsKey: @js(config('services.google_maps.key')),
            centerLat: @js((float) config('store.lat')),
            centerLng: @js((float) config('store.lng')),
            init() {
                if (! this.mapsKey) return;
                this.loadGoogleMaps().then(() => this.initMap());
            },
            loadGoogleMaps() {
                if (window.google?.maps) return Promise.resolve();
                if (! window.__gmapsAdminPromise) {
                    window.__gmapsAdminPromise = new Promise((resolve) => {
                        window.__onGmapsAdminReady = () => resolve();
                        const script = document.createElement('script');
                        script.src = `https://maps.googleapis.com/maps/api/js?key=${this.mapsKey}&language=es&region=AR&callback=__onGmapsAdminReady`;
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

                if (hasExisting) {
                    this.buildPolygon(this.state.map((p) => ({ lat: Number(p.lat), lng: Number(p.lng) })));
                    this.polygon.setEditable(true);
                    this.polygon.setDraggable(true);
                    this.bindPolygonEvents();
                    this.fitToPolygon();
                }
            },
            buildPolygon(paths) {
                this.polygon = new google.maps.Polygon({
                    map: this.map,
                    paths,
                    fillColor: '#ef4444',
                    fillOpacity: 0.3,
                    strokeColor: '#dc2626',
                    strokeWeight: 2,
                });
            },
            startDrawing() {
                if (this.polygon) {
                    this.polygon.setMap(null);
                    this.polygon = null;
                }

                this.state = [];
                this.drawing = true;
                this.drawingPointCount = 0;
                this.buildPolygon([]);
                this.map.setOptions({ draggableCursor: 'crosshair', draggable: false });

                this.clickListener = google.maps.event.addListener(this.map, 'click', (e) => {
                    this.polygon.getPath().push(e.latLng);
                    this.drawingPointCount++;
                });
            },
            undoLastPoint() {
                if (! this.polygon) return;

                const path = this.polygon.getPath();

                if (path.getLength() > 0) {
                    path.removeAt(path.getLength() - 1);
                    this.drawingPointCount = Math.max(0, this.drawingPointCount - 1);
                }
            },
            finishDrawing() {
                if (! this.polygon || this.polygon.getPath().getLength() < 3) return;

                if (this.clickListener) {
                    google.maps.event.removeListener(this.clickListener);
                    this.clickListener = null;
                }

                this.map.setOptions({ draggableCursor: null, draggable: true });
                this.drawing = false;
                this.polygon.setOptions({ editable: true, draggable: true });
                this.bindPolygonEvents();
                this.syncState();
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
                if (this.clickListener) {
                    google.maps.event.removeListener(this.clickListener);
                    this.clickListener = null;
                }

                if (this.polygon) {
                    this.polygon.setMap(null);
                    this.polygon = null;
                }

                this.map?.setOptions({ draggableCursor: null, draggable: true });
                this.drawing = false;
                this.drawingPointCount = 0;
                this.state = [];
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

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <button type="button" x-show="! drawing && ! polygon" x-on:click="startDrawing()"
                        class="fi-btn fi-btn-size-sm rounded-lg bg-primary-600 px-3 py-1.5 font-semibold text-white hover:bg-primary-500">
                        Dibujar zona
                    </button>

                    <button type="button" x-show="! drawing && polygon" x-on:click="startDrawing()"
                        class="text-gray-600 hover:underline dark:text-gray-300">
                        Rehacer polígono
                    </button>

                    <template x-if="drawing">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="text-gray-500 dark:text-gray-400">
                                Hacé clic (sin arrastrar) en cada esquina de la zona — el mapa no se mueve mientras dibujás (<span x-text="drawingPointCount"></span> puntos).
                            </span>
                            <button type="button" x-on:click="undoLastPoint()" x-show="drawingPointCount > 0" class="text-gray-600 hover:underline dark:text-gray-300">
                                Deshacer último punto
                            </button>
                            <button type="button" x-on:click="finishDrawing()" x-show="drawingPointCount >= 3"
                                class="fi-btn fi-btn-size-sm rounded-lg bg-success-600 px-3 py-1.5 font-semibold text-white hover:bg-success-500">
                                Finalizar polígono
                            </button>
                        </div>
                    </template>

                    <span x-show="! drawing && state && state.length >= 3" class="text-gray-500 dark:text-gray-400" x-text="`${state.length} puntos definidos`"></span>

                    <button type="button" x-on:click="clearPolygon()" x-show="drawing || (state && state.length > 0)" class="ms-auto text-danger-600 hover:underline">
                        Borrar polígono
                    </button>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
