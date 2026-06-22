<script setup lang="ts">
import { computed } from 'vue'

interface Bar {
    label: string
    value: number
}

const props = withDefaults(
    defineProps<{
        data: Bar[]
        unit?: string
    }>(),
    { unit: '' },
)

const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)))

function pct(v: number): number {
    return Math.round((v / max.value) * 100)
}

function formatValue(v: number): string {
    return v.toLocaleString('id-ID')
}
</script>

<template>
    <div class="flex flex-col gap-[14px]">
        <div v-for="bar in data" :key="bar.label" class="flex items-center gap-[14px]">
            <span class="w-[96px] shrink-0 text-caption font-normal text-charcoal">{{ bar.label }}</span>
            <div class="h-[14px] flex-1 overflow-hidden rounded-full bg-linen">
                <div
                    class="h-full rounded-full bg-forest-ink transition-all duration-500"
                    :style="{ width: pct(bar.value) + '%' }"
                />
            </div>
            <span class="w-[72px] shrink-0 text-right text-caption font-normal text-forest-ink">
                {{ formatValue(bar.value) }}{{ unit ? ' ' + unit : '' }}
            </span>
        </div>
    </div>
</template>
