<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TraceChain from '@/components/TraceChain.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TraceNode } from '@/lib/listing';

interface NegosiasiItem {
    id: number;
    pengirim: string;
    harga: number;
    catatan?: string;
    created_at: string;
}

interface BahanBakuDetail {
    id: number;
    jenis_sampah: string;
    berat: number;
    harga_awal: number;
    peruntukan?: string;
    user: { name: string };
    source_listing?: {
        jenis_sampah: string;
        berat: number;
        ai_label?: string | null;
        user?: { name: string };
    } | null;
}

interface PesananDetail {
    id: number;
    status: string;
    harga_sepakat?: number;
    bahan_baku: BahanBakuDetail;
    industri: { name: string };
    negosiasi: NegosiasiItem[];
}

interface JenisOption {
    value: string;
    label: string;
}

const props = defineProps<{
    pesanan: PesananDetail;
    jenisSampahOptions: JenisOption[];
}>();

const tawarHarga = ref('');
const tawarCatatan = ref('');

const lastHarga = computed(
    () => props.pesanan.negosiasi.at(-1)?.harga ?? props.pesanan.bahan_baku.harga_awal,
);

function jenisLabel(v: string): string {
    return props.jenisSampahOptions.find((o) => o.value === v)?.label ?? v;
}

const traceNodes = computed<TraceNode[]>(() => {
    const nodes: TraceNode[] = [];
    const src = props.pesanan.bahan_baku.source_listing;

    if (src) {
        nodes.push({
            role: 'rumah_tangga',
            actor: src.user?.name ?? 'Rumah Tangga',
            title: jenisLabel(src.jenis_sampah),
            detail: `${Number(src.berat)} kg${src.ai_label ? ` · AI: ${src.ai_label}` : ''}`,
        });
    }

    nodes.push({
        role: 'pengepul',
        actor: props.pesanan.bahan_baku.user.name,
        title: jenisLabel(props.pesanan.bahan_baku.jenis_sampah),
        detail: `${Number(props.pesanan.bahan_baku.berat)} kg · bahan baku`,
    });

    return nodes;
});

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatDate(s: string): string {
    return new Date(s).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}

function sendTawar(): void {
    if (!tawarHarga.value) {
return;
}

    router.post(
        `/industri/pesanan/${props.pesanan.id}/tawar`,
        { harga: tawarHarga.value, catatan: tawarCatatan.value },
        { onSuccess: () => {
 tawarHarga.value = ''; tawarCatatan.value = ''; 
} },
    );
}

function deal(): void {
    if (!confirm('Sepakat dengan harga terakhir?')) {
return;
}

    router.post(`/industri/pesanan/${props.pesanan.id}/deal`);
}

function batal(): void {
    if (!confirm('Batalkan pesanan ini?')) {
return;
}

    router.post(`/industri/pesanan/${props.pesanan.id}/batal`);
}
</script>

<template>
    <Head title="Negosiasi" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6 max-w-3xl">
        <div>
            <a href="/industri" class="text-sm text-green-700 hover:underline">← Kembali</a>
            <h1 class="mt-2 text-2xl font-bold text-green-900">Negosiasi Harga</h1>
        </div>

        <!-- Detail bahan baku -->
        <Card class="border shadow-sm">
            <CardHeader class="pb-2"><CardTitle class="text-sm">Detail Bahan Baku</CardTitle></CardHeader>
            <CardContent class="grid grid-cols-2 gap-2 pb-4 text-sm">
                <div><span class="text-gray-500">Jenis:</span> {{ jenisLabel(pesanan.bahan_baku.jenis_sampah) }}</div>
                <div><span class="text-gray-500">Berat:</span> {{ pesanan.bahan_baku.berat }} kg</div>
                <div><span class="text-gray-500">Harga Awal:</span> {{ formatRp(pesanan.bahan_baku.harga_awal) }}</div>
                <div><span class="text-gray-500">Pengepul:</span> {{ pesanan.bahan_baku.user.name }}</div>
                <div v-if="pesanan.bahan_baku.peruntukan" class="col-span-2">
                    <span class="text-gray-500">Peruntukan:</span> {{ pesanan.bahan_baku.peruntukan }}
                </div>
                <div class="col-span-2">
                    <span class="text-gray-500">Status Pesanan:</span>
                    <span
                        class="ml-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="{
                            'bg-amber-100 text-amber-700': pesanan.status === 'nego',
                            'bg-green-100 text-green-700': pesanan.status === 'deal',
                            'bg-red-100 text-red-700': pesanan.status === 'batal',
                        }"
                    >
                        {{ pesanan.status }}{{ pesanan.harga_sepakat ? ' — ' + formatRp(pesanan.harga_sepakat) : '' }}
                    </span>
                </div>
            </CardContent>
        </Card>

        <!-- Asal material (traceability) -->
        <Card class="border">
            <CardHeader class="pb-2"><CardTitle class="text-sm">Asal Material</CardTitle></CardHeader>
            <CardContent class="pb-4">
                <TraceChain :nodes="traceNodes" />
            </CardContent>
        </Card>

        <!-- Negosiasi thread -->
        <div class="flex flex-col gap-3">
            <div
                v-for="n in pesanan.negosiasi"
                :key="n.id"
                class="rounded-xl p-3 text-sm"
                :class="
                    n.pengirim === 'industri'
                        ? 'ml-8 border border-blue-100 bg-blue-50'
                        : 'mr-8 border border-green-100 bg-green-50'
                "
            >
                <div class="mb-1 flex justify-between items-start">
                    <span
                        class="font-semibold"
                        :class="n.pengirim === 'industri' ? 'text-blue-700' : 'text-green-700'"
                    >
                        {{ n.pengirim === 'industri' ? '🏭 Anda' : '♻️ Pengepul' }}
                    </span>
                    <span class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</span>
                </div>
                <div
                    class="text-lg font-bold"
                    :class="n.pengirim === 'industri' ? 'text-blue-800' : 'text-green-800'"
                >
                    {{ formatRp(n.harga) }}
                </div>
                <div v-if="n.catatan" class="mt-0.5 text-xs text-gray-500">{{ n.catatan }}</div>
            </div>
        </div>

        <!-- Action form (only if still nego) -->
        <div v-if="pesanan.status === 'nego'" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <Label class="text-xs">Harga Penawaran (Rp)</Label>
                    <Input
                        v-model="tawarHarga"
                        type="number"
                        :placeholder="String(lastHarga)"
                        class="mt-1"
                    />
                </div>
                <div>
                    <Label class="text-xs">Catatan (opsional)</Label>
                    <Input v-model="tawarCatatan" type="text" placeholder="Catatan..." class="mt-1" />
                </div>
            </div>
            <div class="flex gap-3 flex-wrap">
                <button
                    @click="sendTawar"
                    :disabled="!tawarHarga"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-40"
                >
                    Tawar Balik
                </button>
                <button
                    @click="deal"
                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
                >
                    ✓ Sepakat (Deal)
                </button>
                <button
                    @click="batal"
                    class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                >
                    Batalkan
                </button>
            </div>
        </div>
        <div v-else class="text-center py-4 text-sm text-gray-500">
            Pesanan sudah
            <span class="font-medium">{{ pesanan.status }}</span>.
            <span v-if="pesanan.harga_sepakat"> Harga deal: {{ formatRp(pesanan.harga_sepakat) }}</span>
        </div>
    </div>
</template>
