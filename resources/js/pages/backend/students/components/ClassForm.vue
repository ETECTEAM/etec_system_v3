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
    default: () => ({}),
  },
  mode: {
    type: String,
    default: "create",
  },
});

const options = computed(() => ({
  courses: props.options?.courses ?? [],
  teachers: props.options?.teachers ?? [],
  buildings: props.options?.buildings ?? [],
  floors: props.options?.floors ?? [],
  rooms: props.options?.rooms ?? [],
  lessons: props.options?.lessons ?? [],
  terms: props.options?.terms ?? [],
  times: props.options?.times ?? [],
  classTypes: props.options?.classTypes ?? [
    { value: "physical", label: "Physical Class" },
    { value: "online", label: "Online Class" },
  ],
  studyDays: props.options?.studyDays ?? [],
}));

const floors = ref([...options.value.floors]);
const rooms = ref([...options.value.rooms]);
const lessons = ref([...options.value.lessons]);
const terms = ref([...options.value.terms]);
const times = ref([...options.value.times]);
const loading = ref({
  floors: false,
  rooms: false,
  lessons: false,
});

const selectedTerm = ref("");
const selectedTime = ref("");

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
  course_id: props.classData?.course_id ?? "",
  lesson_id: props.classData?.lesson_id ?? "",
  teacher_id: props.classData?.teacher_id ?? "",
  building_id: props.classData?.building_id ?? "",
  floor_id: props.classData?.floor_id ?? "",
  room_id: props.classData?.room_id ?? "",
  term_id: props.classData?.term_id ?? "",
  time_id: props.classData?.time_id ?? "",
  class_type: props.classData?.class_type ?? "physical",
  status: props.classData?.status ?? "active",
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

const filteredTimes = computed(() => times.value);

const selectedCourse = computed(() =>
  options.value.courses.find((course) => String(course.id) === String(form.course_id))
);

const selectedRoom = computed(() =>
  rooms.value.find((room) => String(room.id) === String(form.room_id))
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
selectedTerm.value = props.classData?.term_id ?? "";
selectedTime.value = props.classData?.time_id ?? matchedTime?.id ?? "";
form.term_id = selectedTerm.value;
form.time_id = selectedTime.value;

watch(
  selectedTime,
  (timeId) => {
    form.time_id = timeId ?? "";

    const time = times.value.find((item) => String(item.id) === String(timeId));
    if (!time) {
      form.start_time = "";
      form.end_time = "";
      return;
    }

    const parsed = parseTimeOption(time);
    form.study_days = parsed.studyDays;
    form.start_time = parsed.startTime;
    form.end_time = parsed.endTime;
  }
);

watch(
  selectedTerm,
  (termId) => {
    form.term_id = termId ?? "";
  }
);

watch(
  () => form.course_id,
  async (courseId, oldCourseId) => {
    if (oldCourseId !== undefined) {
      form.lesson_id = "";
    }

    lessons.value = [];
    const course = options.value.courses.find((item) => String(item.id) === String(courseId));
    form.title = course?.title ?? "";
    form.price = course?.price ?? "";

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
  () => form.room_id,
  () => {
    if (form.class_type === "online") return;
    form.capacity = selectedRoom.value?.capacity ?? "";
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
      form.capacity = props.classData?.capacity ?? form.capacity ?? 20;
    }
  }
);

function classTypeLabel(type) {
  if (typeof type === "object") return type.label;
  return type === "online" ? "Online Class" : "Physical Class";
}

function classTypeValue(type) {
  return typeof type === "object" ? type.value : type;
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
        <label class="font-semibold mb-2 block">{{ $t('Course') }}</label>
        <select v-model.number="form.course_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ $t('Select Course') }}</option>
          <option v-for="course in options.courses" :key="course.id" :value="course.id">{{ course.title }}</option>
        </select>
        <p v-if="form.errors.course_id || form.errors.title" class="mt-1 text-xs text-red-600">
          {{ form.errors.course_id || form.errors.title }}
        </p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Status') }}</label>
        <select v-model="form.class_type" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ $t('Select Status') }}</option>
          <option v-for="type in options.classTypes" :key="classTypeValue(type)" :value="classTypeValue(type)">{{ classTypeLabel(type) }}</option>
        </select>
        <p v-if="form.errors.class_type" class="mt-1 text-xs text-red-600">{{ form.errors.class_type }}</p>
      </div>

      <input v-model="form.status" type="hidden" />

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Building') }}</label>
        <select v-model.number="form.building_id" :disabled="form.class_type === 'online'" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ $t('Select Building') }}</option>
          <option v-for="building in options.buildings" :key="building.id" :value="building.id">{{ building.name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Floor') }}</label>
        <select v-model.number="form.floor_id" :disabled="form.class_type === 'online' || loading.floors || !form.building_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ loading.floors ? "Loading..." : "Select Floor" }}</option>
          <option v-for="floor in floors" :key="floor.id" :value="floor.id">{{ floor.name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Room') }}</label>
        <select v-model.number="form.room_id" :disabled="form.class_type === 'online' || loading.rooms || !form.floor_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ loading.rooms ? "Loading..." : "Select Room" }}</option>
          <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.room_number }}</option>
        </select>
        <p v-if="form.errors.room_id" class="mt-1 text-xs text-red-600">{{ form.errors.room_id }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Study Term') }}</label>
        <select v-model="selectedTerm" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ $t('Select Term') }}</option>
          <option v-for="term in terms" :key="term.id" :value="term.id">{{ term.term_name }}</option>
        </select>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Study Time') }}</label>
        <select v-model="selectedTime" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          <option value="">{{ $t('Select Time') }}</option>
          <option v-for="time in filteredTimes" :key="time.id" :value="time.id">{{ time.time_name }}</option>
        </select>
        <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
        <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Capacity') }}</label>
        <input type="number" min="1" v-model="form.capacity" :readonly="form.class_type !== 'online'" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" :class="form.class_type !== 'online' ? 'bg-slate-50 text-slate-500 dark:bg-gray-800/60 dark:text-gray-400' : ''" />
        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Price') }}</label>
        <input type="number" min="0" step="0.01" v-model="form.price" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Start EnRoll') }}</label>
        <input type="date" v-model="form.enrollment_start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Start Date') }}</label>
        <input type="date" v-model="form.start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
      </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6 sm:mt-8 lg:mt-10">
      <button @click="back" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
        {{ $t('Cancel') }}
      </button>

      <button @click="submit" type="button" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500">
        <Save class="w-4 h-4" />
        {{ form.processing ? $t("Saving...") : $t(submitLabel) }}
      </button>
    </div>
  </div>
</template>
