<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

const props = defineProps({
  classList: Object,
});

const classListId = computed(() => props.classList?.id ?? props.classList?.class_list_id);
const classListLabel = computed(() => classListId.value ?? 'Unknown');
const classListDeleteHref = computed(() => classListId.value ? `/dashboard/class-list/${classListId.value}` : null);

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', href: '/dashboard/class-list' },
  { label: 'Delete', current: true },
];

const confirmDelete = () => {
  if (!classListDeleteHref.value) {
    return;
  }

  if (!confirm('Permanently delete this class?')) {
    return;
  }

  router.delete(classListDeleteHref.value, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Delete Class" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Management"
        title="Delete Class"
        description="Confirm permanent removal of this class entry."
      />

      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4 text-sm text-slate-600">
          <p>
            You are about to delete class ID <span class="font-semibold text-slate-900">{{ classListLabel.value }}</span>.
            This action cannot be undone.
          </p>
          <div class="rounded-2xl bg-slate-50 p-4">
            <div class="text-xs uppercase text-slate-400">Class type</div>
            <div class="mt-1 font-semibold text-slate-900">{{ props.classList?.class_type?.type_name || '—' }}</div>
          </div>
          <div class="rounded-2xl bg-slate-50 p-4">
            <div class="text-xs uppercase text-slate-400">Schedule</div>
            <div class="mt-1 text-slate-900">{{ props.classList?.term?.term_name || '—' }} · {{ props.classList?.time?.time_name || '—' }}</div>
          </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
          <Link
            href="/dashboard/class-list"
            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Cancel
          </Link>
          <button
            @click="confirmDelete"
            class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700"
          >
            Delete permanently
          </button>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
