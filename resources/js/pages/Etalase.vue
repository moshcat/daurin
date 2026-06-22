<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ProductCard from '@/components/ProductCard.vue'
import { productFromBahanBaku, productFromBahanJadi, productFromListing } from '@/lib/listing'
import type { BahanBakuItem, BahanJadiItem, ListingItem, MarketProduct } from '@/lib/listing'
import { dashboard, login } from '@/routes'
import { store as registerRoute } from '@/routes/register'

interface JenisOption {
    value: string
    label: string
}

const props = defineProps<{
    listings: ListingItem[]
    bahanBaku: BahanBakuItem[]
    bahanJadi: BahanJadiItem[]
    jenisSampahOptions: JenisOption[]
}>()

const page = usePage()
const authUser = computed(() => (page.props.auth as { user?: { role?: string } } | undefined)?.user ?? null)
const isAuth = computed(() => Boolean(authUser.value))

const allProducts = computed<MarketProduct[]>(() => [
    ...props.bahanBaku.map(productFromBahanBaku),
    ...props.listings.map(productFromListing),
    ...props.bahanJadi.map(productFromBahanJadi),
])

const tabs = [
    { value: 'all', label: 'Semua' },
    { value: 'bahan_baku', label: 'Bahan Baku (Negosiasi)' },
    { value: 'sampah', label: 'Sampah Terpilah' },
    { value: 'bahan_jadi', label: 'Bahan Jadi' },
]
const activeLayer = ref('all')
const activeJenis = ref('')
const search = ref('')

const filtered = computed(() =>
    allProducts.value.filter((p) => {
        if (activeLayer.value !== 'all' && p.layer !== activeLayer.value) {
            return false
        }
        if (activeJenis.value && p.jenis !== activeJenis.value) {
            return false
        }
        if (search.value && !p.title.toLowerCase().includes(search.value.toLowerCase())) {
            return false
        }

        return true
    }),
)

</script>

<template>
    <Head title="Etalase — Daurin">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="et">
        <!-- Header -->
        <header class="et-header">
            <div class="et-container et-nav">
                <Link href="/" class="brand">
                    <img src="/logo.png" alt="Daurin" style="height: 32px; width: auto;" />
                    <span class="brand-name">Daurin</span>
                </Link>
                <div class="nav-right">
                    <a href="/" class="btn-text">Beranda</a>
                    <template v-if="isAuth">
                        <Link :href="dashboard()" class="btn btn-primary">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="btn-text">Masuk</Link>
                        <Link :href="registerRoute.url()" class="btn btn-primary">Daftar</Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="et-container et-main">
            <h1 class="et-title">Etalase Daur Ulang</h1>
            <p class="et-sub">Telusuri sampah terpilah, bahan baku, dan bahan jadi — saring sesuai kebutuhan.</p>

            <!-- Filter -->
            <div class="filters">
                <div class="tabs">
                    <button
                        v-for="t in tabs"
                        :key="t.value"
                        class="tab"
                        :class="{ active: activeLayer === t.value }"
                        @click="activeLayer = t.value"
                    >
                        {{ t.label }}
                    </button>
                </div>
                <div class="filter-right">
                    <select v-model="activeJenis" class="select">
                        <option value="">Semua jenis</option>
                        <option v-for="o in jenisSampahOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                    <input v-model="search" type="text" class="search" placeholder="Cari produk…" />
                </div>
            </div>

            <p class="count">{{ filtered.length }} produk</p>

            <div v-if="filtered.length === 0" class="empty">Tidak ada produk yang cocok dengan filter.</div>

            <div v-else class="grid">
                <ProductCard
                    v-for="product in filtered"
                    :key="`${product.layer}-${product.id}`"
                    :product="product"
                />
            </div>
        </main>
    </div>
</template>

<style scoped>
.et {
    --green: #16a34a;
    --green-dark: #15803d;
    --green-soft: #dcfce7;
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e5e7eb;
    --bg: #ffffff;

    min-height: 100vh;
    background: #f8fafc;
    color: var(--ink);
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.et * {
    box-sizing: border-box;
}

.et-container {
    width: 100%;
    max-width: 1560px;
    margin: 0 auto;
    padding: 0 24px;
}

@media (min-width: 1024px) {
    .et-container {
        padding: 0 56px;
    }
}

/* Header */
.et-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: #fff;
    border-bottom: 1px solid var(--line);
}

.et-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 76px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--ink);
    font-weight: 800;
    font-size: 20px;
}

.brand-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--green);
    color: #fff;
    font-size: 18px;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 14px;
}

.btn-text {
    color: var(--ink);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    font-weight: 700;
    text-decoration: none;
    border: none;
    cursor: pointer;
    padding: 10px 20px;
    font-size: 14px;
}

.btn-primary {
    background: var(--green);
    color: #fff !important;
}

.btn-primary:hover {
    background: var(--green-dark);
}

.btn-sm {
    padding: 8px 14px;
    font-size: 13px;
}

/* Main */
.et-main {
    padding-top: 32px;
    padding-bottom: 64px;
}

.et-title {
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0;
}

.et-sub {
    color: var(--muted);
    margin: 8px 0 24px;
}

/* Filter */
.filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

.tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tab {
    padding: 9px 16px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--muted);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
}

.tab.active {
    background: var(--green);
    border-color: var(--green);
    color: #fff;
}

.filter-right {
    display: flex;
    gap: 10px;
}

.select,
.search {
    height: 40px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
    padding: 0 14px;
    font-size: 14px;
    outline: none;
    font-family: inherit;
}

.select:focus,
.search:focus {
    border-color: var(--green);
}

.count {
    color: var(--muted);
    font-size: 13px;
    margin: 0 0 16px;
}

.empty {
    padding: 60px;
    text-align: center;
    color: var(--muted);
}

/* Grid + card */
.grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 22px;
}

@media (min-width: 640px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1400px) {
    .grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 640px) {
    .filters {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-right {
        flex-direction: column;
    }

    .select,
    .search {
        width: 100%;
    }
}
</style>
