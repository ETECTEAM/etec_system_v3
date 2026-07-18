<script setup>
import { computed, ref, watch } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { Save } from "@lucide/vue";
import axios from "axios";

const props = defineProps({
  classData: {
    type: Object,
    default: null,
  },
  options: {
    type: Object,
    required: true,
  },
  mode: {
    type: String,
    default: "create",
  },
});

const floors = ref([...(props.options.floors ?? [])]);
const rooms = ref([...(props.options.rooms ?? [])]);
const lessons = ref([...(props.options.lessons ?? [])]);
const terms = ref([...(props.options.terms ?? [])]);
const times = ref([...(props.options.times ?? [])]);
const loading = ref({
  floors: false,
  rooms: false,
  lessons: false,
});

const selectedTerm = ref("");
const selectedTime = ref("");
const selectedStudyDays = ref((props.classData?.study_days ?? []).join(","));

const studyDayOptions = computed(() => {
  const days = props.options.studyDays ?? [];

  return [
    ...days.map((day) => ({ label: day, value: day })),
    { label: "Mon & Thu", value: "Monday,Thursday" },
    { label: "Tue & Fri", value: "Tuesday,Friday" },
    { label: "Sat & Sun", value: "Saturday,Sunday" },
  ];
});

const dayMap = {
  Mon: "Monday",
  Monday: "Monday",
  Tue: "Tuesday",
  Tues: "Tuesday",
  Tuesday: "Tuesday",
  Wed: "Wednesday",
  Wednesday: "Wednesday",
  Thu: "Thursday",
  Thur: "Thursday",
  Thurs: "Thursday",
  Thursday: "Thursday",
  Fri: "Friday",
  Friday: "Friday",
  Sat: "Saturday",
  Saturday: "Saturday",
  Sun: "Sunday",
  Sunday: "Sunday",
};

const form = useForm({
  title: props.classData?.title ?? "",
  course_id: props.classData?.course_id ?? props.options.courses?.[0]?.id ?? "",
  lesson_id: props.classData?.lesson_id ?? "",
  teacher_id: props.classData?.teacher_id ?? "",
  building_id: props.classData?.building_id ?? "",
  floor_id: props.classData?.floor_id ?? "",
  room_id: props.classData?.room_id ?? "",
  class_type: props.classData?.class_type ?? "physical",
  status: props.classData?.status ?? "upcoming",
  study_days: props.classData?.study_days ?? [],
  start_time: props.classData?.start_time ?? "",
  end_time: props.classData?.end_time ?? "",
  capacity: props.classData?.capacity ?? 20,
  price: props.classData?.price ?? 0,
  enrollment_start_date: props.classData?.enrollment_start_date ?? "",
  start_date: props.classData?.start_date ?? "",
  end_date: props.classData?.end_date ?? "",
});

const submitLabel = computed(() => (props.mode === "edit" ? "Update Class" : "Save Class"));

const filteredTimes = computed(() =>
  times.value.filter((time) => !selectedTerm.value || String(time.term_id) === String(selectedTerm.value))
);

function parseTimeText(value) {
  const match = String(value ?? "").trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
  if (!match) return "";

  let hour = Number(match[1]);
  const minute = match[2];
  const meridiem = match[3].toUpperCase();

  if (meridiem === "AM" && hour === 12) hour = 0;
  if (meridiem === "PM" && hour !== 12) hour += 12;

  return `${String(hour).padStart(2, "0")}:${minute}`;
}

function parseStudyDays(timeName) {
  const dayPart = String(timeName ?? "").split("(")[0].trim();
  if (!dayPart) return [];

  return dayPart
    .split(/\s*(?:-|,|&|\/|\+|and)\s*/i)
    .map((day) => dayMap[day.trim()])
    .filter(Boolean);
}

function parseTimeOption(time) {
  const range = String(time?.time_name ?? "").match(/\(([^)]+)\)/)?.[1] ?? time?.time_name ?? "";
  const [start, end] = range.split(/\s*-\s*/);

  return {
    studyDays: parseStudyDays(time?.time_name),
    startTime: parseTimeText(start),
    endTime: parseTimeText(end),
  };
}

function sameDays(first, second) {
  if (!first?.length || !second?.length) return true;
  return first.length === second.length && first.every((day) => second.includes(day));
}

function findSelectedTime() {
  return times.value.find((time) => {
    const parsed = parseTimeOption(time);

    return parsed.startTime === form.start_time &&
      parsed.endTime === form.end_time &&
      sameDays(parsed.studyDays, form.study_days ?? []);
  });
}

const matchedTime = findSelectedTime();
selectedTerm.value = matchedTime?.term_id ?? "";
selectedTime.value = matchedTime?.id ?? "";

watch(
  selectedTime,
  (timeId) => {
    const time = times.value.find((item) => String(item.id) === String(timeId));
    if (!time) {
      form.start_time = "";
      form.end_time = "";
      return;
    }

    const parsed = parseTimeOption(time);
    if (parsed.studyDays.length) {
      selectedStudyDays.value = parsed.studyDays.join(",");
    }
    form.start_time = parsed.startTime;
    form.end_time = parsed.endTime;
  }
);

watch(
  selectedStudyDays,
  (value) => {
    form.study_days = value ? value.split(",") : [];
  },
  { immediate: true }
);

watch(
  selectedTerm,
  (termId, oldTermId) => {
    if (oldTermId === undefined) return;

    const currentTime = times.value.find((time) => String(time.id) === String(selectedTime.value));
    if (currentTime && String(currentTime.term_id) === String(termId)) return;

    selectedTime.value = "";
  }
);

watch(
  () => form.course_id,
  async (courseId, oldCourseId) => {
    if (oldCourseId !== undefined) {
      form.lesson_id = "";
    }

    lessons.value = [];
    if (!courseId) return;

    loading.value.lessons = true;
    try {
      const response = await axios.get(`/dashboard/students/courses/${courseId}/lessons`);
      lessons.value = response.data;
    } finally {
      loading.value.lessons = false;
    }
  }
);

watch(
  () => form.building_id,
  async (buildingId, oldBuildingId) => {
    if (oldBuildingId !== undefined) {
      form.floor_id = "";
      form.room_id = "";
    }

    floors.value = [];
    rooms.value = [];
    if (!buildingId) return;

    loading.value.floors = true;
    try {
      const response = await axios.get(`/dashboard/students/buildings/${buildingId}/floors`);
      floors.value = response.data;
    } finally {
      loading.value.floors = false;
    }
  }
);

watch(
  () => form.floor_id,
  async (floorId, oldFloorId) => {
    if (oldFloorId !== undefined) {
      form.room_id = "";
    }

    rooms.value = [];
    if (!floorId) return;

    loading.value.rooms = true;
    try {
      const response = await axios.get(`/dashboard/students/floors/${floorId}/rooms`);
      rooms.value = response.data;
    } finally {
      loading.value.rooms = false;
    }
  }
);

watch(
  () => form.class_type,
  (classType) => {
    if (classType === "online") {
      form.building_id = "";
      form.floor_id = "";
      form.room_id = "";
      floors.value = [];
      rooms.value = [];
    }
  }
);

function classTypeLabel(type) {
  return type === "online" ? "Online Class" : "Physical Class";
}

function back() {
  router.get("/dashboard/students");
}

function submit() {
  if (props.mode === "edit") {
    form.put(`/dashboard/students/${props.classData.id}`);
    return;
  }

  form.post("/dashboard/students");
}
</script>

<template>
  <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-slate-200 p-4 sm:p-6 lg:p-8 dark:bg-gray-900 dark:border-gray-800">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">
      <div>
        <label class="font-semibold mb-2 block">Class Title</label>
        <input v-model="form.title" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" placeholder="Web Design + React.js" />
        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Status</label>
        <select v-model="form.class_type" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Status</option>
          <option v-for="type in options.classTypes" :key="type" :value="type">{{ classTypeLabel(type) }}</option>
        </select>
        <p v-if="form.errors.class_type" class="mt-1 text-xs text-red-600">{{ form.errors.class_type }}</p>
      </div>

      <input v-model="form.course_id" type="hidden" />
      <input v-model="form.status" type="hidden" />

      <div>
        <label class="font-semibold mb-2 block">Building</label>
        <select v-model.number="form.building_id" :disabled="form.class_type === 'online'" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Building</option>
          <option v-for="building in options.buildings" :key="building.id" :value="building.id">{{ building.name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Floor</label>
        <select v-model.number="form.floor_id" :disabled="form.class_type === 'online' || loading.floors || !form.building_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ loading.floors ? "Loading..." : "Select Floor" }}</option>
          <option v-for="floor in floors" :key="floor.id" :value="floor.id">{{ floor.name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Room</label>
        <select v-model.number="form.room_id" :disabled="form.class_type === 'online' || loading.rooms || !form.floor_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ loading.rooms ? "Loading..." : "Select Room" }}</option>
          <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.room_number }}</option>
        </select>
        <p v-if="form.errors.room_id" class="mt-1 text-xs text-red-600">{{ form.errors.room_id }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Study Term</label>
        <select v-model="selectedTerm" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Term</option>
          <option v-for="term in terms" :key="term.id" :value="term.id">{{ term.term_name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Study Days</label>
        <select v-model="selectedStudyDays" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Study Days</option>
          <option v-for="day in studyDayOptions" :key="day.value" :value="day.value">{{ day.label }}</option>
        </select>
        <p v-if="form.errors.study_days" class="mt-1 text-xs text-red-600">{{ form.errors.study_days }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Study Time</label>
        <select v-model="selectedTime" :disabled="!selectedTerm" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">Select Time</option>
          <option v-for="time in filteredTimes" :key="time.id" :value="time.id">{{ time.time_name }}</option>
        </select>
        <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
        <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Capacity</label>
        <input type="number" min="1" v-model="form.capacity" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Price</label>
        <input type="number" min="0" step="0.01" v-model="form.price" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">Start EnRoll</label>
        <input type="date" v-model="form.enrollment_start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
      </div>

      <div>
        <label class="font-semibold mb-2 block">Start Date</label>
        <input type="date" v-model="form.start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
      </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6 sm:mt-8 lg:mt-10">
      <button @click="back" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
        Cancel
      </button>

      <button @click="submit" type="button" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
        <Save class="w-4 h-4" />
        {{ form.processing ? "Saving..." : submitLabel }}
      </button>
    </div>
  </div>
</template>
