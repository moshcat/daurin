<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface SourceListing {
    id: number;
    jenis_sampah: string;
    berat: number;
}

interface PesananItem {
    id: number;
    status: string;
}

interface LelangRef {
    id: number;
    status: string;
}

interface BahanBakuItem {
    id: number;
    jenis_sampah: string;
    peruntukan?: string;
    berat: number;
    harga_awal: number;
    status: string;
    source_listing?: SourceListing;
    pesanan?: PesananItem;
    lelang?: LelangRef | null;
}

interface ClaimedListing {
    id: number;
    jenis_sampah: string;
    berat: number;
    harga: number;
    user: { name: string };
}

interface JenisOption {
    value: string;
    label: string;
}

const props = defineProps<{
    bahanBaku: BahanBakuItem[];
    claimedListings: ClaimedListing[];
    jenisSampahOptions: JenisOption[];
}>();

const showFormForListing = ref<number | null>(null);
const form = ref({ jenis_sampah: '', peruntukan: '', berat: '', harga_awal: '' });
const errors = ref<Record<string, string>>({});

const showLelangFor = ref<number | null>(null);
const lelangForm = ref({ harga_awal: '', kelipatan: '1000', durasi_menit: '60', harga_buyout: '' });
const lelangErrors = ref<Record<string, string>>({});

const statusClass: Record<string, string> = {
    tersedia: 'bg-green-100 text-green-700',
    dilelang: 'bg-amber-100 text-amber-700',
    terjual: 'bg-gray-100 text-gray-500',
};

function openLelang(item: BahanBakuItem): void {
    showLelangFor.value = item.id;
    lelangForm.value = { harga_awal: String(item.harga_awal), kelipatan: '1000', durasi_menit: '60', harga_buyout: '' };
    lelangErrors.value = {};
}

function startLelang(itemId: number): void {
    const payload: Record<string, string> = {
        harga_awal: lelangForm.value.harga_awal,
        kelipatan: lelangForm.value.kelipatan,
        durasi_menit: lelangForm.value.durasi_menit,
    };
    if (lelangForm.value.harga_buyout) {
        payload.harga_buyout = lelangForm.value.harga_buyout;
    }
    router.post(`/pengepul/lelang/${itemId}`, payload, {
        onError: (e) => {
            lelangErrors.value = e;
        },
    });
}

function jenisLabel(v: string): string {
    return props.jenisSampahOptions.find((o) => o.value === v)?.label ?? v;
}

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function openForm(listing: ClaimedListing): void {
    showFormForListing.value = listing.id;
    form.value = {
        jenis_sampah: listing.jenis_sampah,
        peruntukan: '',
        berat: String(listing.berat),
        harga_awal: String(listing.harga),
    };
    errors.value = {};
}

function submitBahanBaku(listingId: number): void {
    router.post(`/pengepul/bahan-baku/${listingId}`, { ...form.value }, {
        onSuccess: () => {
            showFormForListing.value = null;
        },
        onError: (e) => {
            errors.value = e;
        },
    });
}
</script>

<template>
    <Head title="Bahan Baku" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <h1 class="text-2xl font-bold text-green-900">Bahan Baku</h1>

        <!-- Claimed listings to process -->
        <section v-if="claimedListings.length > 0">
            <h2 class="mb-3 text-base font-semibold text-amber-700">
                ⏳ Listing Diklaim — Perlu Diproses
            </h2>
            <div class="flex flex-col gap-3">
                <div
                    v-for="listing in claimedListings"
                    :key="listing.id"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="font-semibold text-sm">{{ jenisLabel(listing.jenis_sampah) }}</span>
                            <span class="ml-2 text-sm text-gray-500">
                                {{ listing.berat }} kg · {{ listing.user.name }}
                            </span>
                        </div>
                        <button
                            @click="openForm(listing)"
                            class="shrink-0 rounded-lg bg-green-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-800"
                        >
                            Pilah → Bahan Baku
                        </button>
                    </div>

                    <!-- Inline form -->
                    <div
                        v-if="showFormForListing === listing.id"
                        class="mt-4 grid grid-cols-2 gap-3 border-t border-amber-200 pt-4"
                    >
                        <div>
                            <Label class="text-xs">Jenis Bahan Baku</Label>
                            <select
                                v-model="form.jenis_sampah"
                                class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option v-for="opt in jenisSampahOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <Label class="text-xs">Peruntukan</Label>
                            <Input v-model="form.peruntukan" placeholder="Cth: daur ulang botol" class="mt-1" />
                        </div>
                        <div>
                            <Label class="text-xs">Berat (kg)</Label>
                            <Input v-model="form.berat" type="number" min="0.1" step="0.1" :max="listing.berat" class="mt-1" />
                            <p class="text-[11px] text-gray-400">Maksimal {{ listing.berat }} kg — tidak boleh melebihi berat listing asal.</p>
                            <p v-if="errors.berat" class="text-xs text-red-500">{{ errors.berat }}</p>
                        </div>
                        <div>
                            <Label class="text-xs">Harga Awal (Rp)</Label>
                            <Input v-model="form.harga_awal" type="number" class="mt-1" />
                            <p v-if="errors.harga_awal" class="text-xs text-red-500">{{ errors.harga_awal }}</p>
                        </div>
                        <div class="col-span-2 flex gap-2">
                            <button
                                @click="submitBahanBaku(listing.id)"
                                class="rounded-lg bg-green-700 px-4 py-1.5 text-sm font-medium text-white hover:bg-green-800"
                            >
                                Simpan
                            </button>
                            <button
                                @click="showFormForListing = null"
                                class="rounded-lg border px-4 py-1.5 text-sm hover:bg-gray-50"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bahan Baku list -->
        <section>
            <h2 class="mb-3 text-base font-semibold text-gray-700">Bahan Baku Saya</h2>
            <div v-if="bahanBaku.length === 0" class="text-sm text-gray-400">
                Belum ada bahan baku. Klaim listing terlebih dahulu.
            </div>
            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="item in bahanBaku" :key="item.id" class="border shadow-sm">
                    <CardHeader class="px-4 pt-4 pb-2">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-sm">{{ jenisLabel(item.jenis_sampah) }}</span>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusClass[item.status] ?? 'bg-gray-100 text-gray-500'"
                            >
                                {{ item.status }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="px-4 pb-4">
                        <div class="text-xl font-bold text-green-800">{{ formatRp(item.harga_awal) }}</div>
                        <div class="text-sm text-gray-500">{{ item.berat }} kg</div>
                        <div v-if="item.peruntukan" class="text-xs text-gray-400">{{ item.peruntukan }}</div>
                        <div v-if="item.source_listing" class="mt-1 text-xs text-blue-600">
                            🔗 Dari: {{ jenisLabel(item.source_listing.jenis_sampah) }} ({{ item.source_listing.berat }} kg)
                        </div>
                        <div v-if="item.pesanan" class="mt-1 text-xs">
                            📦 Pesanan:
                            <span class="font-medium">{{ item.pesanan.status }}</span>
                            <a
                                :href="`/pengepul/pesanan/${item.pesanan.id}`"
                                class="ml-1 text-green-700 hover:underline"
                            >
                                Lihat →
                            </a>
                        </div>

                        <!-- Lelang -->
                        <div class="mt-3 border-t pt-3">
                            <a
                                v-if="item.lelang"
                                :href="`/lelang/${item.lelang.id}`"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-amber-500 py-2 text-sm font-medium text-white hover:bg-amber-600"
                            >
                                💬 Lihat Negosiasi →
                            </a>
                            <template v-else-if="item.status === 'tersedia'">
                                <button
                                    v-if="showLelangFor !== item.id"
                                    @click="openLelang(item)"
                                    class="w-full rounded-lg bg-amber-500 py-2 text-sm font-medium text-white hover:bg-amber-600"
                                >
                                    💬 Mulai Negosiasi
                                </button>
                                <div v-else class="space-y-2">
                                    <div>
                                        <Label class="text-xs">Harga Awal (Rp)</Label>
                                        <Input v-model="lelangForm.harga_awal" type="number" class="mt-1" />
                                        <p v-if="lelangErrors.harga_awal" class="text-xs text-red-500">{{ lelangErrors.harga_awal }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <Label class="text-xs">Kelipatan</Label>
                                            <Input v-model="lelangForm.kelipatan" type="number" class="mt-1" />
                                        </div>
                                        <div>
                                            <Label class="text-xs">Durasi (menit)</Label>
                                            <Input v-model="lelangForm.durasi_menit" type="number" class="mt-1" />
                                        </div>
                                    </div>
                                    <div>
                                        <Label class="text-xs">Beli Langsung (opsional)</Label>
                                        <Input v-model="lelangForm.harga_buyout" type="number" placeholder="kosongkan jika tidak ada" class="mt-1" />
                                        <p v-if="lelangErrors.harga_buyout" class="text-xs text-red-500">{{ lelangErrors.harga_buyout }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            @click="startLelang(item.id)"
                                            class="flex-1 rounded-lg bg-amber-600 py-1.5 text-sm font-medium text-white hover:bg-amber-700"
                                        >
                                            Buka Negosiasi
                                        </button>
                                        <button
                                            @click="showLelangFor = null"
                                            class="rounded-lg border px-3 py-1.5 text-sm hover:bg-gray-50"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </div>
</template>
