<template>
    <!--
        public/logo.png — logo brand Daurin (full-color).
        Path absolut "/logo.png" agar tetap benar di rute mana pun.
        `class` dari pemanggil (mis. size-8) jatuh ke <img> ini lewat attribute
        fallthrough karena ini elemen akar (single root) di Vue.
    -->
    <img src="/logo.png" alt="Daurin" class="object-contain" />
</template>
