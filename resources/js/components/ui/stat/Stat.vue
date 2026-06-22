<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
    value: string
    label: string
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <div :class="cn('flex flex-col items-center gap-[4px] text-center', props.class)">
        <span
            class="font-sans text-heading-lg font-light text-forest-ink"
            style="letter-spacing: -1.68px; line-height: 1"
        >
            {{ value }}
        </span>
        <span
            class="text-caption font-normal text-charcoal"
            style="letter-spacing: -0.36px; line-height: 1.5"
        >
            {{ label }}
        </span>
    </div>
</template>
