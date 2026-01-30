<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit as editLocale, swap } from '@/routes/locale';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';

const { t } = useTranslations();
const page = usePage();
const locale = computed(() => page.props.locale as string);
const locales = computed(() => page.props.locales as Record<string, string>);
const currentLocaleLabel = computed(() => locales.value?.[locale.value] ?? locale.value);
const localeEntries = computed(() =>
    Object.entries(locales.value ?? {}).map(([code, label]) => ({ code, label })),
);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings.locale'),
        href: editLocale().url,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.locale')" />

        <h1 class="sr-only">{{ t('settings.locale_settings_title') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('settings.locale')"
                    :description="t('settings.locale_description')"
                />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            type="button"
                            class="flex h-9 w-1/2 min-w-0 items-center gap-2 rounded-md border border-input bg-transparent p-2 text-start text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30 dark:border-input data-[state=open]:border-ring data-[state=open]:ring-ring/50 data-[state=open]:ring-[3px] data-[state=open]:bg-accent data-[state=open]:text-accent-foreground hover:bg-accent hover:text-accent-foreground"
                        >
                            <span class="truncate font-medium">{{ currentLocaleLabel }}</span>
                            <ChevronsUpDown class="ms-auto size-4 shrink-0" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        :side-offset="4"
                    >
                        <DropdownMenuItem
                            v-for="{ code, label } in localeEntries"
                            :key="code"
                            as-child
                        >
                            <a
                                :href="swap.url(code)"
                                class="block w-full cursor-pointer"
                                :class="{
                                    'bg-accent font-medium': locale === code,
                                }"
                            >
                                {{ label }}
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
