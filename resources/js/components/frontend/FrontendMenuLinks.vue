<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

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
  <template v-if="mobile">
    <Link href="/" class="block rounded-xl px-4 py-3 text-center text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="homeActive ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'" @click="closeMenu">Home</Link>

    <div v-for="item in visibleMenus" :key="item.id" class="grid gap-1">
      <Link :href="item.url" class="block rounded-xl px-4 py-3 text-center text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="isMenuActive(item) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'" @click="closeMenu">
        {{ item.name }}
      </Link>
      <div v-if="item.children?.length" class="grid gap-1 border-l border-slate-200 pl-3">
        <Link v-for="child in item.children" :key="child.id" :href="child.url" class="block rounded-xl px-4 py-2.5 text-center text-sm font-semibold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="isMenuActive(child) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-600'" @click="closeMenu">
          {{ child.name }}
        </Link>
      </div>
    </div>
  </template>

  <template v-else>
    <Link href="/" class="rounded-full px-4 py-2 text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="homeActive ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'">Home</Link>

    <div v-for="item in visibleMenus" :key="item.id" class="group relative">
      <Link :href="item.url" class="inline-flex items-center gap-1 rounded-full px-4 py-2 text-sm font-bold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="isMenuActive(item) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'">
        <span>{{ item.name }}</span>
        <span v-if="item.children?.length" class="text-xs leading-none">v</span>
      </Link>
      <div v-if="item.children?.length" class="invisible absolute left-0 top-full z-50 min-w-48 translate-y-2 rounded-xl border border-slate-200 bg-white p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:translate-y-1 group-hover:opacity-100">
        <Link v-for="child in item.children" :key="child.id" :href="child.url" class="block rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-[#1e5aa8]/10 hover:text-[#1e5aa8]" :class="isMenuActive(child) ? 'bg-[#1e5aa8]/10 text-[#1e5aa8]' : 'text-slate-700'">
          {{ child.name }}
        </Link>
      </div>
    </div>
  </template>
</template>
