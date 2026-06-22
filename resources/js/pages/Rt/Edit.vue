<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface JenisOption {
    value: string;
    label: string;
}

interface Listing {
    id: number;
    jenis_sampah: string;
    berat: number | string;
    harga: number | string;
    foto_path?: string | null;
    ai_label?: string | null;
    ai_confidence?: number | null;
    lat?: number | null;
    lng?: number | null;
}

const props = defineProps<{
    listing: Listing;
    jenisSampahOptions: JenisOption[];
}>();

const jenis = ref(props.listing.jenis_sampah);
const berat = ref(String(props.listing.berat));
const harga = ref(String(props.listing.harga));
const foto = ref<File | null>(null);
const aiLabel = ref(props.listing.ai_label ?? '');
const aiConfidence = ref(
    props.listing.ai_confidence != null
        ? String(props.listing.ai_confidence)
        : '',
);
const lat = ref(props.listing.lat != null ? String(props.listing.lat) : '');
const lng = ref(props.listing.lng != null ? String(props.listing.lng) : '');
const locationText = ref('');
const classifying = ref(false);

interface ClassifyResult {
    label: string;
    confidence: number;
    jenis_sampah: string | null;
    emoji: string;
    tip: string;
    supported: boolean;
}

const classifyResult = ref<ClassifyResult | null>(null);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

function onFileChange(e: Event): void {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (file) {
        foto.value = file;
        classifyResult.value = null;
    }
}

async function classify(): Promise<void> {
    if (!foto.value) {
return;
}

    classifying.value = true;

    try {
        const fd = new FormData();
        fd.append('file', foto.value);
        const metaToken = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        );

        if (metaToken) {
            fd.append('_token', metaToken.content);
        }

        const res = await fetch('/classify', { method: 'POST', body: fd });
        const data = (await res.json()) as ClassifyResult;
        classifyResult.value = data;
        aiLabel.value = data.label;
        aiConfidence.value = String(data.confidence);

        if (data.supported && data.jenis_sampah) {
            jenis.value = data.jenis_sampah;
        }
    } catch {
        classifyResult.value = null;
    } finally {
        classifying.value = false;
    }
}

function getLocation(): void {
    if (!navigator.geolocation) {
return;
}

    locationText.value = 'Mengambil lokasi...';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            lat.value = String(pos.coords.latitude);
            lng.value = String(pos.coords.longitude);
            locationText.value = `${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`;
        },
        () => {
            locationText.value = 'Gagal mengambil lokasi.';
        },
    );
}

function submit(): void {
    errors.value = {};
    const fd = new FormData();
    // Inertia/Laravel method spoofing — PUT via multipart form.
    fd.append('_method', 'put');
    fd.append('jenis_sampah', jenis.value);
    fd.append('berat', berat.value);
    fd.append('harga', harga.value);

    if (foto.value) {
fd.append('foto', foto.value);
}

    if (aiLabel.value) {
fd.append('ai_label', aiLabel.value);
}

    if (aiConfidence.value) {
fd.append('ai_confidence', aiConfidence.value);
}

    if (lat.value) {
fd.append('lat', lat.value);
}

    if (lng.value) {
fd.append('lng', lng.value);
}

    submitting.value = true;
    router.post(`/rt/${props.listing.id}`, fd, {
        forceFormData: true,
        onError: (e) => {
            errors.value = e;
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Ubah Listing Sampah" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div class="mb-2">
            <a href="/rt" class="text-sm text-green-700 hover:underline"
                >← Kembali</a
            >
            <h1 class="mt-2 text-2xl font-bold text-green-900">
                Ubah Listing Sampah
            </h1>
        </div>

        <Card class="max-w-xl border shadow-sm">
            <CardContent class="space-y-5 pt-6">
                <!-- Foto saat ini + ganti + AI -->
                <div class="space-y-2">
                    <Label>Foto Sampah</Label>
                    <img
                        v-if="listing.foto_path"
                        :src="`/storage/${listing.foto_path}`"
                        alt="Foto sampah"
                        class="h-32 w-32 rounded-lg border object-cover"
                    />
                    <input
                        type="file"
                        accept="image/*"
                        @change="onFileChange"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:rounded file:border file:border-gray-300 file:bg-white file:px-3 file:py-1.5 file:text-sm hover:file:bg-gray-50"
                    />
                    <p class="text-xs text-gray-400">
                        Kosongkan jika tidak ingin mengganti foto.
                    </p>
                    <div v-if="foto" class="space-y-2">
                        <button
                            type="button"
                            @click="classify"
                            :disabled="classifying"
                            class="rounded bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{
                                classifying
                                    ? '🤖 Menganalisis...'
                                    : '🤖 Klasifikasi AI'
                            }}
                        </button>
                        <div
                            v-if="classifyResult"
                            class="space-y-1 rounded-lg border p-3 text-sm"
                            :class="
                                classifyResult.supported
                                    ? 'border-green-200 bg-green-50'
                                    : 'border-amber-200 bg-amber-50'
                            "
                        >
                            <div
                                class="font-semibold"
                                :class="
                                    classifyResult.supported
                                        ? 'text-green-800'
                                        : 'text-amber-800'
                                "
                            >
                                {{ classifyResult.emoji }}
                                {{ classifyResult.label }}
                                <span
                                    class="ml-1 text-xs font-normal opacity-70"
                                >
                                    ({{
                                        Math.round(
                                            classifyResult.confidence * 100,
                                        )
                                    }}% keyakinan)
                                </span>
                            </div>
                            <div
                                v-if="classifyResult.tip"
                                class="text-xs text-gray-600"
                            >
                                💡 {{ classifyResult.tip }}
                            </div>
                            <div
                                v-if="!classifyResult.supported"
                                class="text-xs font-medium text-amber-700"
                            >
                                ⚠️ Jenis ini belum ada di katalog — pilih jenis
                                manual di bawah.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jenis Sampah -->
                <div class="space-y-1">
                    <Label for="jenis_sampah">Jenis Sampah *</Label>
                    <select
                        id="jenis_sampah"
                        v-model="jenis"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Pilih jenis...</option>
                        <option
                            v-for="opt in jenisSampahOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <p v-if="errors.jenis_sampah" class="text-xs text-red-500">
                        {{ errors.jenis_sampah }}
                    </p>
                </div>

                <!-- Berat -->
                <div class="space-y-1">
                    <Label for="berat">Berat (kg) *</Label>
                    <Input
                        id="berat"
                        type="number"
                        min="1"
                        step="0.1"
                        v-model="berat"
                        placeholder="Contoh: 2.5"
                    />
                    <p class="text-xs text-gray-400">
                        Minimal 1 kg untuk dijual.
                    </p>
                    <p v-if="errors.berat" class="text-xs text-red-500">
                        {{ errors.berat }}
                    </p>
                </div>

                <!-- Harga -->
                <div class="space-y-1">
                    <Label for="harga">Harga (Rp) *</Label>
                    <Input
                        id="harga"
                        type="number"
                        min="0"
                        v-model="harga"
                        placeholder="Contoh: 5000"
                    />
                    <p v-if="errors.harga" class="text-xs text-red-500">
                        {{ errors.harga }}
                    </p>
                </div>

                <!-- Lokasi -->
                <div class="space-y-1">
                    <Label>Lokasi Pengambilan (opsional)</Label>
                    <div class="flex gap-2">
                        <Input
                            type="text"
                            :value="
                                locationText || (lat ? `${lat}, ${lng}` : '')
                            "
                            placeholder="Belum diambil"
                            readonly
                            class="flex-1"
                        />
                        <button
                            type="button"
                            @click="getLocation"
                            class="shrink-0 rounded border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50"
                        >
                            📍 Ambil
                        </button>
                    </div>
                </div>

                <Button
                    type="button"
                    @click="submit"
                    :disabled="submitting || !jenis || !berat || !harga"
                    class="w-full bg-green-700 hover:bg-green-800"
                >
                    {{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
