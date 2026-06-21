<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'

const props = defineProps({
  classTypes: Array,
  terms: Array,
  times: Array,
})

const currentStep = ref(1)
const stepError = ref('')

const form = useForm({
  class_type_id: '',
  term_id: '',
  time_ids: [],
})

const filteredTimes = computed(() => {
  if (!form.term_id) return []
  return props.times.filter(time => time.term_id == form.term_id)
})

function handleTermChange() {
  form.time_ids = []
}

function nextStep() {
  stepError.value = ''
  if (currentStep.value === 1) {
    if (!form.class_type_id) {
      stepError.value = 'Please select a Class Type.'
      return
    }
    currentStep.value = 2
  } else if (currentStep.value === 2) {
    if (!form.term_id) {
      stepError.value = 'Please select a Term.'
      return
    }
    currentStep.value = 3
  }
}

function prevStep() {
  stepError.value = ''
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function goToStep(step) {
  stepError.value = ''
  if (step === 1) {
    currentStep.value = 1
  } else if (step === 2) {
    if (!form.class_type_id) {
      stepError.value = 'Please select a Class Type.'
      return
    }
    currentStep.value = 2
  } else if (step === 3) {
    if (!form.class_type_id) {
      stepError.value = 'Please select a Class Type.'
      return
    }
    if (!form.term_id) {
      stepError.value = 'Please select a Term.'
      return
    }
    currentStep.value = 3
  }
}

function submit() {
  stepError.value = ''
  if (form.time_ids.length === 0) {
    stepError.value = 'Please select at least one Time Slot.'
    return
  }
  form.post('/dashboard/schdule', {
    preserveScroll: true,
  })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Schedules', href: '/dashboard/schdule' },
  { label: 'Create', current: true },
]
</script>

<template>
  <Head title="Create Schedule" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Schedules Management" title="Create Schedule" description="Add a new schedule step-by-step." />

      <!-- Constrain both stepper and form card to max-w-3xl and center them -->
      <div class="max-w-3xl mx-auto space-y-6">
        <!-- Step Indicator Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between mx-auto">
            <!-- Step 1 -->
            <button
              type="button"
              @click="goToStep(1)"
              class="flex items-center space-x-3 focus:outline-none group cursor-pointer"
            >
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition',
                  currentStep === 1 ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-100' : 'bg-white border-slate-200 text-slate-400 group-hover:border-slate-300'
                ]"
              >
                <span>1</span>
              </div>
              <div :class="['text-sm font-bold transition', currentStep === 1 ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-500']">
                Class Type
              </div>
            </button>

            <!-- Line 1-2 -->
            <div class="flex-1 h-0.5 mx-4 bg-slate-200"></div>

            <!-- Step 2 -->
            <button
              type="button"
              @click="goToStep(2)"
              class="flex items-center space-x-3 focus:outline-none group cursor-pointer"
            >
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition',
                  currentStep === 2 ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-100' : 'bg-white border-slate-200 text-slate-400 group-hover:border-slate-300'
                ]"
              >
                <span>2</span>
              </div>
              <div :class="['text-sm font-bold transition', currentStep === 2 ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-500']">
                Term
              </div>
            </button>

            <!-- Line 2-3 -->
            <div class="flex-1 h-0.5 mx-4 bg-slate-200"></div>

            <!-- Step 3 -->
            <button
              type="button"
              @click="goToStep(3)"
              class="flex items-center space-x-3 focus:outline-none group cursor-pointer"
            >
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition',
                  currentStep === 3 ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-100' : 'bg-white border-slate-200 text-slate-400 group-hover:border-slate-300'
                ]"
              >
                <span>3</span>
              </div>
              <div :class="['text-sm font-bold transition', currentStep === 3 ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-500']">
                Time Slots
              </div>
            </button>
          </div>
        </div>

        <!-- Form Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <form @submit.prevent="submit" class="space-y-6">

            <!-- Global Step Error Message -->
            <div v-if="stepError" class="p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-600 flex items-center gap-2">
              <span>⚠️</span> {{ stepError }}
            </div>

            <!-- STEP 1: CLASS TYPE -->
            <div v-if="currentStep === 1" class="space-y-4">
              <div>
                <h3 class="text-base font-semibold text-slate-900">Select Class Type</h3>
                <p class="text-sm text-slate-500">Choose the class type for this schedule.</p>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <button
                  v-for="ct in classTypes"
                  :key="ct.class_type_id"
                  type="button"
                  @click="form.class_type_id = ct.class_type_id"
                  :class="[
                    'p-4 rounded-xl border text-left transition relative flex items-center space-x-3 cursor-pointer focus:outline-none',
                    form.class_type_id == ct.class_type_id ? 'border-blue-600 bg-blue-50/20 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition', form.class_type_id == ct.class_type_id ? 'border-blue-600' : 'border-slate-300']">
                    <div v-if="form.class_type_id == ct.class_type_id" class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                  </div>
                  <span class="text-sm font-semibold text-slate-800">{{ ct.type_name }}</span>
                </button>
              </div>

              <span v-if="form.errors.class_type_id" class="mt-1 block text-xs text-red-600">{{ form.errors.class_type_id }}</span>
            </div>

            <!-- STEP 2: TERM -->
            <div v-if="currentStep === 2" class="space-y-4">
              <div>
                <h3 class="text-base font-semibold text-slate-900">Select Term</h3>
                <p class="text-sm text-slate-500">Choose the academic term for this schedule.</p>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <button
                  v-for="term in terms"
                  :key="term.id"
                  type="button"
                  @click="form.term_id = term.id; handleTermChange()"
                  :class="[
                    'p-4 rounded-xl border text-left transition relative flex items-center space-x-3 cursor-pointer focus:outline-none',
                    form.term_id == term.id ? 'border-blue-600 bg-blue-50/20 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300'
                  ]"
                >
                  <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center transition', form.term_id == term.id ? 'border-blue-600' : 'border-slate-300']">
                    <div v-if="form.term_id == term.id" class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                  </div>
                  <span class="text-sm font-semibold text-slate-800">{{ term.term_name }}</span>
                </button>
              </div>

              <span v-if="form.errors.term_id" class="mt-1 block text-xs text-red-600">{{ form.errors.term_id }}</span>
            </div>

            <!-- STEP 3: TIME SLOTS -->
            <div v-if="currentStep === 3" class="space-y-4">
              <div>
                <h3 class="text-base font-semibold text-slate-900">Select Time Slots</h3>
                <p class="text-sm text-slate-500">Pick the slots matching the selected term.</p>
              </div>

              <div v-if="filteredTimes.length === 0" class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-500">
                No time slots found for this term.
              </div>

              <div v-else class="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-80 overflow-y-auto bg-slate-50 shadow-inner">
                <label
                  v-for="time in filteredTimes"
                  :key="time.id"
                  class="flex items-center justify-between px-4 py-3 hover:bg-white transition cursor-pointer"
                >
                  <span class="text-sm text-slate-700 font-medium select-none">{{ time.time_name }}</span>
                  <input
                    type="checkbox"
                    :value="time.id"
                    v-model="form.time_ids"
                    class="h-4.5 w-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                  />
                </label>
              </div>

              <span v-if="form.errors.time_ids" class="mt-1 block text-xs text-red-600">{{ form.errors.time_ids }}</span>
            </div>

            <!-- BUTTON FLOW -->
            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
              <div>
                <button
                  v-if="currentStep > 1"
                  type="button"
                  @click="prevStep"
                  class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer"
                >
                  Back
                </button>
                <Link
                  v-else
                  href="/dashboard/schdule"
                  class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 inline-block align-middle"
                >
                  Cancel
                </Link>
              </div>

              <div>
                <button
                  v-if="currentStep < 3"
                  type="button"
                  @click="nextStep"
                  class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 cursor-pointer"
                >
                  Next
                </button>
                <button
                  v-else
                  type="submit"
                  :disabled="form.processing"
                  class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 cursor-pointer"
                >
                  {{ form.processing ? 'Creating...' : 'Create Schedule' }}
                </button>
              </div>
            </div>

          </form>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
