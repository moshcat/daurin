<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Bid {
    id: number;
    industri_nama: string;
    harga: number;
    is_buyout: boolean;
    created_at: string;
}

interface LelangProp {
    id: number;
    status: string;
    harga_awal: number;
    kelipatan: number;
    harga_buyout: number | null;
    harga_tertinggi: number | null;
    minimal_bid: number;
    waktu_selesai: string;
    pemenang_nama: string | null;
    harga_final: number | null;
    bahan_baku: {
        jenis_sampah: string;
        jenis_label: string;
        berat: number;
        peruntukan: string | null;
        pengepul: string;
        source: { jenis_label: string; berat: number; rt: string | null } | null;
    };
    bids: Bid[];
}

interface Member {
    id: number;
    name: string;
    role: string;
}

const props = defineProps<{
    lelang: LelangProp;
    canBid: boolean;
    isOwner: boolean;
    isWinner: boolean;
    pesanan: { id: number; sudah_dibayar: boolean } | null;
}>();

// ─── Live state (Echo memperbaruinya; props menyinkronkan saat reload) ──────────
const status = ref(props.lelang.status);
const bids = ref<Bid[]>([]);
const hargaTertinggi = ref<number | null>(props.lelang.harga_tertinggi);
const minimalBid = ref(props.lelang.minimal_bid);
const waktuSelesai = ref(props.lelang.waktu_selesai);
const pemenangNama = ref<string | null>(props.lelang.pemenang_nama);
const hargaFinal = ref<number | null>(props.lelang.harga_final);
const penonton = ref<Member[]>([]);

const tawarHarga = ref('');
const errorBid = ref('');

function initFromProps(l: LelangProp): void {
    status.value = l.status;
    bids.value = [...l.bids];
    hargaTertinggi.value = l.harga_tertinggi;
    minimalBid.value = l.minimal_bid;
    waktuSelesai.value = l.waktu_selesai;
    pemenangNama.value = l.pemenang_nama;
    hargaFinal.value = l.harga_final;
}

// Sinkron ke kebenaran server pada setiap reload Inertia.
watch(() => props.lelang, (l) => initFromProps(l), { immediate: true });

// ─── Countdown ──────────────────────────────────────────────────────────────
const now = ref(Date.now());
let timer: number | undefined;

const sisaDetik = computed(() =>
    Math.max(0, Math.floor((new Date(waktuSelesai.value).getTime() - now.value) / 1000)),
);

const countdown = computed(() => {
    const s = sisaDetik.value;
    const j = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const d = s % 60;
    const pad = (n: number) => String(n).padStart(2, '0');

    return j > 0 ? `${j}:${pad(m)}:${pad(d)}` : `${pad(m)}:${pad(d)}`;
});

const isOpen = computed(() => status.value === 'berlangsung');
const isSnipe = computed(() => isOpen.value && sisaDetik.value <= 60 && sisaDetik.value > 0);

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatRp(n: number | null): string {
    return n === null ? '—' : 'Rp ' + n.toLocaleString('id-ID');
}

function formatTime(s: string): string {
    return new Date(s).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ─── Echo real-time ─────────────────────────────────────────────────────────
function onBid(e: {
    bid_id: number;
    industri_nama: string;
    harga: number;
    harga_tertinggi: number;
    is_buyout: boolean;
    waktu_selesai: string;
    created_at: string;
}): void {
    if (bids.value.some((b) => b.id === e.bid_id)) {
        return; // dedupe
    }

    bids.value.unshift({
        id: e.bid_id,
        industri_nama: e.industri_nama,
        harga: e.harga,
        is_buyout: e.is_buyout,
        created_at: e.created_at,
    });
    hargaTertinggi.value = e.harga_tertinggi;
    minimalBid.value = e.harga_tertinggi + props.lelang.kelipatan;
    waktuSelesai.value = e.waktu_selesai;
}

function onClosed(e: {
    status: string;
    pemenang_nama: string | null;
    harga_final: number | null;
}): void {
    status.value = e.status;
    pemenangNama.value = e.pemenang_nama;
    hargaFinal.value = e.harga_final;
}

onMounted(() => {
    timer = window.setInterval(() => (now.value = Date.now()), 1000);

    if (typeof window === 'undefined' || !window.Echo) {
        return;
    }

    window.Echo.join(`lelang.${props.lelang.id}`)
        .here((members: Member[]) => (penonton.value = members))
        .joining((m: Member) => penonton.value.push(m))
        .leaving((m: Member) => (penonton.value = penonton.value.filter((x) => x.id !== m.id)))
        .listen('.BidPlaced', onBid)
        .listen('.LelangClosed', onClosed);

    // Sinkron ulang bila koneksi pulih setelah terputus.
    window.Echo.connector?.pusher?.connection?.bind('connected', () => {
        router.reload({ only: ['lelang'], preserveScroll: true });
    });
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.leave(`lelang.${props.lelang.id}`);
    }
});

// ─── Actions ──────────────────────────────────────────────────────────────────
function submitBid(): void {
    const harga = Number(tawarHarga.value);
    if (!harga || harga < minimalBid.value) {
        errorBid.value = `Tawaran minimal ${formatRp(minimalBid.value)}.`;
        return;
    }
    errorBid.value = '';

    router.post(
        `/lelang/${props.lelang.id}/bid`,
        { harga },
        {
            preserveScroll: true,
            onSuccess: () => (tawarHarga.value = ''),
            onError: (e) => (errorBid.value = e.harga ?? e.lelang ?? 'Gagal menawar.'),
        },
    );
}

function buyout(): void {
    if (!confirm(`Beli langsung seharga ${formatRp(props.lelang.harga_buyout)}?`)) {
        return;
    }
    router.post(`/lelang/${props.lelang.id}/buyout`, {}, { preserveScroll: true });
}

function cancel(): void {
    if (!confirm('Batalkan negosiasi ini?')) {
        return;
    }
    router.delete(`/pengepul/lelang/${props.lelang.id}`, { preserveScroll: true });
}

function bayar(): void {
    if (!props.pesanan || !confirm('Bayar pesanan ini (simulasi)?')) {
        return;
    }
    router.post(`/industri/pesanan/${props.pesanan.id}/bayar`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Ruang Negosiasi" />

    <div class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <a href="javascript:history.back()" class="text-sm text-green-700 hover:underline">← Kembali</a>
            <span class="flex items-center gap-1.5 text-xs text-gray-500">
                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                {{ penonton.length }} menonton
            </span>
        </div>

        <div>
            <h1 class="text-2xl font-bold text-green-900">💬 Negosiasi Harga</h1>
            <p class="text-sm text-gray-500">Penawaran terbuka — tawaran tertinggi saat waktu habis yang menang.</p>
        </div>

        <!-- Status & harga tertinggi -->
        <Card class="border-2" :class="isOpen ? 'border-green-300' : 'border-gray-200'">
            <CardContent class="flex flex-col items-center gap-1 py-6">
                <span class="text-xs uppercase tracking-wide text-gray-500">Tawaran Tertinggi</span>
                <span class="text-4xl font-extrabold text-green-800">
                    {{ formatRp(hargaTertinggi ?? lelang.harga_awal) }}
                </span>
                <span v-if="hargaTertinggi === null" class="text-xs text-gray-400">
                    Harga awal · belum ada tawaran
                </span>

                <div v-if="isOpen" class="mt-3 flex flex-col items-center">
                    <span
                        class="rounded-lg px-4 py-1 font-mono text-2xl font-bold tabular-nums"
                        :class="isSnipe ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-gray-100 text-gray-700'"
                    >
                        {{ countdown }}
                    </span>
                    <span v-if="isSnipe" class="mt-1 text-xs font-medium text-red-600">
                        ⚡ Waktu kritis — tawaran memperpanjang +60 dtk
                    </span>
                    <span v-else class="mt-1 text-xs text-gray-400">sisa waktu</span>
                </div>

                <!-- Hasil akhir -->
                <div v-else class="mt-3 text-center">
                    <div v-if="status === 'selesai'" class="rounded-lg bg-green-50 px-4 py-2">
                        <p class="text-sm font-semibold text-green-800">🏆 Negosiasi Selesai</p>
                        <p class="text-sm text-gray-600">
                            Pemenang: <span class="font-medium">{{ pemenangNama }}</span> ·
                            {{ formatRp(hargaFinal) }}
                        </p>
                    </div>
                    <div v-else-if="status === 'gagal'" class="rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-700">
                        Negosiasi berakhir tanpa pemenang (tanpa tawaran / di bawah harga minimum).
                    </div>
                    <div v-else class="rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-600">
                        Negosiasi dibatalkan.
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Pembayaran (pemenang) -->
        <Card v-if="isWinner && status === 'selesai' && pesanan" class="border-green-300">
            <CardContent class="flex items-center justify-between py-4">
                <div>
                    <p class="text-sm font-semibold text-green-900">Pembayaran</p>
                    <p class="text-xs text-gray-500">Selesaikan pembayaran untuk memproses pesanan.</p>
                </div>
                <span
                    v-if="pesanan.sudah_dibayar"
                    class="rounded-lg bg-green-100 px-4 py-2 text-sm font-semibold text-green-700"
                >
                    ✅ Lunas
                </span>
                <button
                    v-else
                    @click="bayar"
                    class="rounded-lg bg-green-700 px-5 py-2 text-sm font-semibold text-white hover:bg-green-800"
                >
                    💳 Bayar (Simulasi)
                </button>
            </CardContent>
        </Card>

        <!-- Detail bahan baku + traceability -->
        <Card class="border">
            <CardHeader class="pb-2"><CardTitle class="text-sm">Barang Dinegosiasikan</CardTitle></CardHeader>
            <CardContent class="grid grid-cols-2 gap-2 pb-4 text-sm">
                <div><span class="text-gray-500">Jenis:</span> {{ lelang.bahan_baku.jenis_label }}</div>
                <div><span class="text-gray-500">Berat:</span> {{ lelang.bahan_baku.berat }} kg</div>
                <div><span class="text-gray-500">Pengepul:</span> {{ lelang.bahan_baku.pengepul }}</div>
                <div><span class="text-gray-500">Harga Awal:</span> {{ formatRp(lelang.harga_awal) }}</div>
                <div v-if="lelang.bahan_baku.peruntukan" class="col-span-2">
                    <span class="text-gray-500">Peruntukan:</span> {{ lelang.bahan_baku.peruntukan }}
                </div>
                <div v-if="lelang.bahan_baku.source" class="col-span-2 text-xs text-blue-600">
                    🔗 Asal: {{ lelang.bahan_baku.source.jenis_label }}
                    ({{ lelang.bahan_baku.source.berat }} kg)
                    <template v-if="lelang.bahan_baku.source.rt"> · RT: {{ lelang.bahan_baku.source.rt }}</template>
                </div>
            </CardContent>
        </Card>

        <!-- Form tawar (industri, bukan penjual) -->
        <Card v-if="canBid && isOpen" class="border-green-200">
            <CardContent class="space-y-3 py-4">
                <div>
                    <Label class="text-xs">Tawaran Anda (min. {{ formatRp(minimalBid) }})</Label>
                    <Input
                        v-model="tawarHarga"
                        type="number"
                        :placeholder="String(minimalBid)"
                        class="mt-1"
                        @keyup.enter="submitBid"
                    />
                    <p v-if="errorBid" class="mt-1 text-xs text-red-500">{{ errorBid }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="submitBid"
                        class="rounded-lg bg-green-700 px-5 py-2 text-sm font-semibold text-white hover:bg-green-800"
                    >
                        💰 Tawar
                    </button>
                    <button
                        v-if="lelang.harga_buyout"
                        @click="buyout"
                        class="rounded-lg border border-amber-400 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50"
                    >
                        ⚡ Beli Langsung ({{ formatRp(lelang.harga_buyout) }})
                    </button>
                </div>
            </CardContent>
        </Card>

        <!-- Kontrol pemilik (pengepul) -->
        <div v-if="isOwner && isOpen && bids.length === 0">
            <button
                @click="cancel"
                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
            >
                Batalkan Negosiasi
            </button>
        </div>
        <p v-else-if="isOwner && isOpen" class="text-xs text-gray-400">
            Sudah ada tawaran — negosiasi tidak dapat dibatalkan.
        </p>

        <!-- Riwayat tawaran (live) -->
        <div>
            <h2 class="mb-2 text-sm font-semibold text-gray-700">Riwayat Tawaran ({{ bids.length }})</h2>
            <div v-if="bids.length === 0" class="rounded-lg border border-dashed py-6 text-center text-sm text-gray-400">
                Belum ada tawaran. Jadilah yang pertama!
            </div>
            <transition-group v-else name="bid" tag="div" class="flex flex-col gap-2">
                <div
                    v-for="(b, i) in bids"
                    :key="b.id"
                    class="flex items-center justify-between rounded-xl border px-4 py-2.5"
                    :class="i === 0 ? 'border-green-300 bg-green-50' : 'border-gray-100 bg-white'"
                >
                    <div class="flex items-center gap-2">
                        <span v-if="i === 0" class="text-xs font-bold text-green-700">TERTINGGI</span>
                        <span class="text-sm text-gray-600">{{ b.industri_nama }}</span>
                        <span v-if="b.is_buyout" class="rounded bg-amber-100 px-1.5 text-[10px] text-amber-700">buyout</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold" :class="i === 0 ? 'text-green-800' : 'text-gray-700'">
                            {{ formatRp(b.harga) }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ formatTime(b.created_at) }}</span>
                    </div>
                </div>
            </transition-group>
        </div>
    </div>
</template>

<style scoped>
.bid-enter-active {
    transition: all 0.3s ease;
}
.bid-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
