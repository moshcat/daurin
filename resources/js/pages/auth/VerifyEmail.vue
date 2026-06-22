<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';

defineProps<{
    status?: string;
}>();

defineOptions({
    layout: {
        title: 'Verifikasi email',
        description: 'Verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirim.',
    },
});
</script>

<template>
    <Head title="Verifikasi email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Tautan verifikasi baru telah dikirim ke alamat email Anda.
    </div>

    <div class="space-y-6 text-center">
        <p class="text-sm text-muted-foreground">
            Belum menerima email? Kami bisa mengirim ulang tautannya.
        </p>

        <Form method="post" action="/email/verification-notification" v-slot="{ processing }">
            <Button type="submit" :disabled="processing" class="w-full" data-test="resend-verification-button">
                <Spinner v-if="processing" />
                Kirim ulang email verifikasi
            </Button>
        </Form>

        <Link
            :href="logout()"
            as="button"
            class="mx-auto block text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
            data-test="logout-button"
        >
            Keluar
        </Link>
    </div>
</template>
