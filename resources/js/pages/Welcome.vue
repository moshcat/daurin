<script setup lang="ts">
import { Head, Link, WhenVisible } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLogo from '@/components/AppLogo.vue'
import MarketCard from '@/components/MarketCard.vue'
import { Button } from '@/components/ui/button'
import { PillBadge } from '@/components/ui/pill-badge'
import { SectionHeading } from '@/components/ui/section-heading'
import { Skeleton } from '@/components/ui/skeleton'
import {
    
    
    
    productFromBahanBaku,
    productFromBahanJadi,
    productFromListing
} from '@/lib/listing'
import type {BahanBakuItem, BahanJadiItem, ListingItem} from '@/lib/listing';
import { dashboard, login } from '@/routes'
import { store as registerRoute } from '@/routes/register'

const props = defineProps<{
    listings: ListingItem[]
    bahanBaku: BahanBakuItem[]
    bahanJadi: BahanJadiItem[]
    pagination: { page: number; hasMore: boolean }
    stats: { listingCount: number; bahanBakuCount: number; bahanJadiCount: number }
}>()

type Tab = 'sampah' | 'bahan_baku' | 'bahan_jadi'
const activeTab = ref<Tab>('sampah')

const sampahProducts = computed(() => props.listings.map(productFromListing))
const bahanBakuProducts = computed(() => props.bahanBaku.map(productFromBahanBaku))
const bahanJadiProducts = computed(() => props.bahanJadi.map(productFromBahanJadi))

const tabs = computed<Array<{ id: Tab; label: string; count: number }>>(() => [
    { id: 'sampah', label: 'Sampah RT', count: props.stats.listingCount },
    { id: 'bahan_baku', label: 'Bahan Baku', count: props.stats.bahanBakuCount },
    { id: 'bahan_jadi', label: 'Bahan Baku Jadi', count: props.stats.bahanJadiCount },
])

const gridClass = 'grid grid-cols-2 gap-x-[16px] gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5'
</script>

<template>
    <Head title="Daurin — Marketplace Daur Ulang" />

    <div class="min-h-screen bg-background font-sans text-foreground">
        <!-- Navbar -->
        <nav class="sticky top-0 z-20 border-b border-hairline-gray bg-linen-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-[1200px] items-center justify-between px-[24px] py-[14px]">
                <AppLogo />
                <div class="flex items-center gap-[14px]">
                    <Button v-if="$page.props.auth.user" as-child size="sm">
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                    <template v-else>
                        <Link :href="login()" class="text-body-sm font-normal text-forest-ink hover:underline">
                            Masuk
                        </Link>
                        <Button as-child size="sm">
                            <Link :href="registerRoute.url()">Daftar</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero (ringkas) -->
        <section class="bg-linen px-[24px] py-[70px]">
            <div class="mx-auto flex max-w-[1200px] flex-col items-center gap-[21px] text-center">
                <PillBadge>Platform Daur Ulang Terintegrasi</PillBadge>
                <h1
                    class="max-w-[820px] font-serif text-heading font-light text-forest-ink sm:text-heading-lg"
                    style="letter-spacing: -1.2px; line-height: 1.05"
                >
                    Sampah Rumah Tangga Jadi Nilai Ekonomi
                </h1>
                <p class="max-w-[560px] text-body font-normal text-charcoal">
                    Daurin menghubungkan Rumah Tangga, Pengepul, dan Industri Pengolah dalam satu
                    platform daur ulang 3-lapis yang terlacak.
                </p>
                <div class="mt-[7px] flex flex-wrap justify-center gap-[14px]">
                    <Button as-child size="lg">
                        <Link :href="registerRoute.url()">Mulai Sekarang</Link>
                    </Button>
                    <Button as-child variant="outline" size="lg">
                        <a href="#etalase">Lihat Etalase</a>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Stats ringkas (3 lapis) -->
        <section class="mx-auto grid max-w-[1200px] grid-cols-3 gap-[14px] px-[24px] py-[42px] text-center">
            <div class="rounded-[14px] border border-hairline-gray py-[28px]">
                <div class="font-sans text-heading font-light text-forest-ink">{{ stats.listingCount }}</div>
                <div class="mt-[4px] text-caption font-normal text-charcoal">Sampah RT</div>
            </div>
            <div class="rounded-[14px] border border-hairline-gray py-[28px]">
                <div class="font-sans text-heading font-light text-forest-ink">{{ stats.bahanBakuCount }}</div>
                <div class="mt-[4px] text-caption font-normal text-charcoal">Bahan Baku</div>
            </div>
            <div class="rounded-[14px] border border-hairline-gray py-[28px]">
                <div class="font-sans text-heading font-light text-forest-ink">{{ stats.bahanJadiCount }}</div>
                <div class="mt-[4px] text-caption font-normal text-charcoal">Bahan Baku Jadi</div>
            </div>
        </section>

        <!-- Etalase 3-lapis -->
        <section id="etalase" class="mx-auto max-w-[1200px] px-[24px] pb-[80px]">
            <SectionHeading label="Etalase 3-Lapis" class="mb-[28px]">
                Rantai daur ulang yang terlacak
            </SectionHeading>

            <!-- Tabs -->
            <div class="mb-[42px] flex flex-wrap gap-[9px]">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="rounded-full px-[18px] py-[9px] text-body-sm font-normal transition-colors"
                    :class="
                        activeTab === tab.id
                            ? 'bg-forest-ink text-linen-white'
                            : 'border border-hairline-gray text-charcoal hover:bg-linen'
                    "
                >
                    {{ tab.label }} · {{ tab.count }}
                </button>
            </div>

            <!-- Layer 1: Sampah RT (infinite scroll) -->
            <div v-if="activeTab === 'sampah'">
                <div v-if="stats.listingCount === 0" class="py-[70px] text-center text-muted-foreground">
                    <p class="text-body">Belum ada listing sampah.</p>
                    <Link
                        :href="registerRoute.url()"
                        class="mt-[7px] inline-block text-body-sm text-forest-ink hover:underline"
                    >
                        Daftar sebagai Rumah Tangga untuk mulai menjual →
                    </Link>
                </div>
                <template v-else>
                    <div :class="gridClass">
                        <MarketCard v-for="p in sampahProducts" :key="p.id" :product="p" />
                    </div>
                    <WhenVisible
                        v-if="pagination.hasMore"
                        always
                        :params="{
                            only: ['listings', 'pagination'],
                            data: { page: pagination.page + 1 },
                            preserveUrl: true,
                        }"
                    >
                        <div :class="['mt-[32px]', gridClass]">
                            <div v-for="n in 5" :key="n" class="flex flex-col gap-[9px]">
                                <Skeleton class="aspect-square w-full rounded-[14px]" />
                                <Skeleton class="h-[14px] w-2/3" />
                                <Skeleton class="h-[14px] w-1/3" />
                            </div>
                        </div>
                    </WhenVisible>
                </template>
            </div>

            <!-- Layer 2: Bahan Baku -->
            <div v-else-if="activeTab === 'bahan_baku'">
                <div v-if="bahanBakuProducts.length === 0" class="py-[70px] text-center text-muted-foreground">
                    <p class="text-body">Belum ada bahan baku tersedia.</p>
                </div>
                <div v-else :class="gridClass">
                    <MarketCard v-for="p in bahanBakuProducts" :key="p.id" :product="p" />
                </div>
            </div>

            <!-- Layer 3: Bahan Baku Jadi -->
            <div v-else>
                <div v-if="bahanJadiProducts.length === 0" class="py-[70px] text-center text-muted-foreground">
                    <p class="text-body">Belum ada bahan baku jadi dari industri.</p>
                </div>
                <div v-else :class="gridClass">
                    <MarketCard v-for="p in bahanJadiProducts" :key="p.id" :product="p" />
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-hairline-gray bg-forest-ink">
            <div class="mx-auto max-w-[1200px] px-[24px] py-[35px] text-center">
                <p class="font-serif text-subheading font-light text-linen-white">🌿 Daurin</p>
                <p class="mt-[4px] text-body-sm font-normal text-sage-wash">
                    Marketplace Daur Ulang 3-Lapis · Rumah Tangga → Pengepul → Industri
                </p>
            </div>
        </footer>
    </div>
</template>
