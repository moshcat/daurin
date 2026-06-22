<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

// Wizard 2 langkah — langkah ke-2 (identitas perusahaan) hanya untuk industri.
const step = ref(1);
const step1El = ref<HTMLElement | null>(null);

// Jika berpindah dari industri ke peran lain, kembalikan ke langkah 1.
watch(selectedRole, (role) => {
    if (role !== 'industri') {
        step.value = 1;
    }
});

function goToCompanyStep(): void {
    const root = step1El.value;

    if (root) {
        const fields = Array.from(
            root.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select'),
        );

        // Pastikan kecocokan kata sandi sebelum lanjut (native tak mengeceknya).
        const pw = root.querySelector<HTMLInputElement>('input[name="password"]');
        const pwc = root.querySelector<HTMLInputElement>('input[name="password_confirmation"]');

        if (pw && pwc) {
            pwc.setCustomValidity(pw.value !== pwc.value ? 'Konfirmasi kata sandi tidak cocok.' : '');
        }

        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();

                return;
            }
        }
    }

    step.value = 2;
}

// Jika ada error validasi pada field langkah 1, lompat kembali ke langkah 1.
function handleError(errors: Record<string, string>): void {
    if (errors.name || errors.email || errors.role || errors.password || errors.password_confirmation) {
        step.value = 1;
    }
}

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
        @error="handleError"
    >
        <!-- Indikator langkah (khusus industri) -->
        <div v-if="selectedRole === 'industri'" class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
            <span :class="step === 1 ? 'text-green-700' : ''">1. Akun</span>
            <span class="h-px flex-1 bg-border" />
            <span :class="step === 2 ? 'text-green-700' : ''">2. Perusahaan</span>
        </div>

        <div class="grid gap-6">
            <!-- ── Langkah 1: data akun ── -->
            <div ref="step1El" v-show="step === 1" class="grid gap-6">
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
            </div>

            <!-- ── Langkah 2: identitas perusahaan (industri saja) ── -->
            <div v-if="selectedRole === 'industri'" v-show="step === 2" class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="nama_pt">Nama Perusahaan</Label>
                    <Input
                        id="nama_pt"
                        type="text"
                        required
                        :tabindex="7"
                        name="nama_pt"
                        placeholder="PT Contoh Daur Ulang"
                    />
                    <InputError :message="errors.nama_pt" />
                </div>

                <div class="grid gap-2">
                    <Label for="alamat_pt">Alamat Perusahaan</Label>
                    <Input
                        id="alamat_pt"
                        type="text"
                        required
                        :tabindex="8"
                        name="alamat_pt"
                        placeholder="Jalan, Kota, Provinsi, Kode Pos"
                    />
                    <InputError :message="errors.alamat_pt" />
                    <span class="text-xs text-muted-foreground">
                        Isi alamat kantor PT secara manual (boleh diisi meski Anda sedang tidak di kantor).
                    </span>
                </div>
            </div>

            <!-- ── Aksi ── -->
            <!-- Non-industri: langsung daftar -->
            <Button
                v-if="selectedRole !== 'industri'"
                type="submit"
                class="mt-2 w-full"
                tabindex="9"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Buat akun
            </Button>

            <!-- Industri, langkah 1: lanjut -->
            <Button
                v-else-if="step === 1"
                type="button"
                class="mt-2 w-full"
                tabindex="9"
                @click="goToCompanyStep"
            >
                Lanjut →
            </Button>

            <!-- Industri, langkah 2: kembali + daftar -->
            <div v-else class="mt-2 flex gap-2">
                <Button type="button" variant="outline" @click="step = 1">← Kembali</Button>
                <Button
                    type="submit"
                    class="flex-1"
                    tabindex="9"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Buat akun
                </Button>
            </div>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Sudah punya akun?
            <TextLink :href="login()" class="underline underline-offset-4" :tabindex="10">Masuk</TextLink>
        </div>
    </Form>
</template>
