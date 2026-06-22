<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import CheckItem from '@/components/ui/check-item/CheckItem.vue'
import PillBadge from '@/components/ui/pill-badge/PillBadge.vue'
import SectionHeading from '@/components/ui/section-heading/SectionHeading.vue'
import Stat from '@/components/ui/stat/Stat.vue'

// Colour tokens for the swatch grid
const brandColors = [
    { name: 'Forest Ink', token: '--color-forest-ink', hex: '#0f3e17', dark: true },
    { name: 'Sage Wash', token: '--color-sage-wash', hex: '#b1dbb8', dark: false },
    { name: 'Mist Blue', token: '--color-mist-blue', hex: '#b6ced5', dark: false },
    { name: 'Mint Veil', token: '--color-mint-veil', hex: '#cfe7d3', dark: false },
    { name: 'Linen', token: '--color-linen', hex: '#e1f4df', dark: false },
    { name: 'Linen White', token: '--color-linen-white', hex: '#fffefc', dark: false },
    { name: 'Hairline Gray', token: '--color-hairline-gray', hex: '#e5e7eb', dark: false },
    { name: 'Charcoal', token: '--color-charcoal', hex: '#222222', dark: true },
    { name: 'Graphite', token: '--color-graphite', hex: '#333333', dark: true },
]

const semanticColors = [
    { name: 'background', bg: 'bg-background', text: 'text-foreground' },
    { name: 'primary', bg: 'bg-primary', text: 'text-primary-foreground' },
    { name: 'secondary', bg: 'bg-secondary', text: 'text-secondary-foreground' },
    { name: 'muted', bg: 'bg-muted', text: 'text-muted-foreground' },
    { name: 'accent', bg: 'bg-accent', text: 'text-accent-foreground' },
    { name: 'destructive', bg: 'bg-destructive', text: 'text-destructive-foreground' },
]

const typeScale = [
    { label: 'display', size: 'text-display', sample: 'Daur Ulang', font: 'font-serif font-light' },
    { label: 'heading-lg', size: 'text-heading-lg', sample: 'Daur Ulang', font: 'font-sans font-light' },
    { label: 'heading', size: 'text-heading', sample: 'Mulai Recycle', font: 'font-serif font-light' },
    { label: 'heading-sm', size: 'text-heading-sm', sample: 'Pengepul Terdekat', font: 'font-sans font-light' },
    { label: 'subheading', size: 'text-subheading', sample: 'Bahan Baku Tersedia', font: 'font-sans font-light' },
    { label: 'body', size: 'text-body', sample: 'Platform daur ulang yang menghubungkan RT, pengepul, dan industri.', font: 'font-sans font-normal' },
    { label: 'body-sm', size: 'text-body-sm', sample: 'Kelola ketersediaan bahan baku dengan mudah.', font: 'font-sans font-normal' },
    { label: 'caption', size: 'text-caption', sample: 'Terdaftar • Aktif • Terverifikasi', font: 'font-sans font-normal' },
]

const checkItems = [
    'Lacak volume sampah dari RT ke pengepul',
    'Negosiasi harga bahan baku transparan',
    'Laporan ketersediaan stok real-time',
    'Koneksi langsung pengepul ke industri',
    'Verifikasi mitra daur ulang terpercaya',
    'Dashboard analitik per kategori sampah',
]
</script>

<template>
    <div class="min-h-screen bg-background font-sans text-foreground">

        <!-- Header -->
        <header class="border-b border-border px-[42px] py-[21px]">
            <div class="mx-auto flex max-w-[1200px] items-center justify-between">
                <span class="text-body-sm font-normal text-charcoal" style="letter-spacing:-0.42px">
                    Daurin — Design System
                </span>
                <PillBadge>v1.0 · Ease Health palette</PillBadge>
            </div>
        </header>

        <main class="mx-auto max-w-[1200px] space-y-[80px] px-[42px] py-[70px]">

            <!-- ── 1. Brand colours ──────────────────────────────────── -->
            <section>
                <SectionHeading label="Warna" class="mb-[42px]">
                    Palette forest ink &amp; sage
                </SectionHeading>

                <div class="grid grid-cols-3 gap-[21px] sm:grid-cols-5 lg:grid-cols-9">
                    <div
                        v-for="c in brandColors"
                        :key="c.token"
                        class="flex flex-col gap-[9px]"
                    >
                        <div
                            class="h-[56px] rounded-[14px] border border-hairline-gray"
                            :style="{ backgroundColor: c.hex }"
                        />
                        <p class="text-caption font-normal text-charcoal" style="letter-spacing:-0.36px;line-height:1.5">
                            {{ c.name }}
                        </p>
                        <p class="text-caption font-normal text-graphite" style="letter-spacing:-0.36px;line-height:1.5">
                            {{ c.hex }}
                        </p>
                    </div>
                </div>

                <div class="mt-[35px] grid grid-cols-3 gap-[14px] sm:grid-cols-6">
                    <div
                        v-for="s in semanticColors"
                        :key="s.name"
                        :class="[s.bg, 'flex h-[56px] items-center justify-center rounded-[14px] border border-hairline-gray']"
                    >
                        <span :class="[s.text, 'text-caption font-normal']" style="letter-spacing:-0.36px">
                            {{ s.name }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- ── 2. Tipografi ──────────────────────────────────────── -->
            <section>
                <SectionHeading label="Tipografi" class="mb-[42px]">
                    Inter &amp; Fraunces, weight 300–400
                </SectionHeading>

                <div class="space-y-[28px]">
                    <div
                        v-for="t in typeScale"
                        :key="t.label"
                        class="flex items-baseline gap-[21px] border-b border-hairline-gray pb-[21px]"
                    >
                        <span class="w-[112px] shrink-0 text-caption font-normal text-graphite" style="letter-spacing:-0.36px">
                            {{ t.label }}
                        </span>
                        <span :class="[t.size, t.font, 'text-foreground']">
                            {{ t.sample }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- ── 3. Button ─────────────────────────────────────────── -->
            <section>
                <SectionHeading label="Komponen" class="mb-[42px]">
                    Button — semua varian
                </SectionHeading>

                <div class="flex flex-wrap items-center gap-[21px]">
                    <Button variant="default">Mulai Recycle</Button>
                    <Button variant="secondary">Lihat Pengepul</Button>
                    <Button variant="outline">Ekspor Data</Button>
                    <Button variant="ghost">Batal</Button>
                    <Button variant="link">Selengkapnya →</Button>
                    <Button variant="destructive">Hapus</Button>
                </div>

                <div class="mt-[21px] flex flex-wrap items-center gap-[14px]">
                    <Button size="sm">Kecil</Button>
                    <Button size="default">Default</Button>
                    <Button size="lg">Besar</Button>
                    <Button disabled>Dinonaktifkan</Button>
                </div>
            </section>

            <!-- ── 4. Badge & PillBadge ──────────────────────────────── -->
            <section>
                <SectionHeading label="Komponen" class="mb-[42px]">
                    Badge &amp; PillBadge
                </SectionHeading>

                <div class="flex flex-wrap items-center gap-[14px]">
                    <Badge variant="default">Default</Badge>
                    <Badge variant="secondary">Secondary</Badge>
                    <Badge variant="outline">Outline</Badge>
                    <Badge variant="destructive">Error</Badge>
                    <Badge variant="pill">Pill / Section tag</Badge>
                </div>

                <div class="mt-[21px] flex flex-wrap items-center gap-[14px]">
                    <PillBadge>Daur Ulang Plastik</PillBadge>
                    <PillBadge>Pengepul Bersertifikat</PillBadge>
                    <PillBadge>Kemitraan Industri</PillBadge>
                </div>
            </section>

            <!-- ── 5. Card ───────────────────────────────────────────── -->
            <section>
                <SectionHeading label="Komponen" class="mb-[42px]">
                    Card — flat, no shadow
                </SectionHeading>

                <div class="grid grid-cols-1 gap-[21px] sm:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <PillBadge>Plastik PET</PillBadge>
                            <CardTitle class="font-serif font-light text-forest-ink text-heading-sm" style="letter-spacing:-0.84px">
                                500 kg tersedia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-body-sm text-muted-foreground" style="letter-spacing:-0.42px;line-height:1.5">
                                RT 05 Kelurahan Dalung · Diperbarui 2 jam lalu
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="bg-sage-wash">
                        <CardHeader>
                            <PillBadge>Kertas Kardus</PillBadge>
                            <CardTitle class="font-serif font-light text-forest-ink text-heading-sm" style="letter-spacing:-0.84px">
                                1.2 ton tersedia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-body-sm text-charcoal" style="letter-spacing:-0.42px;line-height:1.5">
                                Pengepul Sumber Rejeki · Denpasar Utara
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="bg-mist-blue">
                        <CardHeader>
                            <PillBadge>Logam Campuran</PillBadge>
                            <CardTitle class="font-serif font-light text-forest-ink text-heading-sm" style="letter-spacing:-0.84px">
                                300 kg tersedia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-body-sm text-charcoal" style="letter-spacing:-0.42px;line-height:1.5">
                                Industri Daur Ulang Bali · Mengwi
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- ── 6. SectionHeading ─────────────────────────────────── -->
            <section>
                <SectionHeading label="Komponen" class="mb-[42px]">
                    SectionHeading — tag + judul
                </SectionHeading>

                <div class="space-y-[42px] border border-hairline-gray rounded-[14px] p-[35px]">
                    <SectionHeading label="Daur Ulang Plastik" align="left">
                        Platform yang menghubungkan RT, pengepul, dan industri secara langsung
                    </SectionHeading>
                    <SectionHeading label="Bergabung Sekarang" align="center">
                        Mulai perjalanan daur ulang anda hari ini
                    </SectionHeading>
                </div>
            </section>

            <!-- ── 7. CheckItem grid ─────────────────────────────────── -->
            <section>
                <SectionHeading label="Fitur Utama" class="mb-[42px]">
                    Daurin dibangun untuk ekosistem daur ulang Indonesia
                </SectionHeading>

                <div class="grid grid-cols-1 gap-x-[56px] gap-y-[28px] sm:grid-cols-2">
                    <CheckItem
                        v-for="item in checkItems"
                        :key="item"
                    >{{ item }}</CheckItem>
                </div>
            </section>

            <!-- ── 8. Stats block ────────────────────────────────────── -->
            <section class="bg-linen rounded-[14px] py-[56px]">
                <div class="flex flex-col items-center gap-[42px]">
                    <SectionHeading label="Dampak" align="center">
                        Angka yang bicara sendiri
                    </SectionHeading>
                    <div class="flex flex-wrap items-center justify-center gap-[56px]">
                        <Stat value="12 RT" label="terdaftar aktif" />
                        <Stat value="48 ton" label="sampah terkelola" />
                        <Stat value="Rp 2.4M" label="transaksi berjalan" />
                        <Stat value="98%" label="kepuasan mitra" />
                    </div>
                </div>
            </section>

            <!-- ── 9. Surfaces ───────────────────────────────────────── -->
            <section>
                <SectionHeading label="Surface" class="mb-[42px]">
                    Elevasi lewat tint warna, bukan shadow
                </SectionHeading>

                <div class="flex flex-col gap-[14px]">
                    <div
                        v-for="s in [
                            { name: 'Linen White — canvas', cls: 'bg-linen-white' },
                            { name: 'Linen — level 2', cls: 'bg-linen' },
                            { name: 'Mint Veil — level 3', cls: 'bg-mint-veil' },
                            { name: 'Sage Wash — level 4', cls: 'bg-sage-wash' },
                            { name: 'Mist Blue — level 5', cls: 'bg-mist-blue' },
                        ]"
                        :key="s.name"
                        :class="[s.cls, 'rounded-[14px] border border-hairline-gray px-[21px] py-[14px]']"
                    >
                        <p class="text-body-sm font-normal text-charcoal" style="letter-spacing:-0.42px;line-height:1.5">
                            {{ s.name }}
                        </p>
                    </div>
                </div>
            </section>

        </main>

        <footer class="border-t border-hairline-gray bg-forest-ink px-[42px] py-[35px]">
            <div class="mx-auto max-w-[1200px] flex items-center justify-between">
                <span class="text-body-sm font-normal text-linen-white" style="letter-spacing:-0.42px">
                    Daurin Design System · 2026
                </span>
                <span class="text-caption font-normal text-sage-wash" style="letter-spacing:-0.36px">
                    Ease Health palette · Tailwind v4 · shadcn-vue
                </span>
            </div>
        </footer>
    </div>
</template>
