<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

import Create from './Create.vue'
import Edit from './Edit.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'

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
const selectedSchedule = ref(null)

function openEditModal(schdule) {
  selectedSchedule.value = schdule
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedSchedule.value = null
}

// DELETE
function deleteSchedule(id) {
  if (confirm('Are you sure you want to delete this schedule?')) {
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
      <PageHero eyebrow="Schedules Management" title="Schedules" description="Read, create, update, and delete schedules records" />

      <!-- CARD -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">

        <!-- HEADER -->
        <div class="border-b border-slate-200 px-6 py-5">
        
          <div class="flex w-full flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex flex-wrap gap-3 items-center w-full lg:w-[80%]">

              <!-- SEARCH -->
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search schedules..."
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm w-[30%]"
              />

              <!-- CLASS TYPE -->
              <select
                v-model="filters.class_type_id"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
              >
                <option value="">All Class Types</option>
                <option
                  v-for="ct in classTypes"
                  :key="ct.id"
                  :value="ct.id"
                >
                  {{ ct.type_name }}
                </option>
              </select>

              <!-- TERM -->
              <select
                v-model="filters.term_id"
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
              >
                <option value="">All Terms</option>
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
                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
              >
                <option value="">All Times</option>
                <option
                  v-for="t in times"
                  :key="t.id"
                  :value="t.id"
                >
                  {{ t.time_name }}
                </option>
              </select>

            </div>

            <button
              @click="openCreateModal"
              class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-950"
            >
              Create Schedule
            </button>
          </div>

        </div>

        <!-- TABLE -->
        <div class="relative">
          <table class="w-full text-sm">

            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-slate-600">ID</th>
                <th class="px-6 py-3 text-left text-slate-600">Class Type</th>
                <th class="px-6 py-3 text-left text-slate-600">Term</th>
                <th class="px-6 py-3 text-left text-slate-600">Time Slots</th>
                <th class="px-6 py-3 text-right text-slate-600">Actions</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="schdule in schedules.data"
                :key="schdule.id"
                class="border-t border-slate-200 hover:bg-slate-50 transition"
              >
                <td class="px-6 py-4 text-slate-500">{{ schdule.id }}</td>

                <td class="px-6 py-4 font-semibold text-slate-900">
                  {{ schdule.class_type?.type_name || '-' }}
                </td>

                <td class="px-6 py-4 text-slate-600 font-medium">
                  {{ schdule.term?.term_name || '-' }}
                </td>

                <td class="px-6 py-4 text-slate-600">
                  <div class="flex flex-wrap gap-1.5 max-w-md">
                    <span
                      v-for="t in schdule.times"
                      :key="t.id"
                      class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"
                    >
                      {{ t.time_name }}
                    </span>
                    <span v-if="!schdule.times?.length" class="text-slate-400 text-xs">
                      No times selected
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4 text-right space-x-2">

                  <button
                    @click="openEditModal(schdule)"
                    class="px-5 py-2 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition"
                  >
                    Edit
                  </button>

                  <button
                    @click="deleteSchedule(schdule.id)"
                    class="px-5 py-2 text-sm rounded-lg bg-red-700 text-white hover:bg-red-800 transition"
                  >
                    Delete
                  </button>

                </td>
              </tr>

              <tr v-if="!schedules?.data?.length">
                <td colspan="5" class="py-10 text-center text-slate-500">
                  {{ search ? `No results for "${search}"` : 'No schedules found.' }}
                </td>
              </tr>

            </tbody>

          </table>
        </div>

        <!-- FOOTER -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

          <p class="text-sm text-slate-500">
            Showing {{ schedules.from }}–{{ schedules.to }} of {{ schedules.total }} schedules
          </p>

          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in schedules.links"
              :key="link.label"
              :href="link.url || '#'"
              v-html="link.label"
              class="px-3 py-2 rounded-lg border text-sm transition"
              :class="{
                'bg-blue-600 text-white border-blue-600': link.active,
                'hover:bg-gray-100': !link.active,
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
        <div class="bg-white rounded-xl w-full max-w-lg p-6 shadow-lg relative">
          <button @click="closeCreateModal" class="absolute top-3 right-3 text-gray-400 hover:text-black">
            ✖
          </button>

          <Create
            :classTypes="classTypes"
            :terms="terms"
            :times="times"
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
        <div class="bg-white rounded-xl w-full max-w-lg p-6 shadow-lg relative">
          <button @click="closeEditModal" class="absolute top-3 right-3 text-gray-400 hover:text-black">
            ✖
          </button>

          <Edit
            v-if="selectedSchedule"
            :schdule="selectedSchedule"
            :classTypes="classTypes"
            :terms="terms"
            :times="times"
            @close="closeEditModal"
          />
        </div>
      </div>
    </transition>

  </DashboardLayout>
</template>
