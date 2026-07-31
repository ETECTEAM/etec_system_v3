<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { ChevronDown, ChevronRight } from "@lucide/vue";

const props = defineProps({
  menus: {
    type: Array,
    default: () => [],
  },
  activeSlug: {
    type: String,
    default: "",
  },
  activeSlugs: {
    type: Array,
    default: () => [],
  },
  homeActive: {
    type: Boolean,
    default: false,
  },
  mobile: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["navigate"]);

const visibleMenus = computed(() => props.menus.filter((menu) => menu.slug !== "home"));

function isMenuActive(menu) {
  const slugs = new Set([props.activeSlug, ...props.activeSlugs].filter(Boolean));
  return slugs.has(menu.slug) || (menu.children ?? []).some((child) => slugs.has(child.slug));
}

function closeMenu() {
  emit("navigate");
}
</script>

<template>
  <!-- Mobile Off-Canvas Menu Links -->
  <template v-if="mobile">
    <Link href="/" class="flex items-center gap-3 rounded-2xl px-4 py-3.5 text-base font-black transition-all hover:bg-[#F4F7FA] hover:text-[#1A66FF] hover:translate-x-1" :class="homeActive ? 'bg-[#E8F0FF] text-[#1A66FF] border-l-4 border-[#1A66FF]' : 'text-slate-700'" @click="closeMenu">
      Home
    </Link>

    <div v-for="item in visibleMenus" :key="item.id" class="grid gap-1 mt-1">
      <Link :href="item.url" class="flex items-center justify-between rounded-2xl px-4 py-3.5 text-base font-black transition-all hover:bg-[#F4F7FA] hover:text-[#1A66FF] hover:translate-x-1" :class="isMenuActive(item) ? 'bg-[#E8F0FF] text-[#1A66FF] border-l-4 border-[#1A66FF]' : 'text-slate-700'" @click="closeMenu">
        <span>{{ item.name }}</span>
        <ChevronRight v-if="item.children?.length" class="w-4 h-4 text-slate-400" />
      </Link>
      
      <!-- Sub-menu Items -->
      <div v-if="item.children?.length" class="grid gap-2 border-l-2 border-slate-100 ml-6 pl-4 mt-1 mb-2">
        <Link v-for="child in item.children" :key="child.id" :href="child.url" class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition hover:bg-[#F4F7FA] hover:text-[#1A66FF]" :class="isMenuActive(child) ? 'text-[#1A66FF] bg-[#E8F0FF]' : 'text-slate-600'" @click="closeMenu">
           <span class="w-1.5 h-1.5 rounded-full bg-slate-300 transition-colors" :class="isMenuActive(child) ? 'bg-[#1A66FF]' : ''"></span>
          {{ child.name }}
        </Link>
      </div>
    </div>
  </template>

  <!-- Desktop Header Menu Links -->
  <template v-else>
    <Link href="/" class="rounded-full px-5 py-2.5 text-sm font-bold transition-all hover:bg-[#1A66FF] hover:text-white" :class="homeActive ? 'bg-[#1A66FF] text-white shadow-[0_5px_15px_rgba(26,102,255,0.3)]' : 'text-slate-700'">Home</Link>

    <div v-for="item in visibleMenus" :key="item.id" class="group relative">
      <Link :href="item.url" class="inline-flex items-center gap-1.5 rounded-full px-5 py-2.5 text-sm font-bold transition-all hover:bg-[#E8F0FF] hover:text-[#1A66FF]" :class="isMenuActive(item) ? 'text-[#1A66FF] bg-[#E8F0FF]' : 'text-slate-700'">
        <span>{{ item.name }}</span>
        <ChevronDown v-if="item.children?.length" class="w-3.5 h-3.5 transition-transform group-hover:rotate-180" />
      </Link>
      
      <!-- Desktop Dropdown -->
      <div v-if="item.children?.length" class="invisible absolute left-0 top-[calc(100%+0.5rem)] z-50 min-w-[220px] rounded-2xl border border-slate-100 bg-white/95 backdrop-blur-xl p-3 opacity-0 shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] transition-all duration-300 group-hover:visible group-hover:top-full group-hover:opacity-100">
        <Link v-for="child in item.children" :key="child.id" :href="child.url" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold transition-all hover:bg-[#F4F7FA] hover:text-[#1A66FF] hover:translate-x-1" :class="isMenuActive(child) ? 'bg-[#E8F0FF] text-[#1A66FF]' : 'text-slate-600'">
          <span class="w-1.5 h-1.5 rounded-full bg-slate-200 transition-colors" :class="isMenuActive(child) ? 'bg-[#1A66FF]' : 'group-hover:bg-[#1A66FF]'"></span>
          {{ child.name }}
        </Link>
      </div>
    </div>
  </template>
</template>
