<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Buat akun',
        description: 'Masukkan data Anda untuk membuat akun',
    },
});

const selectedRole = ref('rumah_tangga');
const lat = ref('');
const lng = ref('');
const locationStatus = ref('');

function getLocation() {
    if (!navigator.geolocation) {
        locationStatus.value = 'Geolokasi tidak didukung browser ini.';
        return;
    }
    locationStatus.value = 'Mengambil lokasi...';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            lat.value = String(pos.coords.latitude);
            lng.value = String(pos.coords.longitude);
            locationStatus.value = `✓ Lokasi diambil (${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)})`;
        },
        () => {
            locationStatus.value = 'Gagal mengambil lokasi. Anda bisa skip.';
        },
    );
}
</script>

<template>
    <Head title="Daftar" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nama lengkap</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Nama lengkap"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Role selection -->
            <div class="grid gap-2">
                <Label for="role">Peran</Label>
                <select
                    id="role"
                    name="role"
                    v-model="selectedRole"
                    :tabindex="3"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                    <option value="rumah_tangga">🏠 Rumah Tangga</option>
                    <option value="pengepul">♻️ Pengepul</option>
                    <option value="industri">🏭 Industri Pengolah</option>
                </select>
                <InputError :message="errors.role" />
            </div>

            <!-- Location (optional) -->
            <div class="grid gap-2">
                <Label>Lokasi (opsional)</Label>
                <input type="hidden" name="lat" :value="lat" />
                <input type="hidden" name="lng" :value="lng" />
                <div class="flex gap-2 items-center">
                    <Button type="button" variant="outline" size="sm" :tabindex="4" @click="getLocation" class="shrink-0">
                        📍 Ambil Lokasi
                    </Button>
                    <span class="text-xs text-muted-foreground">{{ locationStatus || 'Digunakan untuk fitur peta pengepul' }}</span>
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Konfirmasi kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-2 w-full" tabindex="7" :disabled="processing" data-test="register-user-button">
                <Spinner v-if="processing" />
                Buat akun
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Sudah punya akun?
            <TextLink :href="login()" class="underline underline-offset-4" :tabindex="8">Masuk</TextLink>
        </div>
    </Form>
</template>
