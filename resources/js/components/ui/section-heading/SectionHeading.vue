<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import PillBadge from '@/components/ui/pill-badge/PillBadge.vue'

const props = defineProps<{
    label?: string
    class?: HTMLAttributes['class']
    align?: 'left' | 'center'
}>()
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col gap-[14px]',
                props.align === 'center' ? 'items-center text-center' : 'items-start text-left',
                props.class,
            )
        "
    >
        <PillBadge v-if="label">{{ label }}</PillBadge>
        <slot name="label" />
        <h2
            class="font-serif text-heading font-light text-forest-ink"
            style="letter-spacing: -0.4px; line-height: 1.35"
        >
            <slot />
        </h2>
    </div>
</template>
