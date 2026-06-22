<template>
    <!--
        public/logo.svg — logo brand Daurin (full-color, 960×540 / rasio 16:9).
        Path absolut "/logo.svg" agar tetap benar di rute mana pun.
        `class` dari pemanggil (mis. size-8) jatuh ke <img> ini lewat attribute
        fall-through; object-contain menjaga rasio agar tidak gepeng di kotak persegi.
    -->
    <img src="/logo.webp" alt="Daurin" class="object-contain mr-4" />
</template>
