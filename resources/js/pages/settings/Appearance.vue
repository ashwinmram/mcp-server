<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useTranslations();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings.appearance_settings'),
        href: edit().url,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.appearance_settings')" />

        <h1 class="sr-only">{{ t('settings.appearance_settings_title') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    :title="t('settings.appearance_settings')"
                    :description="t('settings.appearance_description')"
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
