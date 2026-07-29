<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

import Create from './Create.vue'
import Edit from './Edit.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import { useI18n } from '../../../i18n'

// PROPS
const props = defineProps({
  times: Object,
  filters: Object,
})

const { t } = useI18n()

// SEARCH
const filters = ref({
  search: props.filters.search ?? '',
  term_id: props.filters.term_id ?? '',
})

let timeout = null

watch(filters, (value) => {
  clearTimeout(timeout)

  timeout = setTimeout(() => {
    router.get(
      '/dashboard/times',
      {
        search: value.search,
        term_id: value.term_id,
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


// TERMS (for select in Create/Edit)
const page = usePage()
const terms = page.props.terms

// CREATE MODAL
const showCreateModal = ref(false)

function openCreateModal() {
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

// EDIT MODAL
const showEditModal = ref(false)
const selectedTime = ref(null)

function openEditModal(time) {
  selectedTime.value = time
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedTime.value = null
}

// DELETE
function deleteTime(id) {
  if (confirm(t('Are you sure you want to delete this time?'))) {
    router.delete(`/dashboard/times/${id}`, {
      preserveScroll: true,
    })
  }
}
const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Times', current: true },
]
</script>
<template>
  <DashboardLayout>

    <section class="space-y-6">
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero eyebrow="Times Management" :title="$t('Times')" :description="$t('Read, create, update, and delete times records.')" />

      <!-- CARD -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">

        <!-- HEADER -->
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-3 items-center w-full lg:w-[80%]">
               <!-- SEARCH -->
              <input
                  v-model="filters.search"
                  type="text"
                  :placeholder="$t('Search times...')"
                  class="w-[30%] rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500"
                />

              <select
                  v-model="filters.term_id"
                  class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                  <option value="">{{ $t('All Terms') }}</option>

                  <option
                    v-for="term in terms"
                    :key="term.id"
                    :value="term.id"
                  >
                    {{ term.term_name }}
                  </option>
                </select>
            </div>

            <button
              @click="openCreateModal"
              class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-650 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ $t('Create Time') }}
            </button>
          </div>

        </div>

        <!-- TABLE -->
        <div class="relative">
          <table class="w-full text-sm">

            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('ID') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Time Name') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Term') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="time in times.data"
                :key="time.id"
                class="border-t border-slate-200 hover:bg-slate-50 transition dark:border-gray-800 dark:hover:bg-gray-800"
              >
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">{{ time.id }}</td>

                <td class="px-6 py-4 font-medium text-slate-900 dark:text-gray-100">
                  {{ time.time_name }}
                </td>

                <td class="px-6 py-4 text-slate-600 dark:text-gray-300">
                  {{ time.term?.term_name || '-' }}
                </td>

                <td class="px-6 py-4 text-right space-x-2">

                  <button
                    @click="openEditModal(time)"
                    class="px-5 py-2 text-sm rounded-lg  border border-blue-200 bg-blue-50 font-semibold text-blue-700  transition hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                  >
                    {{ $t('Edit') }}
                  </button>

                  <button
                    @click="deleteTime(time.id)"
                    class="px-5 py-2 text-sm rounded-lg border border-rose-200 bg-rose-50 font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                  >
                    {{ $t('Delete') }}
                  </button>

                </td>
              </tr>

              <tr v-if="!times?.data?.length">
                <td colspan="4" class="py-10 text-center text-slate-500 dark:text-gray-400">
                  {{ filters.search ? $t('No results for ":search"', { search: filters.search }) : $t('No times found.') }}
                </td>
              </tr>

            </tbody>

          </table>
        </div>

        <!-- FOOTER -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">

          <p class="text-sm text-slate-500 dark:text-gray-400">
            {{ $t('Showing :from-:to of :total times', { from: times.from, to: times.to, total: times.total }) }}
          </p>

          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in times.links"
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

    <!-- CREATE MODAL -->
    <transition name="fade">
      <div
        v-if="showCreateModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="closeCreateModal"
      >
        <div class="bg-white rounded-xl w-full max-w-lg p-6 shadow-lg relative dark:bg-gray-900">
          <button @click="closeCreateModal" class="absolute top-3 right-3 text-gray-400 hover:text-black dark:text-gray-500 dark:hover:text-gray-100">
            ✖
          </button>

          <Create
            :terms="terms"
            @close="closeCreateModal"
          />
        </div>
      </div>
    </transition>

    <!-- EDIT MODAL -->
    <transition name="fade">
      <div
        v-if="showEditModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="closeEditModal"
      >
        <div class="bg-white rounded-xl w-full max-w-lg p-6 shadow-lg relative dark:bg-gray-900">
          <button @click="closeEditModal" class="absolute top-3 right-3 text-gray-400 hover:text-black dark:text-gray-500 dark:hover:text-gray-100">
            ✖
          </button>

          <Edit
            v-if="selectedTime"
            :time="selectedTime"
            :terms="terms"
            @close="closeEditModal"
          />
        </div>
      </div>
    </transition>

  </DashboardLayout>
</template>
