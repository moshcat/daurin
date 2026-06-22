<script setup lang="ts">
import { Head, Link, WhenVisible, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import {
    productFromBahanBaku,
    productFromBahanJadi,
    productFromListing,
    formatRp
} from '@/lib/listing'
import type {BahanBakuItem, BahanJadiItem, ListingItem, MarketProduct} from '@/lib/listing';
import { dashboard, login } from '@/routes'
import { store as registerRoute } from '@/routes/register'

const props = defineProps<{
    listings: ListingItem[]
    bahanBaku: BahanBakuItem[]
    bahanJadi: BahanJadiItem[]
    pagination: { page: number; hasMore: boolean }
    stats: { listingCount: number; bahanBakuCount: number; bahanJadiCount: number }
}>()

const sampahProducts = computed(() => props.listings.map(productFromListing))
const bahanBakuProducts = computed(() => props.bahanBaku.map(productFromBahanBaku))
const bahanJadiProducts = computed(() => props.bahanJadi.map(productFromBahanJadi))

const page = usePage()
const isAuth = computed(() => Boolean((page.props.auth as { user?: unknown } | undefined)?.user))

// CTA "Beli" tiap kartu: bahan baku yang dilelang → ruang lelang (jalur beli nyata);
// lainnya → login (tamu) atau dashboard (sudah login) untuk lanjut transaksi.
function ctaLabel(product: MarketProduct): string {
    if (product.lelangId) {
        return 'Nego Harga'
    }
    return isAuth.value ? 'Beli' : 'Masuk untuk Beli'
}

function ctaHref(product: MarketProduct): string {
    if (product.lelangId) {
        return `/lelang/${product.lelangId}`
    }
    return isAuth.value ? '/dashboard' : '/login'
}

// Scroll logic for header
const isScrolled = ref(false)
let lastScroll = 0
const handleScroll = () => {
    const currentScroll = window.scrollY
    if (currentScroll > 60 && currentScroll > lastScroll) {
        isScrolled.value = true
    } else {
        isScrolled.value = false
    }
    lastScroll = currentScroll
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
})
onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <Head title="Daurin - Marketplace Daur Ulang">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  </Head>

  <div class="daurin-landing">
    <!-- Top Bar & Main Header inside dark green wrapper -->
    <div class="header-wrapper" :class="{ 'header-scrolled': isScrolled }">
      <!-- Top Bar -->
      <div class="top-bar">
        <div class="container top-bar-inner">
          <div class="top-bar-left">
            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            Hubungi Kami : (021) 555-0123
          </div>
          <div class="top-bar-right">
            <select aria-label="Bahasa">
              <option value="id">Indonesian</option>
              <option value="en">English</option>
            </select>
            <div class="top-icons">
              <Link v-if="$page.props.auth.user" :href="dashboard()" class="icon-btn-top text-xs px-3 w-auto rounded-full text-white font-bold tracking-wider" aria-label="Dashboard">
                Dashboard
              </Link>
              <template v-else>
                <Link :href="login()" class="icon-btn-top text-xs px-3 w-auto rounded-full text-white font-bold tracking-wider" aria-label="Masuk">
                  Masuk
                </Link>
                <Link :href="registerRoute.url()" class="icon-btn-top text-xs px-3 w-auto rounded-full bg-bright-green text-dark-green font-bold tracking-wider hover:bg-bright-green-hover" style="background-color: var(--color-bright-green); color: var(--color-dark-green);" aria-label="Daftar">
                  Daftar
                </Link>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Header -->
      <header class="container">
        <div class="main-header">
          <Link href="/" class="logo">
            <img src="/logo.png" alt="Daurin Logo" style="height: 32px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
          </Link>

          <div class="nav-pill">
            <div class="nav-left-section">
              <button class="btn-categories">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="3" y1="12" x2="21" y2="12"></line>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                Kategori
              </button>
              <nav class="main-nav-links">
                <Link href="/" class="nav-link active">Beranda</Link>
                <a href="#etalase" class="nav-link">Etalase</a>
                <a href="#" class="nav-link">Cara Kerja</a>
                <a href="#" class="nav-link">Mitra Pengepul</a>
              </nav>
            </div>

            <div class="search-wrapper">
              <input type="text" class="search-input" placeholder="Cari material daur ulang...">
              <button type="submit" class="search-btn" aria-label="Cari">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </header>
    </div>

    <main>
      <!-- Hero Section -->
      <section class="hero">
        <img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="Daur Ulang" class="hero-bg-img"
          style="background: url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&q=80&w=800') center/contain no-repeat; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.1));">

        <div class="container relative">
          <div class="hero-content">
            <h1 class="hero-title">Bahan Baku Dari Sampah Terpilah</h1>
            <p class="hero-desc">Platform yang menghubungkan rumah tangga, pengepul, dan industri untuk mengubah sisa konsumsi menjadi bahan baku berkualitas.</p>
            <a href="#etalase" class="btn-shop">Lihat Etalase</a>
          </div>
        </div>
      </section>

      <!-- Features Section -->
      <section class="features-section">
        <div class="container">
          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-icon-container">
                <svg class="feature-svg svg-pickup" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M42 20H48L56 28V46H50" stroke="var(--color-bright-green)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M12 46H8V18H42V46H38" stroke="var(--color-dark-green)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                  <circle cx="18" cy="46" r="6" stroke="var(--color-dark-green)" stroke-width="3" fill="var(--color-surface)" />
                  <circle cx="44" cy="46" r="6" stroke="var(--color-bright-green-hover)" stroke-width="3" fill="var(--color-surface)" />
                  <line x1="2" y1="26" x2="6" y2="26" stroke="var(--color-bright-green)" stroke-width="3" stroke-linecap="round" />
                  <line x1="0" y1="34" x2="4" y2="34" stroke="var(--color-bright-green)" stroke-width="3" stroke-linecap="round" />
                </svg>
              </div>
              <div class="feature-card-content">
                <h3>Jemput Gratis</h3>
                <p>Berlaku untuk pengambilan sampah terpilah rumah tangga dengan berat minimum tertentu di area cakupan kami.</p>
              </div>
            </div>
            <div class="feature-card">
              <div class="feature-icon-container">
                <svg class="feature-svg svg-payment" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M32 6C32 6 48 10 48 22V38C48 48 32 58 32 58C32 58 16 48 16 38V22C16 10 32 6 32 6Z" stroke="var(--color-dark-green)" stroke-width="3" stroke-linejoin="round" />
                  <rect x="24" y="22" width="22" height="15" rx="2.5" stroke="var(--color-bright-green)" stroke-width="2.5" fill="none" />
                  <path d="M24 27H46" stroke="var(--color-bright-green)" stroke-width="2.5" />
                  <circle cx="28" cy="32" r="1.5" fill="var(--color-bright-green)" />
                  <circle cx="42" cy="42" r="9" fill="var(--color-surface)" stroke="var(--color-bright-green-hover)" stroke-width="2.5" />
                  <path d="M39 42L41 44L45 40" stroke="var(--color-bright-green-hover)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <div class="feature-card-content">
                <h3>Pembayaran Aman</h3>
                <p>Kami menerima transfer bank, dompet digital, dan sistem rekber untuk transaksi industri partai besar.</p>
              </div>
            </div>
            <div class="feature-card">
              <div class="feature-icon-container">
                <svg class="feature-svg svg-quality" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="32" cy="32" r="20" stroke="var(--color-dark-green)" stroke-width="3" fill="none" />
                  <path d="M32 4L35 12L43 10L41 18L48 20L43 27L48 33L41 35L43 43L35 41L32 49L29 41L21 43L23 35L16 33L21 27L16 20L23 18L21 10L29 12Z" stroke="var(--color-bright-green)" stroke-width="2.5" stroke-linejoin="round" />
                  <path d="M25 31L30 36L39 27" stroke="var(--color-dark-green)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M24 48V58L32 53L40 58V48" stroke="var(--color-bright-green-hover)" stroke-width="3" stroke-linejoin="round" />
                </svg>
              </div>
              <div class="feature-card-content">
                <h3>Jaminan Kualitas</h3>
                <p>Bahan baku industri telah melewati proses sortir ketat dengan spesifikasi yang terjamin sesuai standar pabrik.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Showcase Container -->
      <div id="etalase" class="container">
        <!-- Lapis 1: Rumah Tangga -->
        <section class="marketplace-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <h2>Sampah Terpilah</h2>
              <p>Sisa konsumsi rumah tangga yang telah dipilah siap ambil.</p>
            </div>
          </div>
          
          <div v-if="sampahProducts.length === 0" class="py-[40px] text-center text-gray-500">
            Belum ada listing sampah.
          </div>
          <div v-else class="product-grid">
            <article class="product-card" v-for="product in sampahProducts" :key="product.id">
              <div class="product-img-wrap">
                <img :src="product.image" :alt="product.title" class="product-img">
                <span class="badge-type">{{ product.badge }}</span>
              </div>
              <div class="product-info">
                <h3 class="product-title">{{ product.title }}</h3>
                <div class="product-price">{{ formatRp(product.price) }}</div>
                <div class="product-weight">{{ product.subtitle }}</div>
                <div class="product-meta">
                  <span class="meta-location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    Tersedia
                  </span>
                  <span class="product-status status-tersedia">Tersedia</span>
                </div>
                <Link :href="ctaHref(product)" class="btn-buy">{{ ctaLabel(product) }}</Link>
              </div>
            </article>
          </div>
          <WhenVisible
              v-if="pagination.hasMore"
              always
              :params="{
                  only: ['listings', 'pagination'],
                  data: { page: pagination.page + 1 },
                  preserveUrl: true,
              }"
          >
              <div class="mt-8 text-center text-gray-500">
                  Memuat lebih banyak...
              </div>
          </WhenVisible>
        </section>

        <!-- Lapis 2: Pengepul -->
        <section class="marketplace-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <h2>Bahan Baku Grosir</h2>
              <p>Dari mitra pengepul siap angkut untuk industri.</p>
            </div>
          </div>
          
          <div v-if="bahanBakuProducts.length === 0" class="py-[40px] text-center text-gray-500">
            Belum ada bahan baku tersedia.
          </div>
          <div v-else class="product-grid">
            <article class="product-card" v-for="product in bahanBakuProducts" :key="product.id">
              <div class="product-img-wrap">
                <img :src="product.image" :alt="product.title" class="product-img">
                <span class="badge-type">{{ product.badge }}</span>
              </div>
              <div class="product-info">
                <h3 class="product-title">{{ product.title }}</h3>
                <div class="product-price">{{ formatRp(product.price) }}</div>
                <div class="product-weight">{{ product.subtitle }}</div>
                <div class="product-meta">
                  <span class="meta-location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    Tersedia
                  </span>
                  <span class="product-status status-tersedia">Tersedia</span>
                </div>
                <Link :href="ctaHref(product)" class="btn-buy">{{ ctaLabel(product) }}</Link>
              </div>
            </article>
          </div>
        </section>

        <!-- Lapis 3: Industri Pengolah -->
        <section class="marketplace-section">
          <div class="section-header">
            <div class="section-title-wrap">
              <h2>Bahan Baku Jadi</h2>
              <p>Keluaran industri pengolah siap pakai.</p>
            </div>
          </div>

          <div v-if="bahanJadiProducts.length === 0" class="py-[40px] text-center text-gray-500">
            Belum ada bahan baku jadi.
          </div>
          <div v-else class="product-grid">
            <article class="product-card" v-for="product in bahanJadiProducts" :key="product.id">
              <div class="product-img-wrap">
                <img :src="product.image" :alt="product.title" class="product-img">
                <span class="badge-type">{{ product.badge }}</span>
              </div>
              <div class="product-info">
                <h3 class="product-title">{{ product.title }}</h3>
                <div class="product-price">{{ formatRp(product.price) }}</div>
                <div class="product-weight">{{ product.subtitle }}</div>
                <div class="product-meta">
                  <span class="meta-location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    Tersedia
                  </span>
                  <span class="product-status status-tersedia">Tersedia</span>
                </div>
                <Link :href="ctaHref(product)" class="btn-buy">{{ ctaLabel(product) }}</Link>
              </div>
            </article>
          </div>
        </section>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-col">
            <div class="logo" style="margin-bottom: 20px;">
              <img src="/logo.png" alt="Daurin Logo" style="height: 32px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
            </div>
            <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6;">Platform marketplace daur ulang B2B & B2C terdepan. Menghubungkan rumah tangga, pengepul, dan industri untuk masa depan lebih hijau.</p>
          </div>
          <div class="footer-col">
            <h4>Kategori</h4>
            <ul>
              <li><a href="#">Kertas & Kardus</a></li>
              <li><a href="#">Plastik (PET/HDPE)</a></li>
              <li><a href="#">Logam & Kaleng</a></li>
              <li><a href="#">Limbah Elektronik</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Informasi</h4>
            <ul>
              <li><a href="#">Tentang Kami</a></li>
              <li><a href="#">Cara Kerja</a></li>
              <li><a href="#">Pengiriman (Jemput)</a></li>
              <li><a href="#">Kebijakan Privasi</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Hubungi Kami</h4>
            <ul>
              <li style="color: #cbd5e1;">Jl. Lingkungan Hijau No. 12, Jakarta</li>
              <li><a href="#">support@daurin.id</a></li>
              <li style="color: var(--color-bright-green); font-weight: bold;">(021) 555-0123</li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          &copy; 2026 Daurin Marketplace. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.daurin-landing {
  /* Organic / Green Theme Colors matching reference */
  --color-dark-green: #0a4226;
  --color-bright-green: #4ade80;
  --color-bright-green-hover: #22c55e;
  --color-text-main: #1f2937;
  --color-text-muted: #6b7280;
  --color-bg: #fdfdfd;
  --color-surface: #ffffff;
  --color-border: #e5e7eb;
  --color-status-tersedia: #10b981;
  --color-status-diambil: #f59e0b;
  --color-status-terjual: #6b7280;
  --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

  font-family: var(--font-family);
  background-color: var(--color-bg);
  color: var(--color-text-main);
  line-height: 1.5;
  font-size: 14px;
  -webkit-font-smoothing: antialiased;
}

.daurin-landing * {
  box-sizing: border-box;
}

.daurin-landing a {
  text-decoration: none;
  color: inherit;
}

.daurin-landing ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.daurin-landing img {
  max-width: 100%;
  display: block;
}

.daurin-landing button,
.daurin-landing input {
  font-family: inherit;
  border: none;
  background: none;
}

.daurin-landing button {
  cursor: pointer;
}

.container {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Top Bar & Header Wrapper */
.header-wrapper {
  background-color: var(--color-dark-green);
  color: #fff;
  padding-bottom: 20px;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.15);
  transition: transform 0.3s ease;
  will-change: transform;
}

.header-wrapper.header-scrolled {
  transform: translateY(calc(-1 * 45px));
}

/* Top Bar */
.top-bar {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 10px 0;
  font-size: 13px;
  transition: opacity 0.3s ease;
  opacity: 1;
}

.header-wrapper.header-scrolled .top-bar {
  opacity: 0;
  pointer-events: none;
}

.top-bar-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.top-bar-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.top-bar-left .icon {
  color: var(--color-bright-green);
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.top-bar-right select {
  background: transparent;
  color: #fff;
  border: none;
  outline: none;
  cursor: pointer;
}

.top-bar-right select option {
  color: var(--color-text-main);
}

.top-icons {
  display: flex;
  align-items: center;
  gap: 16px;
}

.icon-btn-top {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  border-radius: 50%;
  transition: background-color 0.2s;
  position: relative;
}

.icon-btn-top:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

.badge-count {
  position: absolute;
  top: -4px;
  right: -4px;
  background-color: var(--color-bright-green);
  color: var(--color-dark-green);
  font-size: 10px;
  font-weight: bold;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Main Header */
.main-header {
  padding-top: 20px;
  display: flex;
  align-items: center;
  gap: 30px;
}

.logo {
  font-size: 24px;
  font-weight: 800;
  color: var(--color-bright-green);
  letter-spacing: -0.5px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo span {
  color: #fff;
}

/* Nav Pill */
.nav-pill {
  flex: 1;
  background-color: #fff;
  border-radius: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 6px 6px 12px;
  color: var(--color-text-main);
}

.nav-left-section {
  display: flex;
  align-items: center;
  gap: 20px;
}

.btn-categories {
  background-color: var(--color-bright-green);
  color: var(--color-dark-green);
  padding: 10px 20px;
  border-radius: 30px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: background-color 0.2s;
}

.btn-categories:hover {
  background-color: var(--color-bright-green-hover);
}

.main-nav-links {
  display: none;
}

@media (min-width: 992px) {
  .main-nav-links {
    display: flex;
    gap: 24px;
    font-weight: 600;
    font-size: 14px;
  }
}

.nav-link {
  color: var(--color-text-main);
  transition: color 0.2s;
}

.nav-link.active {
  color: var(--color-bright-green-hover);
}

.nav-link:hover {
  color: var(--color-bright-green-hover);
}

/* Search Bar in Header */
.search-wrapper {
  background-color: #f3f4f6;
  border-radius: 30px;
  display: flex;
  align-items: center;
  padding: 4px;
  width: 100%;
  max-width: 300px;
}

.search-input {
  flex: 1;
  padding: 8px 16px;
  font-size: 13px;
  outline: none;
}

.search-input::placeholder {
  color: #9ca3af;
}

.search-btn {
  background-color: var(--color-bright-green);
  color: var(--color-dark-green);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background-color 0.2s;
}

.search-btn:hover {
  background-color: var(--color-bright-green-hover);
}

/* Hero Banner */
.hero {
  background-color: #f8fafc;
  background-image: none;
  position: relative;
  overflow: hidden;
  padding: 60px 0;
}

@media (min-width: 768px) {
  .hero {
    padding: 100px 0;
  }
}

.hero-bg-img {
  position: absolute;
  right: -5%;
  top: 50%;
  transform: translateY(-50%);
  height: 120%;
  object-fit: contain;
  opacity: 0.9;
  pointer-events: none;
}

@media (max-width: 767px) {
  .hero-bg-img {
    opacity: 0.2;
    right: -20%;
  }
}

.hero-content {
  position: relative;
  z-index: 10;
  max-width: 600px;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--color-text-main);
  line-height: 1.2;
  margin-bottom: 20px;
  letter-spacing: -1px;
}

@media (min-width: 768px) {
  .hero-title {
    font-size: 3.5rem;
  }
}

.hero-desc {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin-bottom: 32px;
  line-height: 1.6;
}

.btn-shop {
  display: inline-flex;
  background-color: var(--color-surface);
  color: var(--color-text-main);
  padding: 14px 32px;
  border-radius: 30px;
  font-weight: 700;
  font-size: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.2s;
}

.btn-shop:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
  color: var(--color-dark-green);
}

/* Features Section Redesign */
.features-section {
  background: linear-gradient(180deg, #ffffff 0%, #f3f7f5 100%);
  padding: 80px 0;
  position: relative;
  overflow: hidden;
  border-bottom: 1px solid rgba(10, 66, 38, 0.05);
}

.features-section::before,
.features-section::after {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(74, 222, 128, 0.08) 0%, rgba(74, 222, 128, 0) 70%);
  pointer-events: none;
  z-index: 1;
}

.features-section::before {
  top: -100px;
  left: -100px;
}

.features-section::after {
  bottom: -100px;
  right: -100px;
}

.features-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 32px;
  position: relative;
  z-index: 2;
}

@media (min-width: 768px) {
  .features-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.feature-card {
  background-color: var(--color-surface);
  border: 1px solid rgba(10, 66, 38, 0.06);
  border-radius: 24px;
  padding: 40px 32px;
  box-shadow: 0 10px 30px rgba(10, 66, 38, 0.02);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 24px;
  overflow: hidden;
}

.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--color-bright-green) 0%, var(--color-bright-green-hover) 100%);
  border-radius: 0 0 4px 4px;
  transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.feature-card:hover::before {
  width: 100%;
}

.feature-card::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top right, rgba(74, 222, 128, 0.03) 0%, transparent 60%);
  opacity: 0;
  transition: opacity 0.4s ease;
  z-index: 0;
}

.feature-card:hover::after {
  opacity: 1;
}

.feature-card:hover {
  transform: translateY(-8px);
  border-color: rgba(74, 222, 128, 0.3);
  box-shadow: 0 20px 40px rgba(10, 66, 38, 0.08);
}

.feature-icon-container {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background-color: rgba(74, 222, 128, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 1;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.feature-card:hover .feature-icon-container {
  background-color: var(--color-dark-green);
  transform: scale(1.05);
}

.feature-svg {
  width: 38px;
  height: 38px;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.feature-card:hover .feature-svg path,
.feature-card:hover .feature-svg circle,
.feature-card:hover .feature-svg rect,
.feature-card:hover .feature-svg line {
  stroke: var(--color-bright-green);
}

.feature-card:hover .feature-svg circle[fill="var(--color-surface)"] {
  fill: var(--color-dark-green);
}

@keyframes truckMove {
  0% { transform: translateX(0); }
  30% { transform: translateX(-4px); }
  70% { transform: translateX(4px); }
  100% { transform: translateX(0); }
}

.feature-card:hover .svg-pickup {
  animation: truckMove 0.6s ease-in-out;
}

@keyframes cardPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.08); }
  100% { transform: scale(1); }
}

.feature-card:hover .svg-payment rect {
  animation: cardPulse 0.8s ease-in-out infinite alternate;
}

@keyframes sealRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(15deg); }
}

.feature-card:hover .svg-quality path:nth-child(2) {
  animation: sealRotate 0.5s ease-in-out forwards;
}

.feature-card-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.feature-card h3 {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-dark-green);
  transition: color 0.3s;
}

.feature-card p {
  font-size: 13.5px;
  color: var(--color-text-muted);
  line-height: 1.6;
}

/* Marketplace Section */
.marketplace-section {
  padding: 60px 0 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 30px;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 16px;
}

.section-title-wrap h2 {
  font-size: 24px;
  font-weight: 800;
  color: var(--color-text-main);
  margin-bottom: 8px;
}

.section-title-wrap p {
  color: var(--color-text-muted);
  font-size: 14px;
}

.view-all {
  color: var(--color-dark-green);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.view-all:hover {
  text-decoration: underline;
}

/* Product Grid */
.product-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

@media (min-width: 768px) {
  .product-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1024px) {
  .product-grid {
    grid-template-columns: repeat(5, 1fr);
  }
}

/* Product Card */
.product-card {
  background-color: var(--color-surface);
  border-radius: 16px;
  border: 1px solid var(--color-border);
  overflow: hidden;
  transition: all 0.3s;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  border-color: #d1d5db;
}

.product-img-wrap {
  position: relative;
  padding-top: 100%;
  background-color: #f3f4f6;
}

.product-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-type {
  position: absolute;
  top: 12px;
  left: 12px;
  background-color: var(--color-surface);
  color: var(--color-dark-green);
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.product-info {
  padding: 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.product-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-main);
  margin-bottom: 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 40px;
}

.product-price {
  font-size: 18px;
  font-weight: 800;
  color: var(--color-dark-green);
  margin-bottom: 4px;
}

.product-weight {
  font-size: 12px;
  color: var(--color-text-muted);
  margin-bottom: 12px;
}

.product-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px dashed var(--color-border);
}

.meta-location {
  font-size: 12px;
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  gap: 4px;
}

.product-status {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
}

.status-tersedia {
  background-color: #dcfce7;
  color: var(--color-status-tersedia);
}

.status-diambil {
  background-color: #fef3c7;
  color: var(--color-status-diambil);
}

.status-terjual {
  background-color: #f3f4f6;
  color: var(--color-status-terjual);
}

.btn-buy {
  margin-top: 12px;
  display: block;
  width: 100%;
  text-align: center;
  background-color: var(--color-dark-green);
  color: #fff;
  padding: 9px 0;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  transition: background-color 0.2s, color 0.2s;
}

.btn-buy:hover {
  background-color: var(--color-bright-green);
  color: var(--color-dark-green);
}

/* Footer */
.site-footer {
  background-color: var(--color-dark-green);
  color: #fff;
  padding: 60px 0 20px;
  margin-top: 60px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 40px;
  margin-bottom: 40px;
}

@media (min-width: 768px) {
  .footer-grid {
    grid-template-columns: 2fr 1fr 1fr 1fr;
  }
}

.footer-col h4 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 20px;
  color: var(--color-bright-green);
}

.footer-col ul {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-col a {
  color: #cbd5e1;
  transition: color 0.2s;
}

.footer-col a:hover {
  color: #fff;
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}
</style>
