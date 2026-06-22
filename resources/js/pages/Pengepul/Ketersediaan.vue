<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Weight,
    Route,
    MapPin,
    RotateCcw,
    Clock,
    Navigation,
    ExternalLink,
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { planRoute } from '@/lib/route';
import type { Point } from '@/lib/route';

interface ListingItem {
    id: number;
    jenis_sampah: string;
    berat: number;
    harga: number;
    status: string;
    lat?: number | null;
    lng?: number | null;
    user: { name: string; lat?: number | null; lng?: number | null };
    penawaran?: { id: number; harga: number; status: string }[];
}

const props = defineProps<{
    listings: ListingItem[];
    handledJenis: string[];
}>();

const page = usePage();
const currentUser = computed(
    () =>
        page.props.auth.user as {
            id: number;
            lat?: number | null;
            lng?: number | null;
        },
);

const selectedIds = ref<Set<number>>(new Set());
const routeResult = ref<ReturnType<typeof planRoute> | null>(null);
const capacityKg = ref<number | string>('');

const hasDepot = computed(
    () => currentUser.value?.lat != null && currentUser.value?.lng != null,
);

/** Total berat listing yang sedang dipilih (kg). */
const selectedWeight = computed(() =>
    props.listings
        .filter((l) => selectedIds.value.has(l.id))
        .reduce((sum, l) => sum + Number(l.berat), 0),
);

/** Id titik yang tidak masuk rute karena melebihi kapasitas. */
const excludedIds = computed<Set<number>>(
    () => new Set((routeResult.value?.excluded ?? []).map((p) => Number(p.id))),
);

// ── Pagination daftar titik pickup ────────────────────────────────────────
const PER_PAGE = 5;
const pickupPage = ref(1);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(props.listings.length / PER_PAGE)),
);
const pagedListings = computed(() => {
    const start = (pickupPage.value - 1) * PER_PAGE;

    return props.listings.slice(start, start + PER_PAGE);
});

function goToPage(p: number): void {
    pickupPage.value = Math.min(Math.max(1, p), totalPages.value);
}

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
        .filter(
            (l) =>
                selectedIds.value.has(l.id) && l.lat != null && l.lng != null,
        )
        .map((l) => ({
            id: l.id,
            lat: l.lat!,
            lng: l.lng!,
            weight: Number(l.berat),
            label: jenisLabel(l.jenis_sampah) + ' – ' + l.user.name,
        }));

    if (pts.length === 0) {
        routeResult.value = null;
        updateRouteOnMap([]);

        return;
    }

    const userLat = currentUser.value?.lat ?? undefined;
    const userLng = currentUser.value?.lng ?? undefined;
    const cap = Number(capacityKg.value) || undefined;
    routeResult.value = planRoute(
        pts,
        userLat ?? undefined,
        userLng ?? undefined,
        cap,
    );
    updateRouteOnMap(routeResult.value.order);
}

function resetRoute(): void {
    selectedIds.value = new Set();
    routeResult.value = null;
    updateRouteOnMap([]);
}

// ── Buka rute di Google Maps (rute mengikuti jalan asli) ──────────────────
// Pengepul mengangkut dengan kendaraan, jadi moda selalu "driving".
const GMAPS_TRAVELMODE = 'driving';

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
    const waypointCount = hasDepot.value
        ? Math.max(0, stops - 1)
        : Math.max(0, stops - 2);

    return Math.max(0, waypointCount - GMAPS_MAX_WAYPOINTS);
});

function openInGoogleMaps(): void {
    if (!routeResult.value || routeResult.value.order.length === 0) {
        return;
    }

    // Rangkai perhentian: depot (bila ada) lalu stop sesuai urutan TSP.
    const seq: { lat: number; lng: number }[] = [];

    if (hasDepot.value) {
        seq.push({
            lat: currentUser.value!.lat as number,
            lng: currentUser.value!.lng as number,
        });
    }

    routeResult.value.order.forEach((p) =>
        seq.push({ lat: p.lat, lng: p.lng }),
    );

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
        travelmode: GMAPS_TRAVELMODE,
    });

    if (waypoints.length > 0) {
        params.set(
            'waypoints',
            waypoints.map((w) => coordStr(w.lat, w.lng)).join('|'),
        );
    }

    window.open(
        `https://www.google.com/maps/dir/?${params.toString()}`,
        '_blank',
        'noopener',
    );
}

// Hitung ulang saat kapasitas berubah agar muatan/rute menyesuaikan.
watch(capacityKg, () => {
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
        coords.push([
            currentUser.value!.lat as number,
            currentUser.value!.lng as number,
        ]);
    }

    order.forEach((p) => coords.push([p.lat, p.lng]));

    if (coords.length > 1) {
        routeLine = L.polyline(coords, {
            color: '#2563eb',
            weight: 4,
            opacity: 0.85,
        }).addTo(leafletMap);
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

const tawarFor = ref<number | null>(null);
const tawarHarga = ref('');

function penawaranAktif(
    item: ListingItem,
): { id: number; harga: number; status: string } | undefined {
    return item.penawaran?.find((p) => p.status === 'diajukan');
}

function openTawar(item: ListingItem): void {
    tawarFor.value = item.id;
    tawarHarga.value = String(item.harga);
}

function submitTawar(id: number): void {
    if (!tawarHarga.value) {
        return;
    }

    router.post(
        `/pengepul/penawaran/${id}`,
        { harga: tawarHarga.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                tawarFor.value = null;
                tawarHarga.value = '';
            },
        },
    );
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
        iconRetinaUrl:
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl:
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    const firstWithCoord = props.listings.find(
        (l) => l.lat != null && l.lng != null,
    );
    const center: [number, number] = firstWithCoord
        ? [firstWithCoord.lat!, firstWithCoord.lng!]
        : [-8.6705, 115.2126]; // Denpasar, Bali

    leafletMap = L.map('leaflet-map').setView(center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(leafletMap);

    // Add markers for listings with coordinates
    for (const listing of props.listings) {
        if (listing.lat != null && listing.lng != null) {
            const marker = L.marker([listing.lat, listing.lng]).addTo(
                leafletMap,
            );
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
        L.marker([uLat, uLng], { icon: depotIcon })
            .addTo(leafletMap)
            .bindPopup('Lokasi Anda');
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
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />

    <div class="flex h-[calc(100vh-4rem)] flex-1 flex-col gap-4 p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-bold text-green-900">
                Ketersediaan Sampah
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Listing terfilter sesuai jenis yang Anda tangani
            </p>
        </div>

        <div
            class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-[1fr_380px]"
        >
            <!-- Map (kiri, dominan) -->
            <div
                id="leaflet-map"
                class="min-h-[360px] overflow-hidden rounded-xl border border-gray-200"
            />

            <!-- Panel kanan -->
            <div class="flex min-h-0 flex-col gap-3 overflow-y-auto pr-1">
                <!-- Header -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="flex items-center gap-2">
                        <Route class="h-5 w-5 text-green-700" />
                        <h2 class="text-base font-semibold text-green-900">
                            Hitung Jarak Rute
                        </h2>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Pilih listing di daftar atau peta untuk melihat rute
                        pickup terbaik.
                    </p>

                    <!-- Kapasitas muatan pengepul -->
                    <div class="mt-3">
                        <label
                            for="capacity"
                            class="flex items-center gap-1.5 text-xs font-medium text-gray-600"
                        >
                            <Weight class="h-3.5 w-3.5" />
                            Kapasitas Muatan (kg)
                        </label>
                        <input
                            id="capacity"
                            v-model="capacityKg"
                            type="number"
                            min="0"
                            step="0.1"
                            inputmode="decimal"
                            placeholder="Kosongkan = tanpa batas"
                            class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus-visible:ring-1 focus-visible:ring-green-500 focus-visible:outline-none"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Dipilih: {{ selectedWeight }} kg<span
                                v-if="Number(capacityKg) > 0"
                            >
                                / {{ Number(capacityKg) }} kg</span
                            >
                        </p>
                    </div>

                    <!-- Titik Awal (A) -->
                    <div
                        class="mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-semibold text-green-800"
                        >
                            <MapPin class="h-3.5 w-3.5" />
                            Titik Awal (A)
                        </div>
                        <p
                            v-if="hasDepot"
                            class="mt-0.5 ml-5 text-sm text-gray-700"
                        >
                            Lokasi Anda
                        </p>
                        <p v-else class="mt-0.5 ml-5 text-xs text-amber-600">
                            ⚠ Lokasi Anda belum diatur
                        </p>
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
                        <div
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Route class="h-3.5 w-3.5" />
                            Jarak Tempuh
                        </div>
                        <div class="mt-1 text-xl font-bold text-green-900">
                            {{ routeResult.totalDistanceKm }} km
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <div
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            Estimasi Waktu
                        </div>
                        <div class="mt-1 text-xl font-bold text-green-900">
                            {{ routeResult.estimatedMinutes }} menit
                        </div>
                    </div>
                    <div
                        class="col-span-2 rounded-xl border border-gray-200 bg-white p-3 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground"
                                >Muatan Terangkut</span
                            >
                            <span class="font-semibold text-green-900">
                                {{ routeResult.totalWeightKg }} kg<span
                                    v-if="Number(capacityKg) > 0"
                                    class="text-muted-foreground"
                                >
                                    / {{ Number(capacityKg) }} kg</span
                                >
                            </span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-muted-foreground"
                                >Est. Biaya BBM</span
                            >
                            <span class="font-semibold text-green-900">{{
                                formatRp(routeResult.estimatedCostRp)
                            }}</span>
                        </div>
                    </div>

                    <p
                        v-if="routeResult.excluded.length > 0"
                        class="col-span-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
                    >
                        ⚠ {{ routeResult.excluded.length }} titik tidak masuk
                        rute karena melebihi kapasitas
                        {{ Number(capacityKg) }} kg.
                    </p>
                    <p
                        v-else-if="routeResult.order.length === 0"
                        class="col-span-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
                    >
                        ⚠ Tidak ada pickup yang muat dalam kapasitas
                        {{ Number(capacityKg) }} kg.
                    </p>

                    <!-- Buka di Google Maps (rute mengikuti jalan asli) -->
                    <Button @click="openInGoogleMaps">
                        <ExternalLink class="h-4 w-4" />
                        Buka di Google Maps
                    </Button>
                    <p
                        v-if="gmapsOverflow > 0"
                        class="col-span-2 text-xs text-amber-600"
                    >
                        ⚠ Google Maps membatasi titik perantara —
                        {{ gmapsOverflow }} titik terakhir tidak ikut dikirim.
                    </p>
                </div>

                <!-- Ringkasan Rute -->
                <div
                    v-if="routeResult"
                    class="rounded-xl border border-gray-200 bg-white p-4"
                >
                    <div
                        class="flex items-center gap-2 text-sm font-semibold text-green-900"
                    >
                        <Navigation class="h-4 w-4" />
                        Ringkasan Rute
                    </div>
                    <ol class="mt-2 space-y-1.5 text-sm">
                        <li
                            v-if="hasDepot"
                            class="flex items-center gap-2 text-gray-600"
                        >
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
                    <h3 class="text-sm font-semibold text-green-900">
                        Titik Pickup
                    </h3>
                    <div class="mt-3 flex flex-col gap-2.5">
                        <div
                            v-if="listings.length === 0"
                            class="py-8 text-center text-sm text-gray-400"
                        >
                            <p>Tidak ada listing yang sesuai jenis Anda.</p>
                            <a
                                href="/pengepul/jenis"
                                class="text-green-700 hover:underline"
                                >Tambah jenis yang ditangani →</a
                            >
                        </div>

                        <div
                            v-for="item in pagedListings"
                            :key="item.id"
                            class="cursor-pointer rounded-xl border p-3 transition-colors"
                            :class="[
                                selectedIds.has(item.id)
                                    ? 'border-green-500 bg-green-50'
                                    : 'border-gray-200 hover:border-gray-300',
                                excludedIds.has(item.id) ? 'opacity-60' : '',
                            ]"
                            @click="toggleSelect(item.id)"
                        >
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.has(item.id)"
                                            class="accent-green-700"
                                            @click.stop="toggleSelect(item.id)"
                                        />
                                        <span class="text-sm font-medium">{{
                                            jenisLabel(item.jenis_sampah)
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-0.5 ml-5 text-sm text-gray-500"
                                    >
                                        {{ item.berat }} kg ·
                                        {{ formatRp(item.harga) }}
                                    </div>
                                    <div
                                        class="ml-5 truncate text-xs text-gray-400"
                                    >
                                        {{ item.user.name }}
                                    </div>
                                    <div
                                        v-if="!item.lat"
                                        class="mt-0.5 ml-5 text-xs text-amber-500"
                                    >
                                        ⚠ Tanpa koordinat
                                    </div>
                                    <div
                                        v-if="excludedIds.has(item.id)"
                                        class="mt-0.5 ml-5 text-xs font-medium text-amber-600"
                                    >
                                        ⚠ Melebihi kapasitas
                                    </div>
                                </div>
                                <div
                                    class="flex shrink-0 gap-2 sm:flex-col sm:items-end"
                                    @click.stop
                                >
                                    <span
                                        v-if="penawaranAktif(item)"
                                        class="rounded-lg bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700"
                                    >
                                        Menunggu ·
                                        {{
                                            formatRp(
                                                penawaranAktif(item)!.harga,
                                            )
                                        }}
                                    </span>
                                    <template v-else>
                                        <button
                                            @click="klaim(item.id)"
                                            class="flex-1 rounded-lg bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-800 sm:flex-none"
                                        >
                                            Klaim
                                        </button>
                                        <button
                                            @click="openTawar(item)"
                                            class="flex-1 rounded-lg border border-amber-400 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 sm:flex-none"
                                        >
                                            Tawar
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Form tawar harga -->
                            <div
                                v-if="tawarFor === item.id"
                                class="mt-2 flex items-center gap-2"
                                @click.stop
                            >
                                <input
                                    v-model="tawarHarga"
                                    type="number"
                                    min="0"
                                    :placeholder="String(item.harga)"
                                    class="h-8 flex-1 rounded border border-gray-300 px-2 text-sm focus-visible:ring-1 focus-visible:ring-amber-400 focus-visible:outline-none"
                                />
                                <button
                                    @click="submitTawar(item.id)"
                                    class="rounded bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
                                >
                                    Kirim
                                </button>
                                <button
                                    @click="tawarFor = null"
                                    class="rounded border border-gray-300 px-2 py-1.5 text-xs hover:bg-gray-50"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="mt-3 flex items-center justify-between gap-2"
                    >
                        <button
                            type="button"
                            @click="goToPage(pickupPage - 1)"
                            :disabled="pickupPage === 1"
                            class="flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-40"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            <span class="hidden sm:inline">Sebelumnya</span>
                        </button>
                        <span class="text-xs text-muted-foreground"
                            >Hal {{ pickupPage }} / {{ totalPages }}</span
                        >
                        <button
                            type="button"
                            @click="goToPage(pickupPage + 1)"
                            :disabled="pickupPage === totalPages"
                            class="flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-40"
                        >
                            <span class="hidden sm:inline">Berikutnya</span>
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
