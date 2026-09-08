<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ReceiptPrint from "./components/ReceiptPrint.vue";
import MoveEnrollmentModal from "./components/MoveEnrollmentModal.vue";
import EnrollmentTabs from "./components/enrollment/EnrollmentTabs.vue";
import RegistrationsPanel from "./components/enrollment/RegistrationsPanel.vue";
import ConfirmPaymentModal from "./components/enrollment/ConfirmPaymentModal.vue";
import EditRegistrationModal from "./components/enrollment/EditRegistrationModal.vue";
import RegisterToClass from "./components/RegisterToClass.vue";
import VipClassForm from "./components/VipClassForm.vue";
import ManualRegisterForm from "./components/ManualRegisterForm.vue";
import { getEcho } from "@/echo";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const props = defineProps({
  classes: {
    type: Object,
    default: () => ({ data: [] }),
  },
  // Flat list of classes a new student may still be registered into
  // (open seats + recently started / upcoming) — drives the "Register to Class" tab.
  eligibleClasses: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({ search: "" }),
  },
  depositSummary: {
    type: Object,
    default: null,
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Enrollment Management", current: true },
];

const {
  pendingCount,
  loaded,
  fetchRegistrations,
  receiptClassData,
  receiptStudent,
  moveModalOpen,
  movingRow,
  closeMoveModal,
  onStudentMoved,
} = useEnrollmentRegistrations();

const VALID_TABS = ["registrations", "vip", "manual", "register-class"];
const VALID_VIEWS = ["table", "card"];

function readParam(name, valid, fallback) {
  if (typeof window === "undefined") return fallback;
  const value = new URLSearchParams(window.location.search).get(name);
  return valid.includes(value) ? value : fallback;
}

const activeTab = ref(readParam("tab", VALID_TABS, "registrations"));
const registrationView = ref(readParam("view", VALID_VIEWS, "table"));

// Reflect tab / view in the URL without an Inertia visit — no full-page reload.
function syncUrl() {
  if (typeof window === "undefined") return;
  const url = new URL(window.location.href);
  url.searchParams.set("tab", activeTab.value);
  if (activeTab.value === "registrations") {
    url.searchParams.set("view", registrationView.value);
  } else {
    url.searchParams.delete("view");
  }
  window.history.replaceState(window.history.state, "", `${url.pathname}${url.search}`);
}

watch([activeTab, registrationView], syncUrl);

const classOptions = computed(() => props.classes?.data ?? []);

let notificationsChannel = null;

onMounted(() => {
  syncUrl();

  // Fetched once up front so the "Registrations" tab badge is accurate even
  // when another tab loads first.
  fetchRegistrations();

  notificationsChannel = getEcho()
    ?.private("admin-notifications")
    .listen(".notifications.updated", () => {
      if (loaded.value) fetchRegistrations();
      // Keep the class list's seat counts / stats fresh too.
      router.reload({ only: ["classes", "depositSummary"], preserveScroll: true });
    });
});

onBeforeUnmount(() => {
  if (notificationsChannel) {
    notificationsChannel.stopListening(".notifications.updated");
  }
});
</script>

<template>
  <DashboardLayout>
    <div class="w-full space-y-6">
      <div>
        <Breadcrumbs :items="breadcrumbItems" class="mb-4" />
        <PageHero
          eyebrow="Management"
          :title="$t('Enrollment Management')"
          :description="$t('Manage student registration and payments.')"
        />
      </div>

      <EnrollmentTabs v-model="activeTab" :pending-count="pendingCount" />

      <!-- Panels are lazily mounted — a tab's data only loads when it is opened. -->
      <RegistrationsPanel
        v-if="activeTab === 'registrations'"
        v-model:view="registrationView"
        :deposit-summary="depositSummary"
        :classes="classes"
      />
      <VipClassForm
        v-else-if="activeTab === 'vip'"
        :classes="classOptions"
        @cancel="activeTab = 'registrations'"
      />
      <ManualRegisterForm
        v-else-if="activeTab === 'manual'"
        :classes="classOptions"
        @cancel="activeTab = 'registrations'"
      />
      <RegisterToClass
        v-else
        :classes="eligibleClasses"
      />

      <!-- Shared, tab-independent overlays -->
      <ReceiptPrint :class-data="receiptClassData" :student="receiptStudent" />
      <ConfirmPaymentModal />
      <EditRegistrationModal />
      <MoveEnrollmentModal
        :show="moveModalOpen"
        :enrollment="movingRow"
        @close="closeMoveModal"
        @moved="onStudentMoved"
      />
    </div>
  </DashboardLayout>
</template>
