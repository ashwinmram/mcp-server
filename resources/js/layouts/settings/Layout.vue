<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editLocale } from '@/routes/locale';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useTranslations();
const { urlIsActive } = useActiveUrl();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('settings.profile'),
        href: editProfile(),
    },
    {
        title: t('settings.password'),
        href: editPassword(),
    },
    {
        title: t('settings.two_factor_auth'),
        href: show(),
    },
    {
        title: t('settings.appearance'),
        href: editAppearance(),
    },
    {
        title: t('settings.locale'),
        href: editLocale(),
    },
]);
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('settings.settings')"
            :description="t('settings.manage_profile_account')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0" aria-label="Settings">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': urlIsActive(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="toUrl(item.href)">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
