<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const props = defineProps({
  menu: {
    type: Object,
    default: null,
  },
  pages: {
    type: Array,
    default: () => [],
  },
});

const isEditing = computed(() => Boolean(props.menu));
const form = useForm({
  name: props.menu?.name ?? "",
  page_id: props.menu?.page_id ?? props.menu?.page?.id ?? "",
  is_active: props.menu?.is_active ?? true,
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "Menu Management", href: "/dashboard/website/menus" },
  { label: isEditing.value ? "Edit Menu" : "Create Menu", current: true },
];

function submit() {
  if (isEditing.value) {
    form.put(`/dashboard/website/menus/${props.menu.id}`);
    return;
  }

  form.post("/dashboard/website/menus");
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero
        eyebrow="Website Management"
        :title="isEditing ? $t('Edit Menu') : $t('Create Menu')"
        :description="$t('Connect a public navigation item to one dynamic page.')"
      />

      <form class="w-full rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="submit">
        <div class="space-y-5">
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Menu Name') }}</label>
            <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" :placeholder="$t('About Us')" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Connected Page') }}</label>
            <select v-model="form.page_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
              <option value="">{{ $t('Select Page') }}</option>
              <option v-for="page in pages" :key="page.id" :value="page.id">
                {{ page.title }} /{{ page.slug }}{{ page.is_active ? "" : ` (${$t('inactive')})` }}
              </option>
            </select>
            <p v-if="form.errors.page_id" class="mt-1 text-sm text-rose-600">{{ form.errors.page_id }}</p>
          </div>

          <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/50">
            <span>
              <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ $t('Active Menu') }}</span>
              <span class="block text-xs text-slate-500 dark:text-gray-400">{{ $t('Inactive menus stay in admin but disappear from public navigation.') }}</span>
            </span>
            <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          </label>
        </div>

        <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
          <Link href="/dashboard/website/menus" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200">{{ $t('Cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
            {{ form.processing ? $t("Saving...") : isEditing ? $t("Update Menu") : $t("Create Menu") }}
          </button>
        </div>
      </form>
    </section>
  </DashboardLayout>
</template>
