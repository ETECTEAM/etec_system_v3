<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  terms: Object,
  filters: Object,
})

// init search value safely
const search = ref(props.filters.search ?? '')

// debounce search
let timeout = null

watch(search, (value) => {
  clearTimeout(timeout)

  timeout = setTimeout(() => {
    router.get(
      '/dashboard/terms',
      {
        search: value,
        page: 1, // reset pagination on search
      },
      {
        preserveState: true,
        replace: true,
      }
    )
  }, 400)
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div >
          <h1 class="text-2xl font-bold text-gray-900">Terms</h1>
          <p class="text-sm text-gray-500">Manage all terms</p>
        </div>

        <!-- Search + Create -->
        <div class="flex items-center justify-end gap-4 w-[40%]">

          <input
            v-model="search"
            type="text"
            placeholder="Search term..."
            class="w-full max-w-sm rounded-md border px-4 py-2 text-sm
              focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
          />

          <Link
            href="/dashboard/terms/create"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            + Create Term
          </Link>

        </div>
      </div>

      <!-- Table -->
      <div class="rounded-md bg-white shadow-sm overflow-hidden">

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
              class="border-b border-gray-300 hover:bg-gray-50"
            >
              <td class="p-4 text-gray-500">
                {{ term.id }}
              </td>

              <td class="p-4 font-medium text-gray-900">
                {{ term.term_name }}
              </td>

              <td class="p-4 text-right space-x-2">

                <Link
                  :href="`/dashboard/terms/${term.id}/edit`"
                  class="rounded-lg bg-yellow-500 px-3 py-1 text-white text-sm hover:bg-yellow-600"
                >
                  Edit
                </Link>

                <Link
                  :href="`/dashboard/terms/${term.id}`"
                  method="delete"
                  as="button"
                  class="rounded-lg bg-red-500 px-3 py-1 text-white text-sm hover:bg-red-600"
                >
                  Delete
                </Link>

              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="!terms?.data?.length">
              <td colspan="3" class="text-center py-6 text-gray-500">
                No terms found
              </td>
            </tr>

          </tbody>

        </table>

      </div>

      <!-- Pagination -->
      <div class="flex justify-between items-center text-sm text-gray-600">

        <div>
          Showing {{ terms.from }} to {{ terms.to }} of {{ terms.total }}
        </div>

        <div class="space-x-2">

          <Link
            v-for="link in terms.links"
            :key="link.label"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 border rounded-lg"
            :class="{
              'bg-blue-600 text-white': link.active,
              'opacity-50 pointer-events-none': !link.url
            }"
          />

        </div>

      </div>

    </div>
  </DashboardLayout>
</template>
