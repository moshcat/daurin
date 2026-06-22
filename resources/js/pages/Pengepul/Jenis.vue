<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

interface JenisItem {
    id: number;
    jenis_sampah: string;
}

interface JenisOption {
    value: string;
    label: string;
}

const props = defineProps<{
    jenisList: JenisItem[];
    allJenis: JenisOption[];
}>();

const selected = ref('');

const jenisBadgeClass: Record<string, string> = {
    plastik_pet: 'bg-blue-100 text-blue-800 border-blue-200',
    plastik_hdpe: 'bg-blue-200 text-blue-900 border-blue-300',
    kertas: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    kardus: 'bg-amber-100 text-amber-800 border-amber-200',
    logam: 'bg-gray-200 text-gray-800 border-gray-300',
    kaleng: 'bg-gray-100 text-gray-700 border-gray-200',
    kaca: 'bg-cyan-100 text-cyan-800 border-cyan-200',
    elektronik: 'bg-purple-100 text-purple-800 border-purple-200',
};

function addJenis(): void {
    if (!selected.value) return;
    router.post(
        '/pengepul/jenis',
        { jenis_sampah: selected.value },
        { onSuccess: () => { selected.value = ''; } },
    );
}

function removeJenis(id: number): void {
    router.delete(`/pengepul/jenis/${id}`);
}

function labelFor(value: string): string {
    return props.allJenis.find((a) => a.value === value)?.label ?? value;
}

function isAlreadyAdded(value: string): boolean {
    return props.jenisList.some((j) => j.jenis_sampah === value);
}
</script>

<template>
    <Head title="Jenis Sampah Ditangani" />

    <div class="flex h-full flex-1 flex-col gap-4 p-6">
        <div>
            <h1 class="text-2xl font-bold text-green-900">Jenis Sampah Ditangani</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Daftarkan jenis sampah yang Anda tangani agar hanya listing relevan yang muncul.
            </p>
        </div>

        <!-- Add form -->
        <Card class="max-w-md border shadow-sm">
            <CardContent class="pt-5">
                <div class="flex gap-3">
                    <select
                        v-model="selected"
                        class="flex h-9 flex-1 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">Pilih jenis sampah...</option>
                        <option v-for="opt in allJenis" :key="opt.value" :value="opt.value" :disabled="isAlreadyAdded(opt.value)">
                            {{ opt.label }}{{ isAlreadyAdded(opt.value) ? ' ✓' : '' }}
                        </option>
                    </select>
                    <button
                        @click="addJenis"
                        :disabled="!selected || isAlreadyAdded(selected)"
                        class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 disabled:opacity-50"
                    >
                        Tambah
                    </button>
                </div>
            </CardContent>
        </Card>

        <!-- Existing list -->
        <div v-if="jenisList.length === 0" class="text-sm text-gray-400">
            Belum ada jenis yang didaftarkan.
        </div>
        <div v-else class="flex flex-wrap gap-3">
            <div
                v-for="item in jenisList"
                :key="item.id"
                class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium"
                :class="jenisBadgeClass[item.jenis_sampah] ?? 'bg-gray-100 text-gray-700 border-gray-200'"
            >
                <span>{{ labelFor(item.jenis_sampah) }}</span>
                <button
                    @click="removeJenis(item.id)"
                    class="ml-1 opacity-60 hover:opacity-100 text-xs leading-none"
                    title="Hapus"
                >
                    ✕
                </button>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <a href="/pengepul/ketersediaan" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                → Lihat Ketersediaan
            </a>
        </div>
    </div>
</template>
