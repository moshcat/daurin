<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import MarketCard from '@/components/MarketCard.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {  jenisLabel, productFromBahanJadi } from '@/lib/listing'
import type {BahanJadiItem} from '@/lib/listing';

interface DealItem {
    id: number
    harga_sepakat: number | string
    bahan_baku?: {
        jenis_sampah: string
        berat: number | string
        peruntukan?: string | null
    } | null
}

interface JenisOption {
    value: string
    label: string
}

const props = defineProps<{
    deals: DealItem[]
    bahanJadi: BahanJadiItem[]
    jenisSampahOptions: JenisOption[]
}>()

const openFor = ref<number | null>(null)
const form = ref({
    nama: '',
    jenis_sampah: '',
    deskripsi: '',
    berat: '',
    harga: '',
    foto: null as File | null,
})

function formatRp(n: number | string): string {
    return 'Rp ' + Number(n).toLocaleString('id-ID')
}

function openForm(deal: DealItem): void {
    openFor.value = deal.id
    form.value = {
        nama: '',
        jenis_sampah: deal.bahan_baku?.jenis_sampah ?? '',
        deskripsi: '',
        berat: deal.bahan_baku ? String(deal.bahan_baku.berat) : '',
        harga: '',
        foto: null,
    }
}

function onFile(e: Event): void {
    const target = e.target as HTMLInputElement
    form.value.foto = target.files?.[0] ?? null
}

function submit(dealId: number): void {
    router.post(
        '/industri/bahan-jadi',
        { source_pesanan_id: dealId, ...form.value },
        {
            forceFormData: true,
            onSuccess: () => {
                openFor.value = null
            },
        },
    )
}

const products = props.bahanJadi.map(productFromBahanJadi)
</script>

<template>
    <Head title="Bahan Baku Jadi" />

    <div class="flex h-full flex-1 flex-col gap-8 p-6">
        <div>
            <h1 class="font-serif text-heading-sm font-light text-forest-ink">Bahan Baku Jadi</h1>
            <p class="mt-1 text-body-sm text-muted-foreground">
                Olah pesanan yang sudah deal menjadi produk daur ulang siap jual.
            </p>
        </div>

        <!-- Deals ready to process -->
        <section>
            <h2 class="mb-3 text-sm font-medium text-forest-ink">Pesanan siap diolah ({{ deals.length }})</h2>

            <div v-if="deals.length === 0" class="rounded-[14px] border border-hairline-gray py-10 text-center text-muted-foreground">
                Belum ada pesanan berstatus deal untuk diolah.
            </div>

            <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div
                    v-for="deal in deals"
                    :key="deal.id"
                    class="rounded-[14px] border border-hairline-gray p-[21px]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm font-normal text-forest-ink">
                            {{ deal.bahan_baku ? jenisLabel(deal.bahan_baku.jenis_sampah) : 'Bahan baku' }}
                        </span>
                        <span class="text-caption text-charcoal">Deal {{ formatRp(deal.harga_sepakat) }}</span>
                    </div>
                    <p v-if="deal.bahan_baku" class="mt-1 text-caption text-muted-foreground">
                        {{ Number(deal.bahan_baku.berat) }} kg
                        <template v-if="deal.bahan_baku.peruntukan"> · {{ deal.bahan_baku.peruntukan }}</template>
                    </p>

                    <div class="mt-4">
                        <button
                            v-if="openFor !== deal.id"
                            @click="openForm(deal)"
                            class="w-full rounded-[14px] bg-forest-ink py-2 text-body-sm font-normal text-linen-white hover:bg-forest-ink/90"
                        >
                            Olah jadi bahan baku jadi
                        </button>

                        <div v-else class="space-y-3">
                            <div>
                                <Label class="text-xs">Nama produk</Label>
                                <Input v-model="form.nama" placeholder="mis. Pelet PET daur ulang" class="mt-1" />
                            </div>
                            <div>
                                <Label class="text-xs">Jenis bahan</Label>
                                <select
                                    v-model="form.jenis_sampah"
                                    class="mt-1 flex h-9 w-full rounded-[7px] border border-input bg-transparent px-3 py-1 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option v-for="opt in jenisSampahOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label class="text-xs">Berat (kg)</Label>
                                    <Input v-model="form.berat" type="number" step="0.01" class="mt-1" />
                                </div>
                                <div>
                                    <Label class="text-xs">Harga (Rp)</Label>
                                    <Input v-model="form.harga" type="number" class="mt-1" />
                                </div>
                            </div>
                            <div>
                                <Label class="text-xs">Deskripsi (opsional)</Label>
                                <Input v-model="form.deskripsi" placeholder="Catatan produk..." class="mt-1" />
                            </div>
                            <div>
                                <Label class="text-xs">Foto (opsional)</Label>
                                <input type="file" accept="image/*" @change="onFile" class="mt-1 block w-full text-xs" />
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="submit(deal.id)"
                                    :disabled="!form.nama || !form.berat || !form.harga"
                                    class="flex-1 rounded-[14px] bg-forest-ink py-2 text-body-sm font-normal text-linen-white hover:bg-forest-ink/90 disabled:opacity-50"
                                >
                                    Simpan
                                </button>
                                <button
                                    @click="openFor = null"
                                    class="rounded-[14px] border border-hairline-gray px-3 py-2 text-body-sm hover:bg-linen"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Own finished materials -->
        <section>
            <h2 class="mb-3 text-sm font-medium text-forest-ink">Produk jadi saya ({{ bahanJadi.length }})</h2>

            <div v-if="products.length === 0" class="rounded-[14px] border border-hairline-gray py-10 text-center text-muted-foreground">
                Belum ada bahan baku jadi.
            </div>

            <div v-else class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                <MarketCard v-for="p in products" :key="p.id" :product="p" />
            </div>
        </section>
    </div>
</template>
