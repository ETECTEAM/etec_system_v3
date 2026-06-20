<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

import Create from './Create.vue'
import Edit from './Edit.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'

// PROPS
const props = defineProps({
  times: Object,
  filters: Object,
})

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
  if (confirm('Are you sure you want to delete this time?')) {
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
        <PageHero eyebrow="Times Management" title="Times" description="Read, create, update, and delete times records " />

      <!-- CARD -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">

        <!-- HEADER -->
        <div class="border-b border-slate-200 px-6 py-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-3 items-center w-full lg:w-[80%]">
               <!-- SEARCH -->
              <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Search times..."
                  class="w-[30%] rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                />

              <select
                  v-model="filters.term_id"
                  class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                >
                  <option value="">All Terms</option>

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
              class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-950"
            >
              Create Time
            </button>
          </div>

        </div>

        <!-- TABLE -->
        <div class="relative">
          <table class="w-full text-sm">

            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-slate-600">ID</th>
                <th class="px-6 py-3 text-left text-slate-600">Time Name</th>
                <th class="px-6 py-3 text-left text-slate-600">Term</th>
                <th class="px-6 py-3 text-right text-slate-600">Actions</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="time in times.data"
                :key="time.id"
                class="border-t border-slate-200 hover:bg-slate-50 transition"
              >
                <td class="px-6 py-4 text-slate-500">{{ time.id }}</td>

                <td class="px-6 py-4 font-medium text-slate-900">
                  {{ time.time_name }}
                </td>

                <td class="px-6 py-4 text-slate-600">
                  {{ time.term?.term_name || '-' }}
                </td>

                <td class="px-6 py-4 text-right space-x-2">

                  <button
                    @click="openEditModal(time)"
                    class="px-5 py-2 text-sm rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition"
                  >
                    Edit
                  </button>

                  <button
                    @click="deleteTime(time.id)"
                    class="px-5 py-2 text-sm rounded-lg bg-red-700 text-white hover:bg-red-800 transition"
                  >
                    Delete
                  </button>

                </td>
              </tr>

              <tr v-if="!times?.data?.length">
                <td colspan="4" class="py-10 text-center text-slate-500">
                  {{ search ? `No results for "${search}"` : 'No times found.' }}
                </td>
              </tr>

            </tbody>

          </table>
        </div>

        <!-- FOOTER -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

          <p class="text-sm text-slate-500">
            Showing {{ times.from }}–{{ times.to }} of {{ times.total }} times
          </p>

          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in times.links"
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
        <div class="bg-white rounded-xl w-full max-w-lg p-6 shadow-lg relative">
          <button @click="closeEditModal" class="absolute top-3 right-3 text-gray-400 hover:text-black">
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