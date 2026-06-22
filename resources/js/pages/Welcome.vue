<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ProductCard from '@/components/ProductCard.vue'
import Navbar from '@/components/Navbar.vue'
import { productFromBahanBaku, productFromBahanJadi, productFromListing } from '@/lib/listing'
import type { BahanBakuItem, BahanJadiItem, ListingItem } from '@/lib/listing'
import { store as registerRoute } from '@/routes/register'

const props = defineProps<{
    listings: ListingItem[]
    bahanBaku: BahanBakuItem[]
    bahanJadi: BahanJadiItem[]
    pagination: { page: number; hasMore: boolean }
    stats: { listingCount: number; bahanBakuCount: number; bahanJadiCount: number }
}>()

const page = usePage()
const authUser = computed(() => (page.props.auth as { user?: { role?: string } } | undefined)?.user ?? null)
const isAuth = computed(() => Boolean(authUser.value))

const sampahProducts = computed(() => props.listings.map(productFromListing))
const bahanBakuProducts = computed(() => props.bahanBaku.map(productFromBahanBaku))
const bahanJadiProducts = computed(() => props.bahanJadi.map(productFromBahanJadi))

// Preview gabungan untuk landing; daftar lengkap + filter ada di halaman /etalase.
const previewProducts = computed(() =>
    [...bahanBakuProducts.value, ...sampahProducts.value, ...bahanJadiProducts.value].slice(0, 8),
)

const heroStats = computed(() => [
    { value: props.stats.listingCount, label: 'Sampah' },
    { value: props.stats.bahanBakuCount, label: 'Bahan Baku' },
    { value: props.stats.bahanJadiCount, label: 'Bahan Jadi' },
])

// Logo bisa ditaruh di public/partners/<file>.png; sebelum ada file, inisial tampil.
const partners = [
    { name: 'PT. Makmur Lautan Sejahtera', logo: '/pngtree-fish-logo-design-ready-to-use-png-image_4385061.png' },
    { name: 'PT. Perikanan Nusantara Jaya', logo: '/pngtree-fish-logo-design-ready-to-use-png-image_4385061.png' },
    { name: 'PT. Bahari Sentosa Abadi', logo: '/pngtree-fish-logo-design-ready-to-use-png-image_4385061.png' },
    { name: 'PT. Teknologi Akuakultur', logo: '/pngtree-fish-logo-design-ready-to-use-png-image_4385061.png' },
]
const faqs = [
    { q: 'Apakah layanan di Daurin dikenakan biaya?', a: 'Pendaftaran dan penggunaan dasar aplikasi Daurin 100% gratis. Kami hanya mengenakan biaya admin kecil jika transaksi jual-beli berhasil dilakukan.' },
    { q: 'Apakah program kemitraan Daurin bersertifikasi?', a: 'Ya, setiap mitra resmi yang terdaftar akan mendapatkan sertifikat keanggotaan dan sertifikat kontribusi lingkungan tahunan.' },
    { q: 'Apa saja yang akan didapatkan oleh mitra/penjual?', a: 'Anda akan mendapatkan akses langsung ke ribuan pengepul, kepastian harga yang transparan, riwayat transaksi lengkap, serta panduan memilah sampah.' },
    { q: 'Apakah sertifikat dari Daurin bisa digunakan untuk keperluan CSR?', a: 'Tentu, sertifikat emisi dan kontribusi lingkungan dari Daurin dapat dilampirkan pada laporan keberlanjutan (sustainability report) perusahaan Anda.' },
    { q: 'Berapa lama proses penjemputan yang diselenggarakan oleh Daurin?', a: 'Penjemputan dilakukan selambat-lambatnya 1x24 jam setelah Anda melakukan konfirmasi pesanan di aplikasi.' },
    { q: 'Apakah tersedia layanan konsultasi secara daring (online)?', a: 'Ya, tim support kami tersedia 24/7 melalui fitur Live Chat di aplikasi untuk membantu segala kebutuhan daur ulang Anda.' },
]

const activeFaq = ref<number | null>(null)
const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index
}
</script>

<template>
    <Head title="Daurin — Marketplace Daur Ulang">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="lp">
        <Navbar />

        <main>
            <!-- ── Hero ── -->
            <section class="lp-container hero">
                <div class="hero-left">
                    <h1 class="hero-title">Ubah sampah terpilah jadi bahan baku bernilai</h1>
                    <p class="hero-desc">
                        Marketplace daur ulang yang menghubungkan rumah tangga, pengepul, dan
                        industri — pilah, jual, negosiasi harga, hingga diolah kembali.
                    </p>
                    <div class="hero-cta">
                        <a href="#etalase" class="btn btn-primary btn-lg">Lihat Etalase</a>
                        <a href="#etalase" class="link-underline">Pelajari lebih lanjut</a>
                    </div>

                    <div class="stat-card desktop-stat">
                        <div v-for="s in heroStats" :key="s.label" class="stat-item">
                            <div class="stat-value">{{ s.value }}</div>
                            <div class="stat-label">{{ s.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="hero-art">
                    <img src="/landinghero.png" alt="Ilustrasi daur ulang Daurin" class="hero-img" />
                </div>

                <div class="stat-card mobile-stat">
                    <div v-for="s in heroStats" :key="s.label" class="stat-item">
                        <div class="stat-value">{{ s.value }}</div>
                        <div class="stat-label">{{ s.label }}</div>
                    </div>
                </div>
            </section>

            <!-- ── Mitra ── -->
            <section class="lp-container partners">
                <h2 class="partners-title">Bekerja sama dengan</h2>
                <div class="partners-row">
                    <div v-for="(p, index) in partners" :key="index" class="partner">
                        <div class="partner-logo">
                            <img :src="p.logo" :alt="p.name" class="partner-img" onerror="this.style.display='none'" />
                        </div>
                        <span class="partner-name">{{ p.name }}</span>
                    </div>
                </div>
            </section>

            <!-- ── Etalase (preview gabungan) ── -->
            <section id="etalase" class="lp-container section">
                <div class="section-head row">
                    <div>
                        <h2>Etalase Daur Ulang</h2>
                        <p class="muted">Sampah terpilah, bahan baku, hingga bahan jadi — dalam satu marketplace.</p>
                    </div>
                    <Link href="/etalase" class="link-underline">Lihat Semua →</Link>
                </div>

                <div v-if="previewProducts.length === 0" class="empty-state">
                    Belum ada produk tersedia.
                </div>

                <div v-else class="product-grid">
                    <ProductCard
                        v-for="product in previewProducts"
                        :key="`${product.layer}-${product.id}`"
                        :product="product"
                    />
                </div>
            </section>

            <!-- ── FAQ ── -->
            <section class="lp-container section faq-section">
                <div class="section-head center faq-head">
                    <h2 class="faq-title">Frequently Asked Questions</h2>
                    <p class="muted faq-subtitle">Pertanyaan-pertanyaan yang sering ditanyakan oleh calon mitra sebelum mendaftar di Daurin</p>
                </div>
                <div class="faq-list">
                    <div v-for="(faq, index) in faqs" :key="index" class="faq-item">
                        <button class="faq-question" @click="toggleFaq(index)">
                            <span class="faq-q-text">{{ faq.q }}</span>
                            <span class="faq-icon">{{ activeFaq === index ? '−' : '+' }}</span>
                        </button>
                        <div class="faq-answer" v-show="activeFaq === index">
                            <p>{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── CTA ── -->
            <section class="lp-container cta-section">
                <div class="cta-banner">
                    <h2 class="cta-title">Siap untuk mengubah masa depan bumi?</h2>
                    <p class="cta-desc">
                        Daftar sekarang, dan mulai perjalananmu menuju lingkungan yang lebih hijau serta masa depan yang berkelanjutan.
                    </p>
                    <Link :href="registerRoute.url()" class="btn cta-btn">Daftar Sekarang</Link>
                </div>
            </section>
        </main>

        <!-- ── Footer ── -->
        <footer id="kontak" class="lp-footer">
            <div class="lp-container footer-grid">
                <div class="footer-brand">
                    <Link href="/" class="brand">
                        <img src="/logo.png" alt="Daurin" style="height: 32px; width: auto;" />
                        <span class="brand-name">Daurin</span>
                    </Link>
                    <p class="muted">
                        Marketplace daur ulang tiga lapis yang menghubungkan rumah tangga,
                        pengepul, dan industri untuk masa depan yang lebih hijau.
                    </p>
                    <p class="footer-copy">© Copyright PT LESTARI DAUR NUSANTARA 2026</p>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li>support@daurin.id</li>
                        <li>Jl. Lingkungan Hijau No. 12, Jakarta</li>
                        <li>+62 811 2345 678</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kategori</h4>
                    <ul>
                        <li>Kertas &amp; Kardus</li>
                        <li>Plastik (PET/HDPE)</li>
                        <li>Logam &amp; Kaleng</li>
                        <li>Elektronik</li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.lp {
    --green: #16a34a;
    --green-dark: #15803d;
    --green-soft: #dcfce7;
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e5e7eb;
    --bg: #ffffff;

    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg);
    color: var(--ink);
    line-height: 1.55;
    overflow-x: clip;
}

.lp * {
    box-sizing: border-box;
}

.lp a {
    text-decoration: none;
    color: inherit;
}

.lp ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.lp img {
    max-width: 100%;
    display: block;
}

.lp-container {
    width: 100%;
    max-width: 1560px;
    margin: 0 auto;
    padding: 0 24px;
}

@media (min-width: 1024px) {
    .lp-container {
        padding: 0 56px;
    }
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 14px;
    padding: 10px 22px;
    transition: all 0.18s ease;
    cursor: pointer;
}

.btn-primary {
    background: var(--green);
    color: #fff !important;
}

.btn-primary:hover {
    background: var(--green-dark);
    transform: translateY(-1px);
}

.btn-lg {
    padding: 15px 34px;
    font-size: 15px;
}

.btn-block {
    width: 100%;
    margin-top: 12px;
}

.btn-text {
    font-weight: 700;
    font-size: 14px;
    color: var(--ink);
}

.btn-text:hover {
    color: var(--green-dark);
}

.link-underline {
    font-weight: 700;
    font-size: 14px;
    color: var(--ink);
    text-decoration: underline;
    text-underline-offset: 4px;
    white-space: nowrap;
}

.link-underline:hover {
    color: var(--green-dark);
}

/* Header */
.lp-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid var(--line);
}

.lp-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 76px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 20px;
}

.brand-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--green);
    color: #fff;
    font-size: 20px;
}

.nav-center {
    display: none;
    gap: 40px;
    font-weight: 600;
    font-size: 15px;
    color: #334155;
}

.nav-center a:hover {
    color: var(--green-dark);
}

@media (min-width: 900px) {
    .nav-center {
        display: flex;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.menu-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    color: var(--ink);
}

@media (min-width: 900px) {
    .menu-toggle {
        display: none;
    }
}

.btn-text {
    display: none;
}

@media (min-width: 640px) {
    .btn-text {
        display: inline-flex;
    }
}

.mobile-menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-top: 8px;
    padding-bottom: 16px;
}

.mobile-menu a {
    padding: 12px 8px;
    border-radius: 10px;
    font-weight: 600;
    color: #334155;
}

.mobile-menu a:hover {
    background: #f1f5f9;
}

@media (min-width: 900px) {
    .mobile-menu {
        display: none;
    }
}

/* Hero */
.hero {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
    padding-top: 56px;
    padding-bottom: 56px;
    align-items: center;
}

@media (min-width: 900px) {
    .hero {
        grid-template-columns: 1fr 1.1fr;
        gap: 48px;
        padding-top: 72px;
        padding-bottom: 72px;
    }
}

.hero-title {
    font-size: clamp(2.4rem, 5vw, 4rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.02em;
    color: var(--ink);
    margin: 0 0 22px;
}

.hero-desc {
    font-size: 1.05rem;
    color: var(--muted);
    max-width: 30rem;
    margin: 0 0 32px;
}

.hero-cta {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

/* Stat card with stacked dark shadow (LKP style) */
.stat-card {
    margin-top: 48px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    max-width: 460px;
    padding: 24px 8px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 10px 12px 0 0 var(--ink);
}

@media (max-width: 899px) {
    .stat-card.desktop-stat {
        display: none;
    }
    .stat-card.mobile-stat {
        margin-top: 0;
    }
}

@media (min-width: 900px) {
    .stat-card.mobile-stat {
        display: none;
    }
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
}

.stat-label {
    margin-top: 6px;
    font-size: 0.8rem;
    color: var(--muted);
}

/* Hero art */
.hero-art {
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-img {
    width: 100%;
    max-width: 760px;
    height: auto;
    object-fit: contain;
}

/* Sections */
.section {
    padding-top: 64px;
    padding-bottom: 8px;
}

.section-head.center {
    text-align: center;
    margin-bottom: 40px;
}

.section-head.row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 28px;
}

@media (max-width: 640px) {
    .section-head.row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

.section-head h2 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.01em;
    margin: 0;
}

.eyebrow {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--green-dark);
    background: var(--green-soft);
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 14px;
}

.muted {
    color: var(--muted);
    font-size: 0.95rem;
}

/* Steps */
.step-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 768px) {
    .step-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.step-card {
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 30px 26px;
    background: #fff;
    transition: box-shadow 0.2s, transform 0.2s;
}

.step-card:hover {
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
    transform: translateY(-3px);
}

.step-num {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--green);
    color: #fff;
    font-weight: 800;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
}

.step-card h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
}

.step-card p {
    color: var(--muted);
    font-size: 14px;
    margin: 0;
}

/* Product grid — kartu besar ala referensi */
.product-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 22px;
}

@media (min-width: 640px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1400px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.empty-state {
    border: 1px dashed var(--line);
    border-radius: 16px;
    padding: 48px;
    text-align: center;
    color: var(--muted);
    font-size: 14px;
}

.load-more {
    margin-top: 28px;
    text-align: center;
    color: var(--muted);
    font-size: 14px;
}

/* Footer */
.lp-footer {
    margin-top: 80px;
    background: #0f172a;
    color: #f8fafc;
    padding: 72px 0;
}

.lp-footer .brand {
    color: #fff;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 48px;
}

@media (min-width: 768px) {
    .footer-grid {
        grid-template-columns: 2fr 1.5fr 1fr;
    }
}

.footer-brand .muted {
    margin-top: 16px;
    max-width: 38ch;
    color: #cbd5e1;
}

.footer-copy {
    margin-top: 32px;
    font-size: 13px;
    font-weight: 600;
    color: #cbd5e1;
}

.footer-col h4 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 24px;
    color: #fff;
}

.footer-col ul {
    display: flex;
    flex-direction: column;
    gap: 16px;
    color: #cbd5e1;
    font-size: 14px;
}

.footer-col a:hover {
    color: #fff;
}

/* Mitra / partners */
.partners {
    padding-top: 56px;
    padding-bottom: 8px;
    text-align: center;
}

.partners-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 40px;
}

.partners-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 24px 40px;
}

.partner {
    display: flex;
    align-items: center;
    gap: 14px;
}

.partner-logo {
    position: relative;
    height: 48px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.partner-initials {
    font-weight: 800;
    font-size: 15px;
    color: #94a3b8;
}

.partner-img {
    height: 100%;
    width: auto;
    object-fit: contain;
}

.partner-name {
    color: #9ca3af;
    font-weight: 500;
    font-size: 1rem;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .partners {
        overflow: hidden;
    }
    .partners-row {
        flex-wrap: nowrap;
        justify-content: flex-start;
        gap: 32px;
        width: max-content;
        animation: marquee 15s linear infinite;
    }
    @keyframes marquee {
        0% { transform: translateX(100vw); }
        100% { transform: translateX(-100%); }
    }
}

/* CTA */
.cta-section {
    padding-top: 64px;
    padding-bottom: 64px;
}

.cta-banner {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    background-color: #f8fafc;
    background-image: url('/ctaaset/ctaversimobile.png');
    background-size: cover;
    background-position: center;
    padding: 140px 24px;
    text-align: center;
    color: var(--ink);
}

@media (min-width: 768px) {
    .cta-banner {
        background-image: url('/ctaaset/ctaversideskop.png');
        padding: 180px 40px;
    }
}

.cta-title {
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 800;
    margin: 0 0 16px;
    line-height: 1.2;
    color: var(--ink);
    position: relative;
    z-index: 10;
}

.cta-desc {
    font-size: 1.05rem;
    margin: 0 auto 32px;
    max-width: 600px;
    color: var(--muted);
    position: relative;
    z-index: 10;
}

.cta-btn {
    background: var(--green);
    color: #fff !important;
    padding: 14px 32px;
    font-size: 16px;
    position: relative;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
    border-radius: 999px;
    font-weight: 700;
}

.cta-btn:hover {
    background: var(--green-dark);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3);
}

/* FAQ */
.faq-section {
    max-width: 860px;
    margin: 0 auto;
    padding-top: 80px;
    padding-bottom: 80px;
}

.faq-head {
    margin-bottom: 48px;
}

.faq-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 12px;
}

.faq-subtitle {
    font-size: 1rem;
    color: #718096;
    max-width: 600px;
    margin: 0 auto;
}

.faq-item {
    border-bottom: 1px solid #f0f0f0;
}

.faq-item:first-child {
    border-top: 1px solid #f0f0f0;
}

.faq-question {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 0;
    background: none;
    border: none;
    font-size: 1.05rem;
    font-weight: 600;
    color: #3b82f6;
    cursor: pointer;
    text-align: left;
    transition: color 0.2s;
    font-family: inherit;
}

.faq-question:hover {
    color: #2563eb;
}

.faq-q-text {
    padding-right: 24px;
}

.faq-icon {
    font-size: 1.5rem;
    font-weight: 400;
    line-height: 1;
    color: #3b82f6;
}

.faq-answer {
    padding-bottom: 24px;
    color: #4a5568;
    font-size: 0.95rem;
    line-height: 1.6;
}
</style>
