<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted } from 'vue'
import Edit from './Edit.vue'
import Create from './Create.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  terms: Object,
  filters: Object,
})

const search = ref(props.filters.search ?? '')
let timeout = null

watch(search, (value) => {
  clearTimeout(timeout)

  timeout = setTimeout(() => {
    router.get(
      '/dashboard/terms',
      {
        search: value,
        page: 1,
      },
      {
        preserveState: true,
        replace: true,
      }
    )
  }, 400)
})

function deleteTerm(id) {
if(confirm('Are you sur want to delete !?')){
  router.delete(`/dashboard/terms/${id}`, {
    preserveScroll: true
  })
}
}

function handleKey(e) {
  if (e.key === 'Escape') closeModal()
}

onMounted(() => {
  window.addEventListener('keydown', handleKey)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKey)
})

const showEditModal = ref(false)
const selectedTerm = ref(null)

function openEditModal(term) {
  selectedTerm.value = term
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

const showCreateModal = ref(false)

function openCreateModal() {
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}
const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Terms', current: true },
]

</script>

<template>
  <DashboardLayout>

    <section class="space-y-6">

      <!-- Breadcrumb (optional) -->
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero eyebrow="Terms Management" title="Terms" description="Read, create, update, and delete terms records " />


      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">

        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <!-- Search -->
            <div class=" w-[24%] lg:items-end">
  
              <input
                v-model="search"
                type="text"
                placeholder="Search terms..."
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
              >
  
            </div>

            <button
              @click="openCreateModal"
              class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-650"
            >
              Create Term
            </button>
          </div>

        </div>

        <!-- Table -->
        <div class="relative">
          <table class="w-full text-sm">

            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-slate-600">ID</th>
                <th class="px-6 py-3 text-left text-slate-600">Term Name</th>
                <th class="px-6 py-3 text-right text-slate-600">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="term in terms.data"
                :key="term.id"
                class="border-t border-slate-200 hover:bg-slate-50 transition"
              >
                <td class="px-6 py-4 text-slate-500">
                  {{ term.id }}
                </td>

                <td class="px-6 py-4 font-medium text-slate-900">
                  {{ term.term_name }}
                </td>

                <td class="px-6 py-4 text-right space-x-2">

                  <button
                    @click="openEditModal(term)"
                    class="px-5 py-2 text-sm rounded-lg border border-blue-200 bg-blue-50 font-semibold text-blue-700  transition hover:bg-blue-100"
                  >
                    Edit
                  </button>

                  <button
                    @click="deleteTerm(term.id)"
                    class="px-5 py-2 text-sm rounded-lg border border-rose-200 bg-rose-50 font-semibold text-rose-700 transition hover:bg-rose-100"
                  >
                    Delete
                  </button>

                </td>
              </tr>

              <!-- Empty -->
              <tr v-if="!terms?.data?.length">
                <td colspan="3" class="py-10 text-center text-slate-500">
                  {{ search ? `No results for "${search}"` : 'No terms found.' }}
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

          <p class="text-sm text-slate-500">
            Showing {{ terms.from }}–{{ terms.to }} of {{ terms.total }} terms
          </p>

          <div class="flex flex-wrap gap-2 text-sm">
            <Link
              v-for="link in terms.links"
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

    <!-- Create Modal -->
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
          <Create @close="closeCreateModal" />
        </div>
      </div>
    </transition>

    <!-- Edit Modal -->
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
          <Edit v-if="selectedTerm" :term="selectedTerm" @close="closeEditModal" />
        </div>
      </div>
    </transition>

  </DashboardLayout>
</template>