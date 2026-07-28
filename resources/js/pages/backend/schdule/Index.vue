<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import { useI18n } from '../../../i18n'

const { t } = useI18n()

// PROPS
const props = defineProps({
  schedules: Object,
  filters: Object,
  classTypes: Array,
  terms: Array,
  times: Array,
})

// SEARCH
const filters = ref({
  search: props.filters.search ?? '',
  class_type_id: props.filters.class_type_id ?? '',
  term_id: props.filters.term_id ?? '',
  time_id: props.filters.time_id ?? '',
  per_page: props.filters.per_page ?? 7,
})

let timeout = null

watch(filters, (value) => {
  clearTimeout(timeout)

  timeout = setTimeout(() => {
    router.get(
      '/dashboard/schdule',
      {
        search: value.search,
        class_type_id: value.class_type_id,
        term_id: value.term_id,
        time_id: value.time_id,
        per_page: value.per_page,
        page: 1,
      },
      {
        preserveState: true,
        replace: true,
        preserveScroll: true,
      }
    )
  }, 400)
}, { deep: true })

// DELETE
function deleteSchedule(id) {
  if (confirm(t('Are you sure you want to delete this schedule?'))) {
    router.delete(`/dashboard/schdule/${id}`, {
      preserveScroll: true,
    })
  }
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Schedules', current: true },
]
</script>

<template>
  <DashboardLayout>

    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Schedules Management" :title="$t('Schedules')" :description="$t('Read, create, update, and delete schedules records')" />

      <!-- CARD -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">

        <!-- HEADER -->
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">

          <div class="flex w-full flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex flex-wrap gap-3 items-center w-full lg:w-[80%]">

              <!-- SEARCH -->
              <input
                v-model="filters.search"
                type="text"
                :placeholder="$t('Search schedules...')"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm w-[25%] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
              />

              <!-- CLASS TYPE -->
              <select
                v-model="filters.class_type_id"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              >
                <option value="">{{ $t('All Class Types') }}</option>
                <option
                  v-for="ct in classTypes"
                  :key="ct.class_type_id"
                  :value="ct.class_type_id"
                >
                  {{ ct.type_name }}
                </option>
              </select>

              <!-- TERM -->
              <select
                v-model="filters.term_id"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              >
                <option value="">{{ $t('All Terms') }}</option>
                <option
                  v-for="t in terms"
                  :key="t.id"
                  :value="t.id"
                >
                  {{ t.term_name }}
                </option>
              </select>

              <!-- TIME -->
              <select
                v-model="filters.time_id"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              >
                <option value="">{{ $t('All Times') }}</option>
                <option
                  v-for="t in times"
                  :key="t.id"
                  :value="t.id"
                >
                  {{ t.time_name }}
                </option>
              </select>

              <!-- PER PAGE -->
              <select
                v-model="filters.per_page"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              >
                <option :value="5">{{ $t('Show 5') }}</option>
                <option :value="7">{{ $t('Show 7') }}</option>
                <option :value="10">{{ $t('Show 10') }}</option>
                <option :value="20">{{ $t('Show 20') }}</option>
                <option :value="50">{{ $t('Show 50') }}</option>
              </select>

            </div>

            <Link
              href="/dashboard/schdule/create"
              class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-650 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ $t('Create Schedule') }}
            </Link>
          </div>

        </div>

        <!-- TABLE -->
        <div class="relative">
          <table class="w-full text-sm">

            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('ID') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Class Type') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Term') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Time Slots') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="schdule in schedules.data"
                :key="schdule.id"
                class="border-t border-slate-200 hover:bg-slate-50 transition dark:border-gray-800 dark:hover:bg-gray-800"
              >
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">{{ schdule.id }}</td>

                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-gray-100">
                  {{ schdule.class_type?.type_name || '-' }}
                </td>

                <td class="px-6 py-4 text-slate-600 font-medium dark:text-gray-300">
                  {{ schdule.term?.term_name || '-' }}
                </td>

                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">
                  <div class="flex flex-wrap gap-1.5 max-w-md">
                    <span
                      v-for="t in schdule.times"
                      :key="t.id"
                      class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20"
                    >
                      {{ t.time_name }}
                    </span>
                    <span v-if="!schdule.times?.length" class="text-slate-400 text-xs dark:text-gray-500">
                      {{ $t('No times selected') }}
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4 text-right space-x-2">

                  <Link
                    :href="`/dashboard/schdule/${schdule.id}/edit`"
                    class="px-5 py-2 text-sm rounded-lg border border-blue-200 bg-blue-50 font-semibold text-blue-700  transition hover:bg-blue-100 inline-block dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                  >
                    {{ $t('Edit') }}
                  </Link>

                  <button
                    @click="deleteSchedule(schdule.id)"
                    class="px-5 py-2 text-sm rounded-lg border border-rose-200 bg-rose-50 font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                  >
                    {{ $t('Delete') }}
                  </button>

                </td>
              </tr>

              <tr v-if="!schedules?.data?.length">
                <td colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">
                  {{ filters.search ? $t('No results for ":search"', { search: filters.search }) : $t('No schedules found.') }}
                </td>
              </tr>

            </tbody>

          </table>
        </div>

        <!-- FOOTER -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">

          <p class="text-sm text-slate-500 dark:text-gray-400">
            {{ $t('Showing :from-:to of :total schedules', { from: schedules.from, to: schedules.to, total: schedules.total }) }}
          </p>

          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in schedules.links"
              :key="link.label"
              :href="link.url || '#'"
              v-html="link.label"
              class="px-3 py-2 rounded-lg border text-sm transition dark:border-gray-700 dark:text-gray-300"
              :class="{
                'bg-blue-600 text-white border-blue-600': link.active,
                'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active,
                'opacity-40 pointer-events-none': !link.url
              }"
            />
          </div>

        </div>

      </div>

    </section>

  </DashboardLayout>
</template>
