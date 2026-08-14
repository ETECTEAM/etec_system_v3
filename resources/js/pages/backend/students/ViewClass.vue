<script setup>
import { router } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";
import { ref } from "vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

import ClassInformationCard from "./components/ClassInformationCard.vue";
import DepositTable from "./components/DepositTable.vue";
import DepositSummaryCard from "./components/DepositSummaryCard.vue";
import QuickActions from "./components/QuickActions.vue";
import RecordDepositModal from "./components/RecordDepositModal.vue";
import AddStudentModal from "./components/AddStudentModal.vue";

const props = defineProps({
  classData: {
    type: Object,
    default: null,
  },
  students: {
    type: Array,
    default: () => [],
  },
  depositSummary: {
    type: Object,
    default: null,
  },
  studentsForSelect: {
    type: Array,
    default: () => [],
  },
});

const depositEnrollment = ref(null);
const showEnrollExistingStudent = ref(false);

function openDeposit(enrollment) {
  depositEnrollment.value = enrollment;
}

function addStudent() {
  router.get(`/dashboard/enroll/${props.classData.id}/students/create`);
}

function enrollExistingStudent() {
  showEnrollExistingStudent.value = true;
}

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/enroll" },
  { label: "View Class", current: true },
];

function goBack() {
  router.get("/dashboard/enroll");
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-4 sm:space-y-5">
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero  :title="$t('View Class')" />
      </div>
      <div  v-if="!classData" class="mt-6 flex items-center justify-center rounded-xl bg-white py-20 shadow dark:bg-gray-900">
        <p class="text-slate-400 dark:text-gray-500">{{ $t('Class data is not available.') }}</p>
      </div>

      <template v-else>
        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <button @click="goBack" class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            <ArrowLeft class="h-4 w-4" /> Back to Class List
          </button>
          <QuickActions @add-student="addStudent" @enroll-existing-student="enrollExistingStudent" />
        </div>

        <!-- Class Information Card -->
        <!-- <div class="mt-6">
          <ClassInformationCard :classData="classData" />
        </div> -->

        <!-- Deposit Summary Cards -->
        <div class="mt-6">
          <DepositSummaryCard :depositSummary="depositSummary" />
        </div>

        <!-- Student Deposit Table -->
        <div class="mt-6">
          <DepositTable
            :students="students"
            @record-deposit="openDeposit"
          />
        </div>

        <RecordDepositModal
          :show="Boolean(depositEnrollment)"
          :enrollment="depositEnrollment"
          @close="depositEnrollment = null"
        />

        <AddStudentModal
          :show="showEnrollExistingStudent"
          :classId="classData.id"
          :students="studentsForSelect"
          @close="showEnrollExistingStudent = false"
        />
      </template>
    </div>
  </DashboardLayout>
</template>
