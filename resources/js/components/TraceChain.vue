<script setup lang="ts">
import { ArrowRight } from '@lucide/vue'
import type {TraceNode} from '@/lib/listing';

withDefaults(
    defineProps<{
        nodes: TraceNode[]
        dense?: boolean
    }>(),
    { dense: false },
)

const roleLabel: Record<TraceNode['role'], string> = {
    rumah_tangga: 'Rumah Tangga',
    pengepul: 'Pengepul',
    industri: 'Industri',
}
</script>

<template>
    <div class="flex flex-wrap items-stretch gap-[7px]">
        <template v-for="(node, i) in nodes" :key="i">
            <div
                class="flex flex-1 flex-col gap-[2px] rounded-[7px] border border-hairline-gray bg-linen-white px-[11px] py-[9px]"
                :class="dense ? 'min-w-[120px]' : 'min-w-[140px]'"
            >
                <span class="text-caption font-normal uppercase tracking-wide text-forest-ink">
                    {{ roleLabel[node.role] }}
                </span>
                <span class="truncate text-body-sm font-normal text-foreground">{{ node.title }}</span>
                <span class="truncate text-caption font-normal text-muted-foreground">{{ node.detail }}</span>
                <span class="mt-[2px] truncate text-caption font-normal text-charcoal">{{ node.actor }}</span>
            </div>
            <div v-if="i < nodes.length - 1" class="flex items-center text-forest-ink">
                <ArrowRight class="size-4" />
            </div>
        </template>
    </div>
</template>
