<script setup>
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Menu, X } from "@lucide/vue";

import FrontendMenuLinks from "@/components/frontend/FrontendMenuLinks.vue";

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },

  menus: {
    type: Array,
    default: () => [],
  },

  activePage: {
    type: String,
    default: "",
  },
});

const menuOpen = ref(false);

const schoolName = computed(
  () => props.settings?.school_name || "ETEC Center",
);
</script>

<template>
  <header
    class="fixed left-0 right-0 top-0 z-40 border-b border-slate-200/70 bg-white/90 shadow-sm backdrop-blur-xl"
  >
    <div
      class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
    >
      <Link
        href="/"
        class="flex min-w-0 items-center gap-3 rounded-full pr-2 transition hover:opacity-90"
      >
        <span
          class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#25258f] text-xs font-black text-white shadow-sm"
        >
          <img
            v-if="settings.logo_url"
            :src="settings.logo_url"
            :alt="schoolName"
            class="h-full w-full object-contain"
          />

          <span v-else>ETEC</span>
        </span>

        <span
          class="max-w-[12rem] truncate text-sm font-black leading-tight text-slate-950 sm:max-w-xs sm:text-base"
        >
          {{ schoolName }}
        </span>
      </Link>

      <nav class="hidden items-center gap-1 lg:flex">
        <FrontendMenuLinks
          :menus="menus"
          :active-page="activePage"
        />
      </nav>

      <button
        type="button"
        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden"
        :aria-expanded="menuOpen"
        aria-label="Toggle navigation menu"
        @click="menuOpen = !menuOpen"
      >
        <X v-if="menuOpen" class="h-5 w-5" />
        <Menu v-else class="h-5 w-5" />
      </button>
    </div>

    <nav
      v-if="menuOpen"
      class="mx-4 mb-4 grid gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl lg:hidden"
    >
      <FrontendMenuLinks
        :menus="menus"
        :active-page="activePage"
        mobile
        @navigate="menuOpen = false"
      />
    </nav>
  </header>
</template>