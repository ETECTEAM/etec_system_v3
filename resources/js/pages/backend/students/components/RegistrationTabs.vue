<script setup>
import { Crown, CreditCard, Users } from "@lucide/vue";

defineProps({
  modelValue: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["update:modelValue"]);

const tabs = [
  { key: "vip", label: "VIP Class", icon: Crown },
  { key: "manual", label: "Manual Register", icon: CreditCard },
  { key: "class", label: "Register to Class", icon: Users },
];
</script>

<template>
  <div class="-mx-1 overflow-x-auto px-1">
    <div
      class="inline-flex min-w-full gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-gray-800 dark:bg-gray-800/60 sm:min-w-0"
      role="tablist"
    >
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        role="tab"
        :aria-selected="modelValue === tab.key"
        @click="emit('update:modelValue', tab.key)"
        :class="[
          'inline-flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition',
          modelValue === tab.key
            ? 'bg-blue-600 text-white shadow-sm'
            : 'text-slate-600 hover:bg-white hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-white',
        ]"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ $t(tab.label) }}
      </button>
    </div>
  </div>
</template>
