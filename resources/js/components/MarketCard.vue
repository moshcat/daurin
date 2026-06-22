<script setup lang="ts">
import { Heart, Route } from '@lucide/vue'
import { ref } from 'vue'
import TraceChain from '@/components/TraceChain.vue'
import {  formatRp } from '@/lib/listing'
import type {MarketProduct} from '@/lib/listing';

defineProps<{
    product: MarketProduct
}>()

const liked = ref(false)
const showTrace = ref(false)
</script>

<template>
    <article class="group flex flex-col gap-[9px]">
        <!-- Image -->
        <div class="relative aspect-square overflow-hidden rounded-[14px] bg-muted">
            <img
                :src="product.image"
                :alt="product.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            />

            <!-- Layer / category badge (top-left) -->
            <span
                class="absolute left-[10px] top-[10px] rounded-full bg-linen-white/95 px-[10px] py-[4px] text-xs font-normal text-charcoal"
            >
                {{ product.badge }}
            </span>

            <!-- Favorite button (bottom-right) -->
            <button
                type="button"
                @click="liked = !liked"
                :aria-pressed="liked"
                aria-label="Favoritkan"
                class="absolute bottom-[10px] right-[10px] flex size-9 items-center justify-center rounded-full bg-linen-white/95 text-charcoal transition-colors hover:bg-linen-white"
            >
                <Heart class="size-[18px]" :class="liked ? 'fill-forest-ink text-forest-ink' : ''" />
            </button>
        </div>

        <!-- Meta -->
        <div class="flex flex-col gap-[2px] px-[2px]">
            <p class="truncate text-body-sm font-normal text-forest-ink">{{ product.title }}</p>
            <p class="truncate text-caption font-normal text-muted-foreground">{{ product.subtitle }}</p>
            <p class="mt-[2px] text-body-sm font-normal text-forest-ink">{{ formatRp(product.price) }}</p>

            <!-- Traceability toggle (layers 2 & 3) -->
            <button
                v-if="product.trace && product.trace.length"
                type="button"
                @click="showTrace = !showTrace"
                class="mt-[4px] flex w-fit items-center gap-[4px] text-caption font-normal text-forest-ink hover:underline"
            >
                <Route class="size-3" />
                {{ showTrace ? 'Sembunyikan asal' : 'Lihat asal material' }}
            </button>
        </div>

        <!-- Lineage -->
        <div v-if="showTrace && product.trace" class="px-[2px]">
            <TraceChain :nodes="product.trace" dense />
        </div>
    </article>
</template>
