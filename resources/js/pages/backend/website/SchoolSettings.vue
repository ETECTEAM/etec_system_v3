<script setup>
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

const page = usePage();
const settings = computed(() => page.props.settings ?? {});
const logoPreview = ref(settings.value.logo_url ?? "");

const form = useForm({
  _method: "put",
  school_name: settings.value.school_name ?? "",
  school_logo: null,
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Website Management", current: true },
  { label: "School Settings", current: true },
];

function chooseLogo(event) {
  const file = event.target.files?.[0] ?? null;
  form.school_logo = file;
  logoPreview.value = file ? URL.createObjectURL(file) : settings.value.logo_url ?? "";
}

function submit() {
  form.post("/dashboard/website/school-settings", {
    forceFormData: true,
    preserveScroll: true,
  });
}

function removeLogo() {
  if (!window.confirm("Remove the current school logo?")) return;
  form.delete("/dashboard/website/school-settings/logo", {
    preserveScroll: true,
    onSuccess: () => {
      logoPreview.value = "";
      form.school_logo = null;
    },
  });
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" title="School Settings" description="Manage the public school name and logo from one place." />

      <form class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="submit">
        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
          <div class="space-y-5">
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">School Name</label>
              <input
                v-model="form.school_name"
                type="text"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                placeholder="Engineer of Technology and Electronic Center"
              />
              <p v-if="form.errors.school_name" class="mt-1 text-sm text-rose-600">{{ form.errors.school_name }}</p>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">School Logo</label>
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml"
                class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                @change="chooseLogo"
              />
              <p v-if="form.errors.school_logo" class="mt-1 text-sm text-rose-600">{{ form.errors.school_logo }}</p>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5 dark:border-gray-800">
              <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
              >
                {{ form.processing ? "Saving..." : "Save Settings" }}
              </button>
              <button
                v-if="settings.logo_url"
                type="button"
                class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                @click="removeLogo"
              >
                Remove Logo
              </button>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-gray-800 dark:bg-gray-800/40">
            <p class="mb-4 text-sm font-semibold text-slate-700 dark:text-gray-200">Brand Preview</p>
            <div class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900">
              <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl bg-blue-100 text-lg font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                <img v-if="logoPreview" :src="logoPreview" :alt="form.school_name" class="h-full w-full object-contain" />
                <span v-else>ET</span>
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ form.school_name || "School Name" }}</p>
                <p class="text-xs text-slate-500 dark:text-gray-400">Public header, footer, and admin header</p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </section>
  </DashboardLayout>
</template>
