<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface Penawaran {
    id: number;
    harga: number;
    status: string;
    pengepul: { name: string };
}

interface Listing {
    id: number;
    jenis_sampah: string;
    berat: number;
    harga: number;
    status: string;
    foto_path?: string;
    ai_label?: string;
    ai_confidence?: number;
    created_at: string;
    penawaran?: Penawaran[];
}

defineProps<{ listings: Listing[] }>();

function terimaPenawaran(id: number): void {
    if (!confirm('Terima penawaran ini? Listing akan diambil pengepul.')) return;
    router.post(`/rt/penawaran/${id}/terima`, {}, { preserveScroll: true });
}

function tolakPenawaran(id: number): void {
    router.post(`/rt/penawaran/${id}/tolak`, {}, { preserveScroll: true });
}

const statusClass: Record<string, string> = {
    tersedia: 'bg-green-100 text-green-700',
    diambil: 'bg-amber-100 text-amber-700',
    terjual: 'bg-gray-100 text-gray-500',
};

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

function deleteListing(id: number): void {
    if (!confirm('Hapus listing ini?')) return;
    router.delete(`/rt/${id}`);
}
</script>

<template>
    <Head title="Listing Sampah Saya" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-green-900">Listing Sampah Saya</h1>
            <a
                href="/rt/baru"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
            >
                + Listing Baru
            </a>
        </div>

        <div v-if="listings.length === 0" class="flex flex-1 flex-col items-center justify-center py-16 text-gray-400">
            <p class="text-lg">Belum ada listing.</p>
            <a href="/rt/baru" class="mt-2 text-sm text-green-700 hover:underline">Buat listing pertama →</a>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="item in listings" :key="item.id" class="border shadow-sm">
                <CardHeader class="px-4 pt-4 pb-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold">{{ jenisLabel(item.jenis_sampah) }}</span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="statusClass[item.status] ?? 'bg-gray-100 text-gray-500'"
                        >
                            {{ item.status }}
                        </span>
                    </div>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="text-xl font-bold text-green-800">{{ formatRp(item.harga) }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">{{ item.berat }} kg</div>
                    <div v-if="item.ai_label" class="mt-1 text-xs text-blue-600">
                        🤖 AI: {{ item.ai_label }} ({{ Math.round((item.ai_confidence ?? 0) * 100) }}%)
                    </div>
                    <!-- Penawaran masuk dari pengepul -->
                    <div v-if="item.penawaran && item.penawaran.length > 0" class="mt-3 space-y-2 border-t pt-3">
                        <p class="text-xs font-semibold text-amber-700">💬 Penawaran masuk ({{ item.penawaran.length }})</p>
                        <div
                            v-for="p in item.penawaran"
                            :key="p.id"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-2"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">{{ p.pengepul.name }}</span>
                                <span class="text-sm font-bold text-amber-800">{{ formatRp(p.harga) }}</span>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <button
                                    @click="terimaPenawaran(p.id)"
                                    class="flex-1 rounded bg-green-700 py-1 text-xs font-medium text-white hover:bg-green-800"
                                >
                                    Terima
                                </button>
                                <button
                                    @click="tolakPenawaran(p.id)"
                                    class="rounded border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50"
                                >
                                    Tolak
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button
                            v-if="item.status === 'tersedia'"
                            @click="deleteListing(item.id)"
                            class="rounded border border-red-300 px-3 py-1 text-xs text-red-600 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                        <span v-else class="text-xs italic text-gray-400">Tidak dapat dihapus</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
