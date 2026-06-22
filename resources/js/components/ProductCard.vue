<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { formatRp } from '@/lib/listing'
import type { MarketProduct } from '@/lib/listing'

defineProps<{ product: MarketProduct }>()

const page = usePage()
const authUser = computed(() => (page.props.auth as { user?: { role?: string } } | undefined)?.user ?? null)
const isAuth = computed(() => Boolean(authUser.value))
const userRole = computed(() => authUser.value?.role ?? null)

function tagClass(product: MarketProduct): string {
    if (product.layer === 'sampah') {
        return 'tag-amber'
    }
    if (product.layer === 'bahan_jadi') {
        return 'tag-blue'
    }

    return 'tag-green'
}

function ctaLabel(product: MarketProduct): string {
    if (product.lelangId) {
        return 'Nego Harga'
    }
    if (product.layer === 'bahan_jadi') {
        return isAuth.value ? 'Beli' : 'Masuk untuk Beli'
    }
    if (product.layer === 'sampah') {
        if (!isAuth.value) {
            return 'Masuk untuk Ambil'
        }

        return userRole.value === 'pengepul' ? 'Ambil' : 'Lihat'
    }

    return isAuth.value ? 'Beli' : 'Masuk untuk Beli'
}

function ctaHref(product: MarketProduct): string {
    if (product.lelangId) {
        return `/lelang/${product.lelangId}`
    }
    if (!isAuth.value) {
        return '/login'
    }
    if (product.layer === 'bahan_jadi') {
        return '/beli-bahan-jadi'
    }
    if (product.layer === 'sampah') {
        return userRole.value === 'pengepul' ? '/pengepul/ketersediaan' : '/dashboard'
    }

    return '/dashboard'
}
</script>

<template>
    <article class="product-card">
        <div class="product-media">
            <img :src="product.image" :alt="product.title" />
            <span class="product-tag" :class="tagClass(product)">{{ product.badge }}</span>
        </div>
        <div class="product-body">
            <h3 class="product-title">{{ product.title }}</h3>
            <p class="product-desc">{{ product.subtitle }}</p>
            <div class="product-foot">
                <span class="product-price">{{ formatRp(product.price) }}</span>
                <Link :href="ctaHref(product)" class="card-btn">{{ ctaLabel(product) }}</Link>
            </div>
        </div>
    </article>
</template>

<style scoped>
.product-card {
    --green: #16a34a;
    --green-dark: #15803d;
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e5e7eb;

    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.25s, transform 0.25s;
    font-family: inherit;
}

.product-card:hover {
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.1);
    transform: translateY(-4px);
}

.product-media {
    position: relative;
    aspect-ratio: 4 / 3;
    background: #f1f5f9;
}

.product-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
}

.tag-green {
    background: var(--green);
}

.tag-amber {
    background: #f59e0b;
}

.tag-blue {
    background: #2563eb;
}

.product-body {
    padding: 16px 18px 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.product-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--ink);
    line-height: 1.35;
    margin: 0;
}

.product-desc {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.55;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-foot {
    margin-top: auto;
    padding-top: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.product-price {
    font-size: 17px;
    font-weight: 800;
    color: var(--green-dark);
}

.card-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 8px 14px;
    border-radius: 999px;
    background: var(--green);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    transition: background 0.2s;
}

.card-btn:hover {
    background: var(--green-dark);
}
</style>
