<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BarChart from '@/components/charts/BarChart.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Stat } from '@/components/ui/stat';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

interface Stats {
    total_listing?: number;
    total_bahan_baku?: number;
    total_deal?: number;
    total_nilai_deal?: number;
    my_listing?: number;
    my_listing_tersedia?: number;
    my_listing_diambil?: number;
    my_listing_terjual?: number;
    my_claimed?: number;
    my_bahan_baku?: number;
    my_bahan_baku_tersedia?: number;
    my_pesanan_nego?: number;
    my_pesanan_deal?: number;
    my_pesanan_batal?: number;
    my_nilai_deal?: number;
    kg_recycled?: number;
    co2_saved_kg?: number;
    nilai_ekonomi?: number;
    volume_per_jenis?: Array<{ jenis: string; label: string; kg: number }>;
}

const props = defineProps<{ stats: Stats }>();

const page = usePage();
const user = computed(() => page.props.auth.user as { name: string; role?: string });
const role = computed(() => user.value?.role ?? '');

function formatRp(n?: number): string {
    return 'Rp ' + (n ?? 0).toLocaleString('id-ID');
}

const chartData = computed(() =>
    (props.stats.volume_per_jenis ?? []).map((v) => ({ label: v.label, value: v.kg })),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
        <!-- Greeting -->
        <div>
            <h1 class="text-2xl font-bold text-green-900">
                Selamat datang, {{ user.name }}
            </h1>
            <p class="text-sm text-muted-foreground mt-1 capitalize">
                Peran: <span class="font-medium text-green-700">{{ role.replace('_', ' ') }}</span>
            </p>
        </div>

        <!-- ── Dampak Lingkungan (semua peran) ── -->
        <div class="rounded-[14px] border border-hairline-gray p-6">
            <p class="mb-4 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                Dampak Lingkungan
            </p>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <Stat
                    :value="(stats.kg_recycled ?? 0).toLocaleString('id-ID') + ' kg'"
                    label="Material didaur ulang"
                />
                <Stat
                    :value="(stats.co2_saved_kg ?? 0).toLocaleString('id-ID') + ' kg'"
                    label="Estimasi CO₂ dihemat"
                />
                <Stat :value="formatRp(stats.nilai_ekonomi)" label="Nilai ekonomi tercipta" />
            </div>

            <div v-if="chartData.length" class="mt-8">
                <p class="mb-4 text-sm font-medium text-forest-ink">Volume per Jenis (kg)</p>
                <BarChart :data="chartData" unit="kg" />
            </div>
        </div>

        <!-- ── Rumah Tangga ── -->
        <template v-if="role === 'rumah_tangga'">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Total Listing</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-800">{{ stats.my_listing ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Tersedia</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-700">{{ stats.my_listing_tersedia ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Diambil</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-600">{{ stats.my_listing_diambil ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Terjual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-700">{{ stats.my_listing_terjual ?? 0 }}</div>
                    </CardContent>
                </Card>
            </div>
            <div class="flex gap-3 flex-wrap">
                <Link href="/rt" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                    Kelola Listing
                </Link>
                <Link href="/rt/baru" class="rounded-lg border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                    + Listing Baru
                </Link>
            </div>
        </template>

        <!-- ── Pengepul ── -->
        <template v-if="role === 'pengepul'">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Listing Diklaim</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-800">{{ stats.my_claimed ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Bahan Baku</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-700">{{ stats.my_bahan_baku ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Pesanan Masuk</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-600">{{ stats.my_pesanan_nego ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Deal</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-700">{{ stats.my_pesanan_deal ?? 0 }}</div>
                    </CardContent>
                </Card>
            </div>
            <div class="flex gap-3 flex-wrap">
                <Link href="/pengepul/jenis" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                    Jenis Ditangani
                </Link>
                <Link href="/pengepul/ketersediaan" class="rounded-lg border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                    Ketersediaan + Peta
                </Link>
                <Link href="/pengepul/bahan-baku" class="rounded-lg border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                    Bahan Baku
                </Link>
            </div>
        </template>

        <!-- ── Industri ── -->
        <template v-if="role === 'industri'">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Pesanan Aktif (Nego)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-amber-600">{{ stats.my_pesanan_nego ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Deal</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-700">{{ stats.my_pesanan_deal ?? 0 }}</div>
                    </CardContent>
                </Card>
                <Card class="border">
                    <CardHeader class="pb-1">
                        <CardTitle class="text-xs text-muted-foreground font-medium">Total Nilai Deal</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-lg font-bold text-green-800">{{ formatRp(stats.my_nilai_deal) }}</div>
                    </CardContent>
                </Card>
            </div>
            <div class="flex gap-3 flex-wrap">
                <Link href="/industri" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                    Cari Bahan Baku
                </Link>
                <Link href="/industri/bahan-jadi" class="rounded-lg border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50">
                    Bahan Baku Jadi
                </Link>
            </div>
        </template>

        <!-- Platform overview (all roles) -->
        <div class="mt-2 border-t pt-4">
            <p class="text-xs text-muted-foreground mb-3 font-medium uppercase tracking-wide">Ringkasan Platform</p>
            <div class="grid grid-cols-3 gap-4 max-w-lg">
                <div class="text-center">
                    <div class="text-xl font-bold text-green-800">{{ stats.total_listing ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Total Listing</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-green-800">{{ stats.total_bahan_baku ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Bahan Baku</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-green-800">{{ stats.total_deal ?? 0 }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Transaksi Deal</div>
                </div>
            </div>
        </div>
    </div>
</template>
