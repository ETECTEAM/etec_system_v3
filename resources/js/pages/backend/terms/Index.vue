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

// const showDeleteModal = ref(false)
// const deleteId = ref(null)
// const loading = ref(false)

// function openDeleteModal(id) {
//   deleteId.value = id
//   showDeleteModal.value = true
// }

// function closeModal() {
//   if (loading.value) return
//   showDeleteModal.value = false
//   deleteId.value = null
// }

// function confirmDelete() {
//   loading.value = true
//   showDeleteModal.value = false

//   router.delete(`/dashboard/terms/${deleteId.value}`, {
//     onFinish: () => {
//       loading.value = false
//     },
//     onSuccess: () => {
//       closeModal()
//     }
//   })
// }
//
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

// Edit model
const showEditModal = ref(false)
const selectedTerm = ref(null)

function openEditModal(term) {
  selectedTerm.value = term
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

// Create Model
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
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 uppercase">Terms</h1>
          <p class="text-sm text-gray-500">Manage all terms</p>
        </div>

        <!-- Search + Create -->
        <div class="flex items-center justify-end gap-4 w-[40%]">

          <input
            v-model="search"
            type="text"
            placeholder="Search term..."
            class="w-full max-w-sm rounded-xl border px-4 py-2 text-sm
              focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
          />

          <Link
            href="/dashboard/terms/create"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            + Create Term
          </Link>

        </div>
      </div>

      <!-- Table -->
      <div class="rounded-2xl bg-white shadow-sm overflow-hidden">

        <table class="w-full text-left">

          <thead class="border-b bg-gray-100 text-gray-600 text-sm">
            <tr>
              <th class="p-4">ID</th>
              <th class="p-4">Term Name</th>
              <th class="p-4 text-right">Action</th>
            </tr>
          </thead>

          <tbody>

            <tr
              v-for="term in terms.data"
              :key="term.id"
              class="border-b hover:bg-gray-50"
            >
              <td class="p-4 text-gray-500">
                {{ term.id }}
              </td>

              <td class="p-4 font-medium text-gray-900">
                {{ term.term_name }}
              </td>

              <td class="p-4 text-right space-x-2">

                <button @click="openEditModal(term)"
                  class="px-3 py-1 rounded-lg bg-amber-400 text-white text-sm hover:bg-amber-500 transition cursor-pointer active:bg-blue-200 ">
                  <i class="fa-regular fa-pen-to-square"></i> Edit
                </button>

               <button @click="deleteTerm(term.id)"
  class="px-3 py-1 rounded-lg bg-red-500 text-white text-sm hover:bg-red-600 transition">
  <i class="fa-solid fa-trash-can"></i> Delete
</button>

              </td>
            </tr>
          </tbody>

        </table>

        <div v-if="!terms?.data?.length" class="flex justify-center py-14">
          <div class="text-center max-w-md">
            <p class="text-lg font-semibold text-gray-800">
              {{ search ? `No results for "${search}"` : 'No terms available' }}
            </p>
            <p class="text-sm text-gray-500 mt-2">
              {{ search
                ? 'Try different keywords or check spelling'
                : 'Start by creating your first term'
              }}
            </p>

          </div>
        </div>
      </div>

      <div class="flex justify-between items-center text-sm text-gray-600">
        <div>
          Showing {{ terms.from }} to {{ terms.to }} of {{ terms.total }}
        </div>
        <div class="space-x-2">
          <Link v-for="link in terms.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
            class="px-3 py-1 border rounded-lg" :class="{
              'bg-blue-600 text-white': link.active,
              'opacity-50 pointer-events-none': !link.url
            }" />
        </div>
      </div>
    </div>
     <!--  -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl w-full max-w-lg p-6 relative">
        <button @click="closeEditModal" class="absolute top-2 right-2 text-gray-500 hover:text-black">
          ✖
        </button>
        <Edit :term="selectedTerm" @close="closeEditModal" />
      </div>
    </div>
    <!--  -->
<div v-if="showCreateModal"
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

  <div class="bg-white rounded-xl w-full max-w-lg p-6 relative">
    <button
      @click="closeCreateModal"
      class="absolute top-2 right-2 text-gray-500 hover:text-black">
      ✖
    </button>
    <Create @close="closeCreateModal" />
  </div>
</div>

    <!-- <transition name="fade"> -->
      <!--  -->
      
      <!-- <div v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
        @click.self="closeModal">
        <div class="bg-white w-[340px] rounded-2xl shadow-xl p-6 text-center">
          <div
            class="mx-auto w-14 h-14 flex items-center justify-center rounded-full bg-red-50 border border-red-100 mb-3">
            <span class="text-2xl">⚠️</span>
          </div>

          <h2 class="text-lg font-semibold text-gray-800">
            Delete Confirmation
          </h2>
          <p class="text-sm text-gray-500 mt-2">
            This action cannot be undone.
          </p>
          <div class="flex gap-2 mt-5">

            <button @click="closeModal"
              class="w-full py-2 rounded-lg border text-gray-600 hover:bg-gray-100 transition">
              Cancel
            </button>
            <button @click="confirmDelete" :disabled="loading"
              class="w-full py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition disabled:opacity-50">
              {{ loading ? 'Deleting...' : 'Delete' }}
            </button>

          </div>
        </div>
      </div> -->
    <!-- </transition> -->
  </DashboardLayout>
</template>