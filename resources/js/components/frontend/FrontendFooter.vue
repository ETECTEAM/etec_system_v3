<script setup>
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
</script>

<template>
  <footer class="bg-slate-950 py-12 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr_1.2fr] lg:px-8">
      <div>
        <Link href="/" class="inline-flex items-center gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#25258f] text-xs font-black text-white">
            <img v-if="settings.logo_url" :src="settings.logo_url" :alt="settings.school_name" class="h-full w-full object-contain" />
            <span v-else>ETEC</span>
          </span>
          <span class="text-lg font-black text-white">{{ settings.school_name || "ETEC Center" }}</span>
        </Link>
        <p class="mt-3 leading-7">Practical IT skills for web, mobile, networking, design, and office work.</p>
        <div class="mt-4 flex gap-3">
          <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 font-black text-white hover:bg-[#f4a261] hover:text-slate-950">f</a>
          <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 font-black text-white hover:bg-[#f4a261] hover:text-slate-950">x</a>
          <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 font-black text-white hover:bg-[#f4a261] hover:text-slate-950">in</a>
        </div>
      </div>

      <div>
        <h4 class="font-black text-white">Quick Links</h4>
        <nav class="mt-3 grid gap-2">
          <Link v-for="item in menus.slice(0, 5)" :key="item.id" :href="item.url" class="hover:text-[#f4a261]">
            {{ item.name }}
          </Link>
        </nav>
      </div>

      <div>
        <h4 class="font-black text-white">Courses</h4>
        <p class="mt-3 leading-7">Web Development<br />Mobile App<br />Computer Networking</p>
      </div>

      <div>
        <h4 class="font-black text-white">Contact</h4>
        <p class="mt-3 leading-7">+855 12 345 678<br />info@etec-center.edu.kh<br />Phnom Penh, Cambodia</p>
        <form class="mt-4 flex gap-2">
          <input type="email" class="min-w-0 flex-1 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm text-white placeholder:text-slate-400" placeholder="Email address" />
          <button type="button" class="rounded-full bg-[#f4a261] px-4 py-2 text-sm font-black text-slate-950">Join</button>
        </form>
      </div>
    </div>
    <div class="mx-auto mt-8 max-w-7xl border-t border-white/10 px-4 pt-6 text-center text-sm sm:px-6 lg:px-8">
      © 2026 {{ settings.school_name || "ETEC Center" }}. All rights reserved.
    </div>
  </footer>
</template>
