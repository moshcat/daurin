<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

interface BahanBakuItem {
    id: number;
    jenis_sampah: string;
    peruntukan?: string;
    berat: number;
    harga_awal: number;
    status: string;
    user: { name: string };
}

interface JenisOption {
    value: string;
    label: string;
}

const props = defineProps<{
    bahanBaku: BahanBakuItem[];
    jenisSampahOptions: JenisOption[];
}>();

const filterJenis = ref('');
const showOrderFormFor = ref<number | null>(null);
const orderHarga = ref('');
const orderCatatan = ref('');

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
        ? props.bahanBaku.filter((b) => b.jenis_sampah === filterJenis.value)
        : props.bahanBaku,
);

function jenisLabel(v: string): string {
    return props.jenisSampahOptions.find((o) => o.value === v)?.label ?? v;
}

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function openOrderForm(item: BahanBakuItem): void {
    showOrderFormFor.value = item.id;
    orderHarga.value = String(item.harga_awal);
    orderCatatan.value = '';
}

function submitOrder(bahanBakuId: number): void {
    if (!orderHarga.value) return;
    router.post(
        '/industri/pesanan',
        { bahan_baku_id: bahanBakuId, harga: orderHarga.value, catatan: orderCatatan.value },
        {
            onSuccess: () => {
                showOrderFormFor.value = null;
                orderHarga.value = '';
            },
        },
    );
}
</script>

<template>
    <Head title="Cari Bahan Baku" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold text-green-900">Bahan Baku Tersedia</h1>
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
            <p>Tidak ada bahan baku tersedia.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="item in filtered" :key="item.id" class="border shadow-sm">
                <CardHeader class="px-4 pt-4 pb-2">
                    <div class="flex items-center justify-between">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="jenisBadgeClass[item.jenis_sampah] ?? 'bg-gray-100 text-gray-700'"
                        >
                            {{ jenisLabel(item.jenis_sampah) }}
                        </span>
                        <Badge variant="secondary" class="text-[10px]">tersedia</Badge>
                    </div>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <div class="text-xl font-bold text-green-800">{{ formatRp(item.harga_awal) }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">{{ item.berat }} kg</div>
                    <div v-if="item.peruntukan" class="text-xs text-gray-400">{{ item.peruntukan }}</div>
                    <div class="text-xs text-gray-400">{{ item.user.name }}</div>

                    <!-- Order form -->
                    <div class="mt-3">
                        <button
                            v-if="showOrderFormFor !== item.id"
                            @click="openOrderForm(item)"
                            class="w-full rounded-lg bg-green-700 py-2 text-sm font-medium text-white hover:bg-green-800"
                        >
                            Pesan &amp; Nego
                        </button>
                        <div v-else class="space-y-2">
                            <div>
                                <Label class="text-xs">Penawaran Awal (Rp)</Label>
                                <Input
                                    v-model="orderHarga"
                                    type="number"
                                    :placeholder="String(item.harga_awal)"
                                    class="mt-1"
                                />
                            </div>
                            <div>
                                <Label class="text-xs">Catatan (opsional)</Label>
                                <Input v-model="orderCatatan" type="text" placeholder="Catatan..." class="mt-1" />
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="submitOrder(item.id)"
                                    :disabled="!orderHarga"
                                    class="flex-1 rounded-lg bg-green-700 py-2 text-sm font-medium text-white hover:bg-green-800 disabled:opacity-50"
                                >
                                    Kirim Penawaran
                                </button>
                                <button
                                    @click="showOrderFormFor = null"
                                    class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
