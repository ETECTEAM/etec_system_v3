<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import RegistrationTabs from "./components/RegistrationTabs.vue";
import RegisterToClass from "./components/RegisterToClass.vue";
import VipClassForm from "./components/VipClassForm.vue";
import ManualRegisterForm from "./components/ManualRegisterForm.vue";

const props = defineProps({
  // Classes that just started, presented via GetClassList::presentClass.
  classes: {
    type: Array,
    default: () => [],
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/enroll" },
  { label: "Register Student", current: true },
];

const VALID_TABS = ["vip", "manual", "class"];

function initialTab() {
  if (typeof window === "undefined") return "class";
  const tab = new URLSearchParams(window.location.search).get("tab");
  return VALID_TABS.includes(tab) ? tab : "class";
}

const activeTab = ref(initialTab());

// Client-side tab switch — reflect it in the URL without an Inertia visit so
// the page (and its already-loaded data) is never re-fetched.
watch(activeTab, (tab) => {
  if (typeof window === "undefined") return;
  const url = new URL(window.location.href);
  url.searchParams.set("tab", tab);
  window.history.replaceState(window.history.state, "", `${url.pathname}${url.search}`);
});

function backToList() {
  router.get("/dashboard/enroll");
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-5">
        <Breadcrumbs :items="breadcrumbItems" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <PageHero
            eyebrow="Class Management"
            :title="$t('Register Student')"
            :description="$t('Manage student registration and payments.')"
          />

          <button
            type="button"
            @click="backToList"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
          >
            <ArrowLeft class="h-4 w-4" />
            {{ $t('Back to List') }}
          </button>
        </div>

        <RegistrationTabs v-model="activeTab" />

        <!-- Panels are lazily mounted: a tab's component and data only load when opened. -->
        <RegisterToClass v-if="activeTab === 'class'" :classes="classes" />
        <VipClassForm v-else-if="activeTab === 'vip'" :classes="classes" @cancel="activeTab = 'class'" />
        <ManualRegisterForm v-else :classes="classes" @cancel="activeTab = 'class'" />
      </div>
    </div>
  </DashboardLayout>
</template>
