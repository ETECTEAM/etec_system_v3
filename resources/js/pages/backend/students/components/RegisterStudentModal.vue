<script setup>
import { computed, nextTick, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { X, UserPlus } from "@lucide/vue";
import { latinNameError } from "@/composables/useLatinNameValidation";
import SelectSearch from "@/components/ui/select-search/SelectSearch.vue";

const genderOptions = [
  { value: "male", label: "Male" },
  { value: "female", label: "Female" },
];

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  classId: {
    type: [Number, String],
    default: null,
  },
  classTitle: {
    type: String,
    default: "",
  },
  seatsLeft: {
    type: Number,
    default: null,
  },
  // Optional richer summary for the "Register to Class" flow — when set, a
  // compact "Selected Class" panel replaces the one-line subtitle.
  classInfo: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close"]);

const nameInput = ref(null);

const form = useForm({
  name: "",
  gender: "",
  phone: "",
});

const nameLiveError = computed(() => latinNameError(form.name));

function normalizePhoneInput(event) {
  const value = event.target.value ?? "";
  form.phone = String(value).replace(/\D+/g, "").slice(0, 12);
}

function close() {
  form.reset();
  form.clearErrors();
  emit("close");
}

function resetForNextStudent() {
  form.reset();
  form.clearErrors();

  nextTick(() => {
    nameInput.value?.focus();
  });
}

function submit() {
  if (nameLiveError.value || !props.classId) {
    return;
  }

  form.post(`/dashboard/enroll/${props.classId}/students`, {
    preserveScroll: true,
    onSuccess: () => {
      resetForNextStudent();
    },
  });
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
    @click.self="close"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-500/10">
            <UserPlus class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
              {{ $t('Register Student') }}
            </h3>
            <p v-if="classTitle && !classInfo" class="text-xs text-slate-500 dark:text-gray-400">
              {{ classTitle }}
              <span v-if="seatsLeft !== null"> &middot; {{ seatsLeft }} {{ $t('seats left') }}</span>
            </p>
          </div>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="-mr-2 -mt-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
          @click="close"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Selected class summary (Register to Class flow) -->
      <div v-if="classInfo" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
          {{ $t('Selected Class') }}
        </p>
        <p class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ classInfo.title }}</p>
        <div class="mt-1 space-y-0.5 text-xs text-slate-500 dark:text-gray-400">
          <p v-if="classInfo.teacher">{{ classInfo.teacher }}</p>
          <p v-if="classInfo.term || classInfo.time">{{ [classInfo.term, classInfo.time].filter(Boolean).join(' · ') }}</p>
          <p v-if="classInfo.room">{{ classInfo.room }}</p>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <span v-if="classInfo.startLabel" class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
            {{ classInfo.startLabel }}
          </span>
          <span v-if="classInfo.capacity" class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 tabular-nums ring-1 ring-inset ring-slate-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
            {{ classInfo.students }} / {{ classInfo.capacity }} {{ $t('Students') }}
          </span>
        </div>
      </div>

      <form class="mt-5 space-y-4" @submit.prevent="submit">
        <p v-if="form.errors.class" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-400">
          {{ form.errors.class }}
        </p>

        <div>
          <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
            {{ $t('Student Name') }}
          </label>
          <input
            ref="nameInput"
            v-model="form.name"
            type="text"
            :class="[
              'w-full rounded-xl border px-4 py-3 text-sm outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-200',
              nameLiveError || form.errors.name
                ? 'border-red-300 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60'
                : 'border-slate-300 focus:border-indigo-400 focus:ring-indigo-100 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20',
            ]"
            :placeholder="$t('Enter student name')"
          />
          <p v-if="nameLiveError || form.errors.name" class="mt-1 text-xs text-red-600">
            {{ nameLiveError || form.errors.name }}
          </p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
            {{ $t('Gender') }}
          </label>
          <SelectSearch
            v-model="form.gender"
            :options="genderOptions"
            :searchable="false"
            :clearable="false"
            :placeholder="$t('Select gender')"
            button-class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-3 text-left text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
          />
          <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">
            {{ form.errors.gender }}
          </p>
        </div>

        <div>
          <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
            {{ $t('Phone') }}
          </label>
          <input
            v-model="form.phone"
            type="text"
            inputmode="numeric"
            autocomplete="tel"
            pattern="[0-9]*"
            maxlength="12"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
            :placeholder="$t('Enter phone number')"
            @input="normalizePhoneInput"
          />
          <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
            {{ form.errors.phone }}
          </p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            @click="close"
          >
            {{ $t('Cancel') }}
          </button>
          <button
            type="submit"
            :disabled="form.processing || !!nameLiveError"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <UserPlus class="h-4 w-4" />
            {{ form.processing ? $t('Saving...') : $t('Register Student') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
