<script setup>
import { useForm } from '@inertiajs/vue3'
import { nextTick, onMounted, ref, computed, watch } from 'vue'

const props = defineProps({
  classTypes: Array,
  terms: Array,
  times: Array,
})

const emit = defineEmits(['close'])

const form = useForm({
  class_type_id: '',
  term_id: '',
  time_ids: [],
})

const selectRef = ref(null)

onMounted(() => {
  nextTick(() => {
    selectRef.value?.focus()
  })
})

const filteredTimes = computed(() => {
  if (!form.term_id) return []
  return props.times.filter(time => time.term_id == form.term_id)
})

watch(() => form.term_id, () => {
  form.time_ids = []
})

function submit() {
  form.post('/dashboard/schdule', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      emit('close')
    }
  })
}
</script>

<template>
  <div>

    <!-- HEADER -->
    <div class="text-center mb-4">
      <h2 class="text-lg font-bold text-gray-900">
        Create Schedule
      </h2>
      <p class="text-sm text-gray-500">
        Add a new schedule with class type, term, and times
      </p>
    </div>

    <!-- FORM -->
    <form
      @submit.prevent="submit"
      class="rounded-2xl border bg-white p-6 shadow-sm space-y-5"
    >

      <!-- CLASS TYPE SELECT -->
      <div>
        <label class="text-sm font-medium text-gray-700">
          Class Type
        </label>

        <select
          ref="selectRef"
          v-model="form.class_type_id"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm bg-gray-50
                 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition"
        >
          <option value="">Select Class Type</option>

          <option
            v-for="ct in classTypes"
            :key="ct.class_type_id"
            :value="ct.class_type_id"
          >
            {{ ct.type_name }}
          </option>
        </select>

        <p v-if="form.errors.class_type_id" class="text-red-500 text-sm mt-1">
          {{ form.errors.class_type_id }}
        </p>
      </div>

      <!-- TERM SELECT -->
      <div>
        <label class="text-sm font-medium text-gray-700">
          Term
        </label>

        <select
          v-model="form.term_id"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm bg-gray-50
                 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition"
        >
          <option value="">Select Term</option>

          <option
            v-for="term in terms"
            :key="term.id"
            :value="term.id"
          >
            {{ term.term_name }}
          </option>
        </select>

        <p v-if="form.errors.term_id" class="text-red-500 text-sm mt-1">
          {{ form.errors.term_id }}
        </p>
      </div>

      <!-- TIME SLOTS CHECKBOX LIST -->
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-2">
          Time Slots
        </label>

        <div v-if="!form.term_id" class="text-sm text-gray-400 bg-gray-50 rounded-xl p-4 text-center border border-dashed border-gray-200">
          Please select a term first to load times
        </div>

        <div v-else-if="filteredTimes.length === 0" class="text-sm text-gray-400 bg-gray-50 rounded-xl p-4 text-center border border-dashed border-gray-200">
          No time slots found for this term
        </div>

        <div
          v-else
          class="border rounded-xl divide-y divide-slate-100 max-h-64 overflow-y-auto bg-slate-50/50 shadow-inner"
        >
          <div
            v-for="time in filteredTimes"
            :key="time.id"
            class="flex items-center justify-between px-4 py-3 hover:bg-white transition cursor-pointer"
            @click="form.time_ids.includes(time.id) ? form.time_ids = form.time_ids.filter(id => id !== time.id) : form.time_ids.push(time.id)"
          >
            <span class="text-sm text-slate-700 font-medium select-none">
              {{ time.time_name }}
            </span>
            <input
              type="checkbox"
              :value="time.id"
              v-model="form.time_ids"
              @click.stop
              class="h-4.5 w-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
            />
          </div>
        </div>

        <p v-if="form.errors.time_ids" class="text-red-500 text-sm mt-1">
          {{ form.errors.time_ids }}
        </p>
      </div>

      <!-- ACTIONS -->
      <div class="flex justify-end gap-3 pt-2">

        <button
          type="button"
          @click="$emit('close')"
          class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 active:bg-orange-300 cursor-pointer"
        >
          Cancel
        </button>

        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
        >
          {{ form.processing ? 'Creating...' : 'Create Schedule' }}
        </button>

      </div>

    </form>

  </div>
</template>
