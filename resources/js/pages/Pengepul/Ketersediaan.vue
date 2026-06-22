<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Car, Footprints, Bike, Route, MapPin, RotateCcw, Clock, Navigation, ExternalLink } from '@lucide/vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { planRoute } from '@/lib/route';
import type { Point, TransportMode } from '@/lib/route';

interface ListingItem {
    id: number;
    jenis_sampah: string;
    berat: number;
    harga: number;
    status: string;
    lat?: number | null;
    lng?: number | null;
    user: { name: string; lat?: number | null; lng?: number | null };
}

const props = defineProps<{
    listings: ListingItem[];
    handledJenis: string[];
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth.user as { id: number; lat?: number | null; lng?: number | null });

const selectedIds = ref<Set<number>>(new Set());
const routeResult = ref<ReturnType<typeof planRoute> | null>(null);
const transportMode = ref<TransportMode>('mobil');

const transportModes: { value: TransportMode; label: string; icon: typeof Car }[] = [
    { value: 'mobil', label: 'Mobil', icon: Car },
    { value: 'jalan', label: 'Jalan', icon: Footprints },
    { value: 'sepeda', label: 'Sepeda', icon: Bike },
];

const hasDepot = computed(() => currentUser.value?.lat != null && currentUser.value?.lng != null);

let leafletMap: any = null;
let L: any = null;
let routeLine: any = null;
let orderMarkers: any[] = [];

const jenisSampahLabel: Record<string, string> = {
    plastik_pet: 'Plastik PET',
    plastik_hdpe: 'Plastik HDPE',
    kertas: 'Kertas',
    kardus: 'Kardus',
    logam: 'Logam',
    kaleng: 'Kaleng',
    kaca: 'Kaca',
    elektronik: 'Elektronik',
};

function jenisLabel(v: string): string {
    return jenisSampahLabel[v] ?? v;
}

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function toggleSelect(id: number): void {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }

    selectedIds.value = new Set(selectedIds.value);
}

function calculateRoute(): void {
    const pts: Point[] = props.listings
        .filter((l) => selectedIds.value.has(l.id) && l.lat != null && l.lng != null)
        .map((l) => ({ id: l.id, lat: l.lat!, lng: l.lng!, label: jenisLabel(l.jenis_sampah) + ' – ' + l.user.name }));

    if (pts.length === 0) {
        routeResult.value = null;
        updateRouteOnMap([]);

        return;
    }

    const userLat = currentUser.value?.lat ?? undefined;
    const userLng = currentUser.value?.lng ?? undefined;
    routeResult.value = planRoute(pts, userLat ?? undefined, userLng ?? undefined, transportMode.value);
    updateRouteOnMap(routeResult.value.order);
}

function resetRoute(): void {
    selectedIds.value = new Set();
    routeResult.value = null;
    updateRouteOnMap([]);
}

// ── Buka rute di Google Maps (rute mengikuti jalan asli) ──────────────────
const GMAPS_TRAVELMODE: Record<TransportMode, string> = {
    mobil: 'driving',
    jalan: 'walking',
    sepeda: 'bicycling',
};

// URL publik Google Maps mendukung maksimal ~9 waypoint di antara origin & destination.
const GMAPS_MAX_WAYPOINTS = 9;

function coordStr(lat: number, lng: number): string {
    return `${lat},${lng}`;
}

// Jumlah titik yang TIDAK ikut terkirim ke Google Maps karena melebihi batas waypoint.
const gmapsOverflow = computed<number>(() => {
    if (!routeResult.value) {
        return 0;
    }

    const stops = routeResult.value.order.length;
    const waypointCount = hasDepot.value ? Math.max(0, stops - 1) : Math.max(0, stops - 2);

    return Math.max(0, waypointCount - GMAPS_MAX_WAYPOINTS);
});

function openInGoogleMaps(): void {
    if (!routeResult.value || routeResult.value.order.length === 0) {
        return;
    }

    // Rangkai perhentian: depot (bila ada) lalu stop sesuai urutan TSP.
    const seq: { lat: number; lng: number }[] = [];

    if (hasDepot.value) {
        seq.push({ lat: currentUser.value!.lat as number, lng: currentUser.value!.lng as number });
    }

    routeResult.value.order.forEach((p) => seq.push({ lat: p.lat, lng: p.lng }));

    const origin = seq[0];
    const destination = seq[seq.length - 1];
    let waypoints = seq.slice(1, -1);

    if (waypoints.length > GMAPS_MAX_WAYPOINTS) {
        waypoints = waypoints.slice(0, GMAPS_MAX_WAYPOINTS);
    }

    const params = new URLSearchParams({
        api: '1',
        origin: coordStr(origin.lat, origin.lng),
        destination: coordStr(destination.lat, destination.lng),
        travelmode: GMAPS_TRAVELMODE[transportMode.value],
    });

    if (waypoints.length > 0) {
        params.set('waypoints', waypoints.map((w) => coordStr(w.lat, w.lng)).join('|'));
    }

    window.open(`https://www.google.com/maps/dir/?${params.toString()}`, '_blank', 'noopener');
}

// Hitung ulang saat moda berganti agar Jarak/Waktu mengikuti kecepatan moda.
watch(transportMode, () => {
    if (routeResult.value) {
        calculateRoute();
    }
});

function updateRouteOnMap(order: Point[]): void {
    if (!leafletMap || !L) {
        return;
    }

    if (routeLine) {
        leafletMap.removeLayer(routeLine);
        routeLine = null;
    }

    for (const m of orderMarkers) {
        leafletMap.removeLayer(m);
    }

    orderMarkers = [];

    if (order.length === 0) {
        return;
    }

    // Rangkai koordinat dimulai dari depot (Lokasi Anda) bila tersedia.
    const coords: [number, number][] = [];

    if (hasDepot.value) {
        coords.push([currentUser.value!.lat as number, currentUser.value!.lng as number]);
    }

    order.forEach((p) => coords.push([p.lat, p.lng]));

    if (coords.length > 1) {
        routeLine = L.polyline(coords, { color: '#2563eb', weight: 4, opacity: 0.85 }).addTo(leafletMap);
    }

    // Marker bernomor sesuai urutan kunjungan.
    order.forEach((p, idx) => {
        const numIcon = L.divIcon({
            html: `<div style="background:#2563eb;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;box-shadow:0 1px 4px rgba(0,0,0,0.3);">${idx + 1}</div>`,
            className: '',
            iconSize: [26, 26],
            iconAnchor: [13, 13],
        });
        const m = L.marker([p.lat, p.lng], { icon: numIcon }).addTo(leafletMap);

        if (p.label) {
            m.bindPopup(`<b>#${idx + 1}</b> ${p.label}`);
        }

        orderMarkers.push(m);
    });

    if (routeLine) {
        leafletMap.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
    } else {
        leafletMap.setView(coords[0], 14);
    }
}

function klaim(id: number): void {
    if (!confirm('Klaim listing ini?')) {
        return;
    }

    router.post(
        `/pengepul/klaim/${id}`,
        {},
        {
            onSuccess: () => {
                selectedIds.value.delete(id);
                selectedIds.value = new Set(selectedIds.value);
            },
        },
    );
}

onMounted(async () => {
    if (typeof window === 'undefined') {
        return;
    }

    L = await import('leaflet');
    // Fix default icon paths for Vite bundling
    delete (L.Icon.Default.prototype as Record<string, unknown>)._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    const firstWithCoord = props.listings.find((l) => l.lat != null && l.lng != null);
    const center: [number, number] = firstWithCoord
        ? [firstWithCoord.lat!, firstWithCoord.lng!]
        : [-8.6705, 115.2126]; // Denpasar, Bali

    leafletMap = L.map('leaflet-map').setView(center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(leafletMap);

    // Add markers for listings with coordinates
    for (const listing of props.listings) {
        if (listing.lat != null && listing.lng != null) {
            const marker = L.marker([listing.lat, listing.lng]).addTo(leafletMap);
            marker.bindPopup(
                `<b>${jenisLabel(listing.jenis_sampah)}</b><br>${listing.berat} kg — ${formatRp(listing.harga)}<br>${listing.user.name}`,
            );
        }
    }

    // Show pengepul own marker if they have coordinates
    const uLat = currentUser.value?.lat;
    const uLng = currentUser.value?.lng;

    if (uLat != null && uLng != null) {
        const depotIcon = L.divIcon({
            html: '<div style="background:#15803d;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;">🏠</div>',
            className: '',
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
        L.marker([uLat, uLng], { icon: depotIcon }).addTo(leafletMap).bindPopup('Lokasi Anda');
    }
});

onUnmounted(() => {
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }
});
</script>

<template>
    <Head title="Ketersediaan Sampah" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="flex h-[calc(100vh-4rem)] flex-1 flex-col gap-4 p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-bold text-green-900">Ketersediaan Sampah</h1>
            <p class="mt-1 text-sm text-muted-foreground">Listing terfilter sesuai jenis yang Anda tangani</p>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-[1fr_380px]">
            <!-- Map (kiri, dominan) -->
            <div id="leaflet-map" class="min-h-[360px] overflow-hidden rounded-xl border border-gray-200" />

            <!-- Panel kanan -->
            <div class="flex min-h-0 flex-col gap-3 overflow-y-auto pr-1">
                <!-- Header -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex items-center gap-2">
                        <Route class="h-5 w-5 text-green-700" />
                        <h2 class="text-base font-semibold text-green-900">Hitung Jarak Rute</h2>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Pilih listing di daftar atau peta untuk melihat rute pickup terbaik.
                    </p>

                    <!-- Toggle moda transport -->
                    <div class="mt-3 grid grid-cols-3 gap-1 rounded-lg bg-gray-100 p-1">
                        <button
                            v-for="m in transportModes"
                            :key="m.value"
                            type="button"
                            @click="transportMode = m.value"
                            class="flex items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
                            :class="transportMode === m.value ? 'bg-white text-green-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            <component :is="m.icon" class="h-4 w-4" />
                            {{ m.label }}
                        </button>
                    </div>

                    <!-- Titik Awal (A) -->
                    <div class="mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                        <div class="flex items-center gap-2 text-xs font-semibold text-green-800">
                            <MapPin class="h-3.5 w-3.5" />
                            Titik Awal (A)
                        </div>
                        <p v-if="hasDepot" class="mt-0.5 ml-5 text-sm text-gray-700">Lokasi Anda</p>
                        <p v-else class="mt-0.5 ml-5 text-xs text-amber-600">⚠ Lokasi Anda belum diatur</p>
                    </div>

                    <!-- Aksi -->
                    <div class="mt-3 flex gap-2">
                        <button
                            @click="calculateRoute"
                            :disabled="selectedIds.size === 0"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-800 disabled:opacity-40"
                        >
                            <Route class="h-4 w-4" />
                            Hitung Rute ({{ selectedIds.size }})
                        </button>
                        <button
                            @click="resetRoute"
                            class="flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-gray-600 transition-colors hover:bg-gray-50"
                            title="Reset"
                        >
                            <RotateCcw class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Kartu hasil -->
                <div v-if="routeResult" class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Route class="h-3.5 w-3.5" />
                            Jarak Tempuh
                        </div>
                        <div class="mt-1 text-xl font-bold text-green-900">{{ routeResult.totalDistanceKm }} km</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Clock class="h-3.5 w-3.5" />
                            Estimasi Waktu
                        </div>
                        <div class="mt-1 text-xl font-bold text-green-900">{{ routeResult.estimatedMinutes }} menit</div>
                    </div>
                    <div
                        v-if="transportMode === 'mobil'"
                        class="col-span-2 rounded-xl border border-gray-200 bg-white p-3 text-sm"
                    >
                        <span class="text-muted-foreground">Est. Biaya BBM:</span>
                        <span class="ml-1 font-semibold text-green-900">{{ formatRp(routeResult.estimatedCostRp) }}</span>
                    </div>

                    <!-- Buka di Google Maps (rute mengikuti jalan asli) -->
                    <Button
                        @click="openInGoogleMaps"
                    >
                        <ExternalLink class="h-4 w-4" />
                        Buka di Google Maps
                    </Button>
                    <p v-if="gmapsOverflow > 0" class="col-span-2 text-xs text-amber-600">
                        ⚠ Google Maps membatasi titik perantara — {{ gmapsOverflow }} titik terakhir tidak ikut dikirim.
                    </p>
                </div>

                <!-- Ringkasan Rute -->
                <div v-if="routeResult" class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex items-center gap-2 text-sm font-semibold text-green-900">
                        <Navigation class="h-4 w-4" />
                        Ringkasan Rute
                    </div>
                    <ol class="mt-2 space-y-1.5 text-sm">
                        <li v-if="hasDepot" class="flex items-center gap-2 text-gray-600">
                            <span class="h-2 w-2 rounded-full bg-green-600" />
                            Lokasi Anda
                        </li>
                        <li
                            v-for="(p, idx) in routeResult.order"
                            :key="p.id"
                            class="flex items-center gap-2 text-gray-700"
                        >
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-semibold text-white"
                            >
                                {{ idx + 1 }}
                            </span>
                            {{ p.label ?? p.id }}
                        </li>
                    </ol>
                </div>

                <!-- Daftar titik pickup -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-green-900">Titik Pickup</h3>
                    <div class="mt-3 flex flex-col gap-2.5">
                        <div v-if="listings.length === 0" class="py-8 text-center text-sm text-gray-400">
                            <p>Tidak ada listing yang sesuai jenis Anda.</p>
                            <a href="/pengepul/jenis" class="text-green-700 hover:underline">Tambah jenis yang ditangani →</a>
                        </div>

                        <div
                            v-for="item in listings"
                            :key="item.id"
                            class="cursor-pointer rounded-xl border p-3 transition-colors"
                            :class="selectedIds.has(item.id) ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                            @click="toggleSelect(item.id)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.has(item.id)"
                                            class="accent-green-700"
                                            @click.stop="toggleSelect(item.id)"
                                        />
                                        <span class="text-sm font-medium">{{ jenisLabel(item.jenis_sampah) }}</span>
                                    </div>
                                    <div class="mt-0.5 ml-5 text-sm text-gray-500">{{ item.berat }} kg · {{ formatRp(item.harga) }}</div>
                                    <div class="ml-5 text-xs text-gray-400">{{ item.user.name }}</div>
                                    <div v-if="!item.lat" class="mt-0.5 ml-5 text-xs text-amber-500">⚠ Tanpa koordinat</div>
                                </div>
                                <button
                                    @click.stop="klaim(item.id)"
                                    class="shrink-0 rounded-lg bg-green-700 px-3 py-1 text-xs font-medium text-white hover:bg-green-800"
                                >
                                    Klaim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
