<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted } from 'vue'
import Edit from './Edit.vue'
import Create from './Create.vue'

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
  router.delete(`/dashboard/terms/${id}`, {
    preserveScroll: true
  })
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

</script>

<template>
  <DashboardLayout>

    <div class="p-6 space-y-8">

      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">

        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
            Terms Management
          </h1>
          <p class="text-gray-500 text-sm mt-1">
            Create, edit, and manage your academic terms easily
          </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">

          <div class="relative w-full md:w-80">
            <input v-model="search" type="text" placeholder="Search terms..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm
                     focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-none" />
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>
          </div>

          <button @click="openCreateModal" class="flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600
                   text-white px-5 py-2.5 rounded-xl shadow hover:scale-105 transition">
            <i class="fa-solid fa-plus"></i>
            New Term
          </button>

        </div>
      </div>

      <div class="bg-white rounded-2xl shadow border overflow-hidden">

        <table class="w-full text-sm">

          <thead class="bg-gray-50 uppercase text-xs tracking-wider">
            <tr>
              <th class="px-6 py-4 text-xl text-blue-600  text-left">ID</th>
              <th class="px-6 py-4 text-xl text-blue-600  text-left">Term Name</th>
              <th class="px-20 py-4 text-xl text-blue-600  text-right">Actions</th>
            </tr>
          </thead>

          <tbody>

            <tr v-for="term in terms.data" :key="term.id" class="border-t hover:bg-gray-50 transition duration-150">
              <td class="px-6 py-4 text-gray-400 font-medium text-xl ">
                {{ term.id }}
              </td>

              <td class="px-6 py-4 font-semibold text-gray-800 text-xl ">
                {{ term.term_name }}
              </td>

              <td class="px-6 py-4 text-right space-x-2">

                <button @click="openEditModal(term)" class="px-3 py-1.5 text-lg rounded-lg bg-amber-400 text-white
                         hover:bg-amber-500 transition shadow-sm cursor-pointer">
                  <i class="fa-regular fa-pen-to-square"></i> Edit
                </button>

                <button @click="deleteTerm(term.id)" class="px-3 py-1.5 text-lg rounded-lg bg-red-500 text-white
                         hover:bg-blue-600 transition shadow-sm cursor-pointer">
                  <i class="fa-solid fa-trash-can"></i> Delete
                </button>

              </td>
            </tr>

          </tbody>
        </table>

        <div v-if="!terms?.data?.length" class="flex flex-col items-center justify-center py-20">

          <div class="text-5xl mb-4">📭</div>

          <p class="text-lg font-semibold text-gray-800">
            {{ search ? `No results for "${search}"` : 'No terms yet' }}
          </p>

          <p class="text-gray-500 text-sm mt-2">
            {{ search
              ? 'Try another keyword'
              : 'Start by creating your first term'
            }}
          </p>

          <button v-if="!search" @click="openCreateModal"
            class="mt-5 px-5 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">
            Create First Term
          </button>

        </div>
      </div>

      <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-600">

        <div class="text-2xl ">
          Showing <span class="font-semibold">{{ terms.from }}</span>
          to <span class="font-semibold">{{ terms.to }}</span>
          of <span class="font-semibold">{{ terms.total }}</span>
        </div>

        <div class="flex flex-wrap gap-2 text-2xl">
          <Link v-for="link in terms.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
            class="px-3 py-1 rounded-lg border text-sm transition" :class="{
              'bg-blue-600 text-white border-blue-600': link.active,
              'hover:bg-gray-100': !link.active,
              'opacity-40 pointer-events-none': !link.url
            }" />
        </div>

      </div>

    </div>

    <!-- Model -->
    <transition name="fade">
      <div v-if="showCreateModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="closeCreateModal">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl relative">

          <button @click="closeCreateModal" class="absolute top-3 right-3 text-gray-400 hover:text-black">
            ✖
          </button>

          <Create @close="closeCreateModal" />
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showEditModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="closeEditModal">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl relative">

          <button @click="closeEditModal" class="absolute top-3 right-3 text-gray-400 hover:text-black">
            ✖
          </button>

          <Edit v-if="selectedTerm" :term="selectedTerm" @close="closeEditModal" />
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>