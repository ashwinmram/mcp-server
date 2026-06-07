<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    displayName?: string;
}>();

const page = usePage();
const appName = computed(
    () => props.displayName ?? (page.props.name as string),
);
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md"
    >
        <img
            src="/favicon.png"
            :alt="appName"
            class="size-8 object-contain"
        />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ appName }}
        </span>
    </div>
</template>
