<script setup lang="ts">
import { swap } from '@/routes/locale';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale as string);
const locales = computed(() => page.props.locales as Record<string, string>);

const localeEntries = computed(() =>
    Object.entries(locales.value ?? {}).map(([code, label]) => ({ code, label })),
);
</script>

<template>
    <div class="flex items-center gap-1">
        <a
            v-for="{ code, label } in localeEntries"
            :key="code"
            :href="swap.url(code)"
            class="rounded-md px-2 py-1 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
            :class="
                locale === code
                    ? 'bg-accent text-accent-foreground'
                    : 'text-muted-foreground'
            "
        >
            {{ code === 'en' ? 'EN' : label }}
        </a>
    </div>
</template>
