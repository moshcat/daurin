<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex w-fit items-center justify-center overflow-hidden whitespace-nowrap rounded-full border-transparent bg-mist-blue px-[14px] py-[7px] text-xs font-normal text-forest-ink',
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
