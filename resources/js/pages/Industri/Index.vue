<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface LelangItem {
    id: number;
    jenis_sampah: string;
    jenis_label: string;
    berat: number;
    peruntukan?: string | null;
    pengepul: string;
    harga_awal: number;
    harga_tertinggi: number | null;
    jumlah_bid: number;
    waktu_selesai: string;
}

interface JenisOption {
    value: string;
    label: string;
}

const props = defineProps<{
    lelangs: LelangItem[];
    jenisSampahOptions: JenisOption[];
}>();

const filterJenis = ref('');

const jenisBadgeClass: Record<string, string> = {
    plastik_pet: 'bg-blue-100 text-blue-800',
    plastik_hdpe: 'bg-blue-200 text-blue-900',
    kertas: 'bg-yellow-100 text-yellow-800',
    kardus: 'bg-amber-100 text-amber-800',
    logam: 'bg-gray-200 text-gray-800',
    kaleng: 'bg-gray-100 text-gray-700',
    kaca: 'bg-cyan-100 text-cyan-800',
    elektronik: 'bg-purple-100 text-purple-800',
};

const filtered = computed(() =>
    filterJenis.value
        ? props.lelangs.filter((b) => b.jenis_sampah === filterJenis.value)
        : props.lelangs,
);

const now = ref(Date.now());
let timer: number | undefined;
onMounted(() => (timer = window.setInterval(() => (now.value = Date.now()), 1000)));
onUnmounted(() => timer && clearInterval(timer));

function countdown(waktuSelesai: string): string {
    const s = Math.max(0, Math.floor((new Date(waktuSelesai).getTime() - now.value) / 1000));
    const j = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const d = s % 60;
    const pad = (n: number) => String(n).padStart(2, '0');
    return j > 0 ? `${j}:${pad(m)}:${pad(d)}` : `${pad(m)}:${pad(d)}`;
}

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}
</script>

<template>
    <Head title="Negosiasi Berlangsung" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold text-green-900">💬 Negosiasi Harga Berlangsung</h1>
                <a href="/industri/bahan-jadi" class="text-sm font-medium text-green-700 hover:underline">
                    → Bahan Baku Jadi
                </a>
            </div>
            <select
                v-model="filterJenis"
                class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
                <option value="">Semua jenis</option>
                <option v-for="opt in jenisSampahOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <div v-if="filtered.length === 0" class="flex flex-1 items-center justify-center text-gray-400">
            <p>Belum ada negosiasi berlangsung saat ini.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="item in filtered" :key="item.id" class="border shadow-sm">
                <CardHeader class="px-4 pt-4 pb-2">
                    <div class="flex items-center justify-between">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="jenisBadgeClass[item.jenis_sampah] ?? 'bg-gray-100 text-gray-700'"
                        >
                            {{ item.jenis_label }}
                        </span>
                        <Badge variant="secondary" class="font-mono text-[10px] tabular-nums">
                            ⏱ {{ countdown(item.waktu_selesai) }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="text-xs text-gray-400">Tawaran tertinggi</div>
                    <div class="text-xl font-bold text-green-800">
                        {{ formatRp(item.harga_tertinggi ?? item.harga_awal) }}
                    </div>
                    <div class="mt-0.5 text-sm text-gray-500">
                        {{ item.berat }} kg · {{ item.jumlah_bid }} tawaran
                    </div>
                    <div v-if="item.peruntukan" class="text-xs text-gray-400">{{ item.peruntukan }}</div>
                    <div class="text-xs text-gray-400">{{ item.pengepul }}</div>

                    <Link
                        :href="`/lelang/${item.id}`"
                        class="mt-3 block w-full rounded-lg bg-green-700 py-2 text-center text-sm font-medium text-white hover:bg-green-800"
                    >
                        Masuk Ruang Negosiasi →
                    </Link>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
