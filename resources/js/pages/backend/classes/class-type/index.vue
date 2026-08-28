<script setup>
import axios from 'axios'
import { onMounted, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Breadcrumbs } from '@/components/ui/breadcrumbs'
import { PageHero } from '@/components/ui/page-hero'
import { Pagination } from '@/components/ui/pagination'
import { Card } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useI18n } from '@/i18n'
import { useToast } from 'vue-toastification'

const { t } = useI18n()
const { confirm } = useConfirm()
const toast = useToast()
const classTypes = ref([])
const savingId = ref(null)
const search = ref('')
const perPage = ref(10)
const isLoading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const breadcrumbItems = [{ label: 'Dashboard', href: '/dashboard' }, { label: 'Class Types', current: true }]

onMounted(() => fetchClassTypes())

async function fetchClassTypes(pageNumber = 1) {
  isLoading.value = true
  try {
    const response = await axios.get('/dashboard/class-types/data', { params: { page: pageNumber, per_page: perPage.value, search: search.value } })
    classTypes.value = response.data.data ?? []
    pagination.value = { current_page: response.data.current_page ?? 1, last_page: response.data.last_page ?? 1, per_page: response.data.per_page ?? 10, total: response.data.total ?? 0 }
  } catch (error) {
    console.error('Failed to fetch class types', error)
    toast.error(t('Failed to load class types. Please try again.'))
  } finally {
    isLoading.value = false
  }
}

async function saveClassTypeField(classType, field, value) {
  const normalized = field === 'is_active' ? value === 'true' : value
  const previous = classType[field]
  if (normalized === previous || savingId.value !== null) return
  savingId.value = classType.class_type_id
  try {
    const response = await axios.put(`/dashboard/class-types/${classType.class_type_id}`, {
      type_name: field === 'type_name' ? normalized : classType.type_name,
      description: field === 'description' ? normalized : classType.description,
      is_active: field === 'is_active' ? normalized : classType.is_active,
    })
    Object.assign(classType, response.data.data ?? { [field]: normalized })
    toast.success(t('Class type updated.'))
  } catch (error) {
    classType[field] = previous
    console.error('Failed to update class type', error)
    toast.error(t(error.response?.data?.message ?? 'Failed to update class type. Please try again.'))
  } finally {
    savingId.value = null
  }
}

async function deleteClassType(classType) {
  const confirmed = await confirm({ title: t('Delete class type'), message: t('Are you sure you want to delete :name?', { name: classType.type_name }), confirmText: t('Delete'), cancelText: t('Cancel'), danger: true })
  if (!confirmed) return
  try {
    await axios.delete(`/dashboard/class-types/${classType.class_type_id}`)
    toast.success(t('Class type deleted successfully.'))
    fetchClassTypes(classTypes.value.length === 1 && pagination.value.current_page > 1 ? pagination.value.current_page - 1 : pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete class type', error)
    toast.error(t(error.response?.data?.message ?? 'Could not delete the class type. It may be in use.'))
  }
}

function rowNumber(index) { return ((pagination.value.current_page - 1) * pagination.value.per_page) + index + 1 }
function paginationStart() { return pagination.value.total === 0 || classTypes.value.length === 0 ? 0 : ((pagination.value.current_page - 1) * pagination.value.per_page) + 1 }
function paginationEnd() { return pagination.value.total === 0 || classTypes.value.length === 0 ? 0 : ((pagination.value.current_page - 1) * pagination.value.per_page) + classTypes.value.length }

watch(search, () => fetchClassTypes(1))
watch(perPage, () => fetchClassTypes(1))
</script>

<template>
  <Head :title="$t('Class Types')" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Management" :title="$t('Class Types')" :description="$t('Manage your class categories.')" />
      <Card padding="p-0">
        <div class="flex justify-between border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <input v-model="search" type="text" :placeholder="$t('Search by name...')" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 lg:max-w-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
          <Link
            href="/dashboard/class-types/create"
            class="ml-4 inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ $t('Create Class Type') }}
          </Link>
        </div>
        <div class="relative">
          <Table>
            <TableHeader><TableRow><TableHead class="w-16">{{ $t('No') }}</TableHead><TableHead>{{ $t('Type Name') }}</TableHead><TableHead>{{ $t('Status') }}</TableHead><TableHead class="text-right">{{ $t('Actions') }}</TableHead></TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="(classType, index) in classTypes" :key="classType.class_type_id">
                <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
                <TableCell class="font-medium text-slate-900 dark:text-gray-100"><input type="text" :value="classType.type_name" :disabled="savingId === classType.class_type_id" class="w-40 rounded-lg border border-transparent bg-transparent px-2 py-1 font-medium text-slate-900 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="saveClassTypeField(classType, 'type_name', $event.target.value.trim())"></TableCell>
                <TableCell><select :value="String(classType.is_active)" :disabled="savingId === classType.class_type_id" class="rounded-lg border border-transparent bg-transparent px-2 py-1 text-sm font-medium text-slate-700 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="saveClassTypeField(classType, 'is_active', $event.target.value)"><option value="true">{{ $t('Active') }}</option><option value="false">{{ $t('Inactive') }}</option></select></TableCell>
                <TableCell class="text-right"><button type="button" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20" @click="deleteClassType(classType)">{{ $t('Delete') }}</button></TableCell>
              </TableRow>
              <TableRow v-if="classTypes.length === 0"><TableCell colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No class types found.') }}</TableCell></TableRow>
            </TableBody>
          </Table>
        </div>
        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Showing :from-:to of :total class types', { from: paginationStart(), to: paginationEnd(), total: pagination.total }) }}</p>
          <Pagination :current-page="pagination.current_page" :last-page="pagination.last_page" :disabled="isLoading" @page-change="fetchClassTypes" />
        </div>
      </Card>
    </section>
  </DashboardLayout>
</template>
