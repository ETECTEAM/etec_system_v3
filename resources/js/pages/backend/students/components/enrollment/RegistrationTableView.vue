<script setup>
import { UserPlus, UserCheck, Printer, Pencil, ArrowRightLeft, X, Check } from "@lucide/vue";
import Table from "@/components/ui/table/Table.vue";
import TableHeader from "@/components/ui/table/TableHeader.vue";
import TableHead from "@/components/ui/table/TableHead.vue";
import TableBody from "@/components/ui/table/TableBody.vue";
import TableRow from "@/components/ui/table/TableRow.vue";
import TableCell from "@/components/ui/table/TableCell.vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const {
  registrations,
  loading,
  pagination,
  printingId,
  needsManualScheduling,
  isPendingRegistration,
  scheduleLabel,
  requestedScheduleLabel,
  registrationPageLabel,
  goRegistrationPage,
  printReceipt,
  approveRegistration,
  openPartialPaymentModal,
  openMoveModal,
  editingId,
  editDraft,
  editErrors,
  editSaving,
  editNameLiveError,
  isEditing,
  startEdit,
  cancelEdit,
  saveEdit,
} = useEnrollmentRegistrations();
</script>

<template>
  <div class="w-full">
    <div class="w-full overflow-x-auto rounded-xl bg-white shadow dark:bg-gray-900">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Name') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Gender') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">Phone</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Class') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Schedule') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Price') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Payment Status') }}</TableHead>
            <TableHead class="h-8 px-3 text-[11px]">{{ $t('Registered') }}</TableHead>
            <TableHead class="h-8 px-3 text-right text-[11px]">{{ $t('Action') }}</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-for="row in registrations" :key="row.enrollment_id">
            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm font-semibold">
              <template v-if="isEditing(row)">
                <input
                  v-model="editDraft.name"
                  type="text"
                  class="w-36 rounded-lg border px-2 py-1 text-sm font-normal outline-none transition focus:ring-2"
                  :class="editNameLiveError || editErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200'"
                >
                <p v-if="editNameLiveError || editErrors.name" class="mt-1 text-[11px] font-semibold text-red-600">{{ editNameLiveError || editErrors.name[0] }}</p>
              </template>
              <template v-else>{{ row.name }}</template>
            </TableCell>

            <TableCell class="px-3 py-1.5 text-sm">
              <template v-if="isEditing(row)">
                <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-gray-600">
                  <button type="button" class="px-2.5 py-1 text-xs font-semibold transition" :class="editDraft.gender === 'male' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-gray-800 dark:text-gray-300'" @click="editDraft.gender = 'male'">{{ $t('Male') }}</button>
                  <button type="button" class="border-l border-slate-300 px-2.5 py-1 text-xs font-semibold transition dark:border-gray-600" :class="editDraft.gender === 'female' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-gray-800 dark:text-gray-300'" @click="editDraft.gender = 'female'">{{ $t('Female') }}</button>
                </div>
                <p v-if="editErrors.gender" class="mt-1 text-[11px] font-semibold text-red-600">{{ editErrors.gender[0] }}</p>
              </template>
              <template v-else>{{ row.gender }}</template>
            </TableCell>

            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm">
              <template v-if="isEditing(row)">
                <input
                  v-model="editDraft.phone"
                  type="text"
                  class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                <p v-if="editErrors.phone" class="mt-1 text-[11px] font-semibold text-red-600">{{ editErrors.phone[0] }}</p>
              </template>
              <template v-else>{{ row.phone }}</template>
            </TableCell>

            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm">
              <div v-if="needsManualScheduling(row)">
                <p>{{ row.course_title || '-' }}</p>
              </div>
              <div v-else>
                <p>{{ row.class_title }}</p>
                <p v-if="row.course_title" class="max-w-72 truncate text-[11px] leading-4 text-slate-500 dark:text-gray-400">{{ row.course_title }}</p>
              </div>
            </TableCell>

            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm">
              {{ needsManualScheduling(row) ? requestedScheduleLabel(row) : scheduleLabel(row) }}
            </TableCell>

            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm">
              ${{ Number(row.fee_amount + row.document_fee_amount).toFixed(2) }}
            </TableCell>

            <TableCell class="px-3 py-1.5 text-sm">
              <span
                class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs"
                :class="{
                  'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400': isPendingRegistration(row) || row.payment_status === 'Partial',
                  'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400': !isPendingRegistration(row) && row.payment_status === 'Paid',
                  'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400': !isPendingRegistration(row) && row.payment_status === 'Unpaid',
                }"
              >
                {{ isPendingRegistration(row) ? $t('Pending Approval') : row.payment_status }}
              </span>
              <span
                v-if="needsManualScheduling(row)"
                class="ml-1 inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
              >
                {{ $t('Needs Class') }}
              </span>
            </TableCell>

            <TableCell class="whitespace-nowrap px-3 py-1.5 text-sm text-slate-500 dark:text-gray-400">
              {{ row.enrolled_at }}
            </TableCell>

            <TableCell class="px-3 py-1.5 text-sm">
              <div class="flex flex-wrap justify-end items-center gap-1.5">
                <template v-if="isEditing(row)">
                  <button
                    type="button"
                    :disabled="editSaving || !!editNameLiveError"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :title="$t('Save')"
                    @click="saveEdit(row)"
                  >
                    <Check class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ editSaving ? $t('Saving...') : $t('Save') }}</span>
                  </button>
                  <button
                    type="button"
                    :disabled="editSaving"
                    class="inline-flex h-8 items-center justify-center rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    :title="$t('Cancel')"
                    @click="cancelEdit"
                  >
                    <X class="h-4 w-4 shrink-0" />
                  </button>
                </template>

                <template v-else>
                  <button
                    v-if="isPendingRegistration(row)"
                    type="button"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-100 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                    :title="$t('Approve request')"
                    @click="approveRegistration(row)"
                  >
                    <UserCheck class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ $t('Approve') }}</span>
                  </button>

                  <button
                    v-else
                    type="button"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    :title="$t('Edit')"
                    @click="startEdit(row)"
                  >
                    <Pencil class="h-4 w-4 shrink-0" />
                  </button>

                  <button
                    v-if="!isPendingRegistration(row) && needsManualScheduling(row)"
                    type="button"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-100 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                    :title="$t('Assign to Class')"
                    @click="openMoveModal(row)"
                  >
                    <UserPlus class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ $t('Assign to Class') }}</span>
                  </button>

                  <button
                    v-if="!isPendingRegistration(row) && !needsManualScheduling(row)"
                    type="button"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-slate-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:disabled:hover:bg-gray-800"
                    :disabled="row.payment_status !== 'Paid'"
                    :title="row.payment_status === 'Paid' ? $t('Move to Another Class') : $t('Record payment and print the receipt before moving this student.')"
                    @click="openMoveModal(row)"
                  >
                    <ArrowRightLeft class="h-4 w-4 shrink-0" />
                  </button>

                  <button
                    v-if="!isPendingRegistration(row)"
                    type="button"
                    class="inline-flex h-8 w-[150px] items-center justify-center gap-1.5 rounded-lg bg-blue-100 px-3 text-xs font-semibold text-blue-700 transition hover:bg-blue-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                    :disabled="printingId === row.enrollment_id"
                    :title="row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record payment')"
                    @click="row.payment_status === 'Paid' ? printReceipt(row) : openPartialPaymentModal(row)"
                  >
                    <Printer class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record Payment') }}</span>
                  </button>
                </template>
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-if="registrations.length === 0">
            <TableCell colspan="9">
              <div class="py-10 text-center text-slate-500 dark:text-gray-400">
                {{ loading ? $t('Loading...') : $t('No public registrations yet.') }}
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div v-if="pagination.total > pagination.per_page" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-slate-500 dark:text-gray-400">
        {{ $t('Showing') }} {{ pagination.from ?? 0 }} {{ $t('to') }} {{ pagination.to ?? 0 }} {{ $t('of') }} {{ pagination.total ?? 0 }}
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="link in pagination.links"
          :key="link.label"
          type="button"
          :disabled="!link.url || loading"
          class="inline-flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
          :class="link.active
            ? 'border-blue-900 bg-blue-900 text-white dark:border-blue-500 dark:bg-blue-600'
            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'"
          @click="goRegistrationPage(link)"
        >
          {{ registrationPageLabel(link.label) }}
        </button>
      </div>
    </div>
  </div>
</template>
