<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Item {
    id: number;
    nama: string;
    jenis_label: string;
    deskripsi?: string | null;
    berat: number;
    harga: number;
    image?: string | null;
    penjual: string;
    is_own: boolean;
}

defineProps<{ items: Item[] }>();

const page = usePage();
const authUser = page.props.auth as { user?: { name?: string; nama_pt?: string; alamat_pt?: string; role?: string } } | undefined;
const showPerusahaan = authUser?.user?.role === 'industri';

const placeholder =
    'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22500%22%20height%3D%22500%22%20viewBox%3D%220%200%20500%20500%22%3E%3Crect%20width%3D%22500%22%20height%3D%22500%22%20fill%3D%22%23e2e8f0%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3D%22sans-serif%22%20font-size%3D%2224%22%20fill%3D%22%2394a3b8%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';

const metodePembayaran = [
    { value: 'transfer', label: 'Transfer Bank', desc: 'BCA / Mandiri / BNI' },
    { value: 'va', label: 'Virtual Account', desc: 'Bayar via VA otomatis' },
    { value: 'ewallet', label: 'Dompet Digital', desc: 'GoPay / OVO / DANA' },
];

const checkoutItem = ref<Item | null>(null);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

const form = ref({
    nama_penerima: '',
    perusahaan: '',
    alamat: '',
    telepon: '',
    metode: 'transfer',
    catatan: '',
});

function formatRp(n: number): string {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function openCheckout(item: Item): void {
    checkoutItem.value = item;
    errors.value = {};
    form.value = {
        nama_penerima: authUser?.user?.name ?? '',
        perusahaan: authUser?.user?.nama_pt ?? '',
        alamat: authUser?.user?.alamat_pt ?? '',
        telepon: '',
        metode: 'transfer',
        catatan: '',
    };
}

function closeCheckout(): void {
    checkoutItem.value = null;
}

function validate(): boolean {
    const e: Record<string, string> = {};
    if (!form.value.nama_penerima) e.nama_penerima = 'Wajib diisi.';
    if (showPerusahaan && !form.value.perusahaan) e.perusahaan = 'Wajib diisi.';
    if (!form.value.alamat) e.alamat = 'Alamat pengiriman wajib diisi.';
    if (!form.value.telepon) e.telepon = 'No. telepon wajib diisi.';
    errors.value = e;
    return Object.keys(e).length === 0;
}

function bayar(): void {
    if (!checkoutItem.value || !validate()) {
        return;
    }
    processing.value = true;
    router.post(`/beli-bahan-jadi/${checkoutItem.value.id}/beli`, { ...form.value }, {
        preserveScroll: true,
        onSuccess: () => {
            checkoutItem.value = null;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Beli Bahan Baku Jadi" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div>
            <h1 class="text-2xl font-bold text-green-900">Bahan Baku Jadi</h1>
            <p class="mt-1 text-sm text-gray-500">Produk olahan industri siap pakai — beli dengan harga tetap.</p>
        </div>

        <div v-if="items.length === 0" class="flex flex-1 items-center justify-center text-gray-400">
            <p>Belum ada bahan baku jadi tersedia.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Card v-for="item in items" :key="item.id" class="overflow-hidden border shadow-sm">
                <div class="aspect-square bg-gray-100">
                    <img :src="item.image ?? placeholder" :alt="item.nama" class="h-full w-full object-cover" />
                </div>
                <CardHeader class="px-4 pt-4 pb-1">
                    <span class="w-fit rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                        {{ item.jenis_label }}
                    </span>
                </CardHeader>
                <CardContent class="px-4 pb-4">
                    <h3 class="line-clamp-2 text-sm font-semibold text-gray-800">{{ item.nama }}</h3>
                    <div class="mt-1 text-xl font-bold text-green-800">{{ formatRp(item.harga) }}</div>
                    <div class="text-sm text-gray-500">{{ item.berat }} kg</div>
                    <div class="text-xs text-gray-400">{{ item.penjual }}</div>

                    <span
                        v-if="item.is_own"
                        class="mt-3 block rounded-lg bg-gray-100 py-2 text-center text-xs font-medium text-gray-400"
                    >
                        Milik Anda
                    </span>
                    <button
                        v-else
                        @click="openCheckout(item)"
                        class="mt-3 w-full rounded-lg bg-green-700 py-2 text-sm font-semibold text-white hover:bg-green-800"
                    >
                        🛒 Beli Sekarang
                    </button>
                </CardContent>
            </Card>
        </div>

        <!-- Modal Checkout (simulasi) -->
        <div
            v-if="checkoutItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeCheckout"
        >
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="text-lg font-bold text-green-900">Checkout</h2>
                    <button @click="closeCheckout" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="space-y-5 px-5 py-4">
                    <!-- Ringkasan order -->
                    <div class="rounded-xl border bg-gray-50 p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ checkoutItem.nama }}</p>
                                <p class="text-xs text-gray-500">{{ checkoutItem.jenis_label }} · {{ checkoutItem.berat }} kg · {{ checkoutItem.penjual }}</p>
                            </div>
                            <span class="text-sm font-bold text-green-800">{{ formatRp(checkoutItem.harga) }}</span>
                        </div>
                    </div>

                    <!-- Data pengiriman -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Data Pengiriman</h3>
                        <div class="grid gap-3" :class="showPerusahaan ? 'grid-cols-2' : 'grid-cols-1'">
                            <div>
                                <Label class="text-xs">Nama Penerima</Label>
                                <Input v-model="form.nama_penerima" class="mt-1" />
                                <p v-if="errors.nama_penerima" class="text-xs text-red-500">{{ errors.nama_penerima }}</p>
                            </div>
                            <div v-if="showPerusahaan">
                                <Label class="text-xs">Perusahaan</Label>
                                <Input v-model="form.perusahaan" class="mt-1" />
                                <p v-if="errors.perusahaan" class="text-xs text-red-500">{{ errors.perusahaan }}</p>
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs">Alamat Pengiriman</Label>
                            <textarea
                                v-model="form.alamat"
                                rows="2"
                                class="mt-1 flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            <p v-if="errors.alamat" class="text-xs text-red-500">{{ errors.alamat }}</p>
                        </div>
                        <div>
                            <Label class="text-xs">No. Telepon</Label>
                            <Input v-model="form.telepon" type="tel" placeholder="08xxxxxxxxxx" class="mt-1" />
                            <p v-if="errors.telepon" class="text-xs text-red-500">{{ errors.telepon }}</p>
                        </div>
                    </div>

                    <!-- Metode pembayaran -->
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700">Metode Pembayaran</h3>
                        <label
                            v-for="m in metodePembayaran"
                            :key="m.value"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-2.5 transition-colors"
                            :class="form.metode === m.value ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input v-model="form.metode" type="radio" :value="m.value" class="accent-green-700" />
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ m.label }}</div>
                                <div class="text-xs text-gray-400">{{ m.desc }}</div>
                            </div>
                        </label>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <Label class="text-xs">Catatan (opsional)</Label>
                        <Input v-model="form.catatan" placeholder="Instruksi pengiriman…" class="mt-1" />
                    </div>

                    <!-- Total -->
                    <div class="space-y-1 border-t pt-3 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span><span>{{ formatRp(checkoutItem.harga) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Biaya Layanan</span><span>Rp 0</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-green-900">
                            <span>Total</span><span>{{ formatRp(checkoutItem.harga) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 border-t px-5 py-4">
                    <button
                        @click="closeCheckout"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50"
                    >
                        Batal
                    </button>
                    <button
                        @click="bayar"
                        :disabled="processing"
                        class="flex-1 rounded-lg bg-green-700 py-2 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-50"
                    >
                        {{ processing ? 'Memproses…' : `💳 Bayar ${formatRp(checkoutItem.harga)} (Simulasi)` }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
