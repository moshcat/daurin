<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { dashboard, login } from '@/routes'
import { store as registerRoute } from '@/routes/register'

const page = usePage()
const authUser = computed(() => (page.props.auth as { user?: { role?: string } } | undefined)?.user ?? null)
const isAuth = computed(() => Boolean(authUser.value))

const mobileMenuOpen = ref(false)
</script>

<template>
    <header class="lp-header">
        <div class="lp-container lp-nav">
            <Link href="/" class="brand">
                <img src="/logo.png" alt="Daurin" style="height: 32px; width: auto;" />
                <span class="brand-name">Daurin</span>
            </Link>

            <nav class="nav-center">
                <a href="/etalase">Etalase</a>
                <a href="#kontak">Kontak</a>
            </nav>

            <div class="nav-right">
                <template v-if="isAuth">
                    <Link :href="dashboard()" class="btn btn-primary">Dashboard</Link>
                </template>
                <template v-else>
                    <Link :href="login()" class="btn-text">Masuk</Link>
                    <Link :href="registerRoute.url()" class="btn btn-primary">Daftar</Link>
                </template>
                <button class="menu-toggle" aria-label="Menu" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
        </div>

        <nav v-if="mobileMenuOpen" class="mobile-menu lp-container">
            <a href="/etalase" @click="mobileMenuOpen = false">Etalase</a>
            <a href="#kontak" @click="mobileMenuOpen = false">Kontak</a>
        </nav>
    </header>
</template>

<style scoped>
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

.lp-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid #e5e7eb;
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
    text-decoration: none;
    color: #0f172a;
    font-weight: 800;
    font-size: 22px;
}

.brand-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #16a34a;
    color: #fff;
    font-size: 18px;
}

.nav-center {
    display: none;
    gap: 32px;
}

.nav-center a {
    text-decoration: none;
    color: #64748b;
    font-weight: 600;
    font-size: 15px;
    transition: color 0.15s;
}

.nav-center a:hover {
    color: #16a34a;
}

@media (min-width: 768px) {
    .nav-center {
        display: flex;
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
    background: transparent;
    border: none;
    color: #0f172a;
    cursor: pointer;
    padding: 8px;
}

@media (min-width: 768px) {
    .menu-toggle {
        display: none;
    }
}

.mobile-menu {
    display: flex;
    flex-direction: column;
    padding: 16px 24px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}

.mobile-menu a {
    padding: 12px 0;
    text-decoration: none;
    color: #0f172a;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb;
}

.mobile-menu a:last-child {
    border-bottom: none;
}

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
    text-decoration: none;
}

.btn-primary {
    background: #16a34a;
    color: #fff !important;
}

.btn-primary:hover {
    background: #15803d;
    transform: translateY(-1px);
}

.btn-text {
    font-weight: 700;
    font-size: 14px;
    color: #0f172a;
    text-decoration: none;
}

.btn-text:hover {
    color: #15803d;
}
</style>
