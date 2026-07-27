<script setup>
import { Link } from "@inertiajs/vue3";
import DashboardLayout from "@/layouts/DashboardLayout.vue";
import Breadcrumbs from "@/components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "@/components/ui/page-hero/PageHero.vue";

defineProps({
  pageData: Object,
});

const breadcrumbs = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Page Management", href: "/dashboard/website/pages" },
  { label: "View Page", current: true },
];
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHero eyebrow="Website Management" :title="pageData.title" :description="`/${pageData.slug}`" />

      <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="prose max-w-none dark:prose-invert" v-html="pageData.content || '<p>No content yet.</p>'"></div>
        </article>
        <aside class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="font-bold text-slate-900 dark:text-gray-100">Page Details</p>
            <dl class="mt-4 space-y-3 text-slate-600 dark:text-gray-300">
              <div class="flex justify-between gap-3"><dt>Status</dt><dd class="font-semibold">{{ pageData.is_active ? "Active" : "Inactive" }}</dd></div>
              <div class="flex justify-between gap-3"><dt>Hero</dt><dd class="font-semibold">{{ pageData.hero?.is_active ? "Enabled" : "Disabled" }}</dd></div>
              <div class="flex justify-between gap-3"><dt>Menus</dt><dd class="font-semibold">{{ pageData.menus?.length ?? 0 }}</dd></div>
            </dl>
          </div>
          <div class="flex flex-col gap-2">
            <Link :href="`/dashboard/website/pages/${pageData.id}/preview`" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-center text-sm font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">Preview Public Page</Link>
            <Link :href="`/dashboard/website/pages/${pageData.id}/edit`" class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700">Edit Page</Link>
          </div>
        </aside>
      </div>
    </section>
  </DashboardLayout>
</template>
