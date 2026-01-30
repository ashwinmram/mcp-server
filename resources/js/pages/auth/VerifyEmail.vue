<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <AuthLayout
        :title="t('auth.verify_email_title')"
        :description="t('auth.verify_email_description')"
    >
        <Head :title="t('auth.email_verification')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('auth.verification_link_sent') }}
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                {{ t('auth.resend_verification_email') }}
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                {{ t('nav.log_out') }}
            </TextLink>
        </Form>
    </AuthLayout>
</template>
