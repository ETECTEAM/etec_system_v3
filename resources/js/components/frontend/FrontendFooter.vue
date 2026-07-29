<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  menus: {
    type: Array,
    default: () => [],
  },
});

const currentYear = computed(() => new Date().getFullYear());
</script>

<template>
  <footer class="bg-slate-950 py-12 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 md:grid-cols-[1.4fr_1fr] lg:px-8">
      <div>
        <Link href="/" class="inline-flex items-center gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#25258f] text-xs font-black text-white">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.school_name" class="h-full w-full object-contain" />
            <span v-else>ETEC</span>
          </span>
          <span class="text-lg font-black text-white">{{ settings.school_name || "ETEC Center" }}</span>
        </Link>
      </div>

      <div>
        <h4 class="font-black text-white">Quick Links</h4>
        <nav class="mt-3 grid gap-2">
          <div v-for="item in menus" :key="item.id" class="grid gap-1">
            <Link :href="item.url" class="hover:text-[#f4a261]">
              {{ item.name }}
            </Link>
            <Link v-for="child in item.children ?? []" :key="child.id" :href="child.url" class="pl-3 text-sm text-slate-400 hover:text-[#f4a261]">
              {{ child.name }}
            </Link>
          </div>
        </nav>
      </div>
    </div>
    <div class="mx-auto mt-8 max-w-7xl border-t border-white/10 px-4 pt-6 text-center text-sm sm:px-6 lg:px-8">
      © {{ currentYear }} {{ settings.school_name || "ETEC Center" }}. All rights reserved.
    </div>
  </footer>
</template>
