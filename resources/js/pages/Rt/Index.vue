<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

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
}

defineProps<{ listings: Listing[] }>();

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
