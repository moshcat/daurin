<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Check } from '@lucide/vue'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <div :class="cn('flex items-start gap-[14px]', props.class)">
        <!-- Forest Ink square with white checkmark -->
        <div
            class="flex size-8 shrink-0 items-center justify-center rounded-[7px] bg-forest-ink text-linen-white"
        >
            <Check class="size-4" stroke-width="2.5" />
        </div>
        <p class="text-body-sm font-normal text-foreground" style="letter-spacing: -0.42px; line-height: 1.5">
            <slot />
        </p>
    </div>
</template>
