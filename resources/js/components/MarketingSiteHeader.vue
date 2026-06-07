<script setup lang="ts">
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/composables/useTranslations';
import { Heart, Menu } from 'lucide-vue-next';
import { ref } from 'vue';

const { t } = useTranslations();

const mobileMenuOpen = ref(false);

const techLinks = [
    { name: 'Cursor', url: 'https://cursor.sh' },
    { name: 'Inertia', url: 'https://inertiajs.com' },
    { name: 'Laracast', url: 'https://laracasts.com' },
    { name: 'Laravel', url: 'https://laravel.com' },
    { name: 'NotebookLM', url: 'https://notebooklm.google.com' },
    { name: 'Spatie', url: 'https://spatie.be' },
    { name: 'Tailwind', url: 'https://tailwindcss.com' },
    { name: 'Vue', url: 'https://vuejs.org' },
];
</script>

<template>
    <header
        class="w-full border-b border-[#19140035] bg-white/80 backdrop-blur-sm dark:border-[#3E3E3A] dark:bg-[#161615]/80"
    >
        <nav
            class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8"
        >
            <div class="flex items-center gap-2">
                <img
                    src="/favicon.png"
                    :alt="t('welcome.app_name')"
                    class="h-8 w-auto object-contain"
                />
                <h1 class="text-xl font-semibold">
                    {{ t('welcome.app_name_short') }}
                </h1>
            </div>
            <div class="hidden flex-wrap items-center gap-2 sm:flex">
                <template v-for="(link, index) in techLinks" :key="link.name">
                    <a
                        :href="link.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                    >
                        {{ link.name }}
                    </a>
                    <Heart
                        v-if="index < techLinks.length - 1"
                        class="mx-3 h-3 w-3"
                        style="color: #ce9e47"
                        fill="currentColor"
                    />
                </template>
            </div>
            <Sheet v-model:open="mobileMenuOpen">
                <SheetTrigger as-child>
                    <button
                        class="rounded-sm p-2 text-[#706f6c] hover:bg-[#19140035]/10 hover:text-[#1b1b18] sm:hidden dark:text-[#A1A09A] dark:hover:bg-[#3E3E3A]/50 dark:hover:text-[#EDEDEC]"
                        aria-label="Open menu"
                    >
                        <Menu class="h-6 w-6" />
                    </button>
                </SheetTrigger>
                <SheetContent side="right" class="w-[300px]">
                    <SheetHeader>
                        <SheetTitle>{{ t('welcome.tech_stack') }}</SheetTitle>
                    </SheetHeader>
                    <nav class="mt-6 flex flex-col items-center space-y-2">
                        <template
                            v-for="(link, index) in techLinks"
                            :key="link.name"
                        >
                            <a
                                :href="link.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-center text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                @click="mobileMenuOpen = false"
                            >
                                {{ link.name }}
                            </a>
                            <div
                                v-if="index < techLinks.length - 1"
                                class="flex items-center justify-center py-2"
                            >
                                <Heart
                                    class="h-3 w-3"
                                    style="color: #ce9e47"
                                    fill="currentColor"
                                />
                            </div>
                        </template>
                    </nav>
                </SheetContent>
            </Sheet>
        </nav>
    </header>
</template>
