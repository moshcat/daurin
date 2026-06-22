<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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

const balasHarga = ref('');
const balasCatatan = ref('');

function jenisLabel(v: string): string {
    return props.jenisSampahOptions.find((o) => o.value === v)?.label ?? v;
}

const pesananStatusDetail = computed(() =>
    props.pesanan.status === 'deal' && props.pesanan.harga_sepakat
        ? `deal · ${formatRp(props.pesanan.harga_sepakat)}`
        : props.pesanan.status,
);

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
    nodes.push({
        role: 'industri',
        actor: props.pesanan.industri.name,
        title: 'Calon pembeli',
        detail: pesananStatusDetail.value,
    });

    return nodes;
});

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatDate(s: string): string {
    return new Date(s).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}

function sendBalas(): void {
    if (!balasHarga.value) {
return;
}

    router.post(
        `/pengepul/pesanan/${props.pesanan.id}/balas`,
        { harga: balasHarga.value, catatan: balasCatatan.value },
        { onSuccess: () => {
 balasHarga.value = ''; balasCatatan.value = ''; 
} },
    );
}

function deal(): void {
    if (!confirm('Setujui deal dengan harga ini?')) {
return;
}

    router.post(`/pengepul/pesanan/${props.pesanan.id}/deal`);
}
</script>

<template>
    <Head title="Negosiasi — Pengepul" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6 max-w-3xl">
        <div>
            <a href="/pengepul/bahan-baku" class="text-sm text-green-700 hover:underline">← Bahan Baku</a>
            <h1 class="mt-2 text-2xl font-bold text-green-900">Negosiasi dari Industri</h1>
            <p class="text-sm text-muted-foreground">Pembeli: {{ pesanan.industri.name }}</p>
        </div>

        <!-- Detail bahan baku -->
        <Card class="border shadow-sm">
            <CardHeader class="pb-2"><CardTitle class="text-sm">Detail Bahan Baku</CardTitle></CardHeader>
            <CardContent class="grid grid-cols-2 gap-2 pb-4 text-sm">
                <div><span class="text-gray-500">Jenis:</span> {{ jenisLabel(pesanan.bahan_baku.jenis_sampah) }}</div>
                <div><span class="text-gray-500">Berat:</span> {{ pesanan.bahan_baku.berat }} kg</div>
                <div><span class="text-gray-500">Harga Awal:</span> {{ formatRp(pesanan.bahan_baku.harga_awal) }}</div>
                <div>
                    <span class="text-gray-500">Status:</span>
                    <span
                        class="ml-1 font-medium"
                        :class="{
                            'text-amber-600': pesanan.status === 'nego',
                            'text-green-700': pesanan.status === 'deal',
                            'text-red-600': pesanan.status === 'batal',
                        }"
                    >
                        {{ pesanan.status }}{{ pesanan.harga_sepakat ? ' — ' + formatRp(pesanan.harga_sepakat) : '' }}
                    </span>
                </div>
            </CardContent>
        </Card>

        <!-- Asal material (traceability) -->
        <Card class="border shadow-sm">
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
                    n.pengirim === 'pengepul'
                        ? 'ml-8 border border-green-100 bg-green-50'
                        : 'mr-8 border border-blue-100 bg-blue-50'
                "
            >
                <div class="mb-1 flex justify-between">
                    <span
                        class="font-semibold"
                        :class="n.pengirim === 'pengepul' ? 'text-green-700' : 'text-blue-700'"
                    >
                        {{ n.pengirim === 'pengepul' ? '♻️ Anda' : '🏭 Industri' }}
                    </span>
                    <span class="text-xs text-gray-400">{{ formatDate(n.created_at) }}</span>
                </div>
                <div class="text-lg font-bold">{{ formatRp(n.harga) }}</div>
                <div v-if="n.catatan" class="mt-0.5 text-xs text-gray-500">{{ n.catatan }}</div>
            </div>
        </div>

        <!-- Action form -->
        <div v-if="pesanan.status === 'nego'" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <Label class="text-xs">Harga Balasan (Rp)</Label>
                    <Input v-model="balasHarga" type="number" class="mt-1" placeholder="Masukkan harga..." />
                </div>
                <div>
                    <Label class="text-xs">Catatan (opsional)</Label>
                    <Input v-model="balasCatatan" type="text" class="mt-1" placeholder="Catatan..." />
                </div>
            </div>
            <div class="flex gap-3 flex-wrap">
                <button
                    @click="sendBalas"
                    :disabled="!balasHarga"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-40"
                >
                    Balas Tawaran
                </button>
                <button
                    @click="deal"
                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800"
                >
                    ✓ Terima Deal
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
