<script setup>
import { computed, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { Save } from "@lucide/vue";
import axios from "axios";
import { SelectSearch } from "@/components/ui/select-search";

function toStringOrEmpty(value) {
  return value === null || value === undefined || value === "" ? "" : String(value);
}

function todayIso() {
  return new Date().toLocaleDateString("en-CA"); // YYYY-MM-DD, matches <input type="date">
}

const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
// Admins/super admins assign a class to an instructor; the instructor then picks
// the Building/Floor/Room once they take ownership of the class.
const isAdminUser = computed(() => roles.value.includes("super_admin") || roles.value.includes("admin"));

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
  scheduleGroups: props.options?.scheduleGroups ?? [],
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

const selectedScheduleType = ref("");
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
  course_id: toStringOrEmpty(props.classData?.course_id),
  lesson_id: toStringOrEmpty(props.classData?.lesson_id),
  teacher_id: toStringOrEmpty(props.classData?.teacher_id),
  building_id: toStringOrEmpty(props.classData?.building_id),
  floor_id: toStringOrEmpty(props.classData?.floor_id),
  room_id: toStringOrEmpty(props.classData?.room_id),
  term_id: toStringOrEmpty(props.classData?.term_id),
  time_id: toStringOrEmpty(props.classData?.time_id),
  class_type: props.classData?.class_type ?? "physical",
  status: props.classData?.status ?? "active",
  study_days: props.classData?.study_days ?? [],
  start_time: props.classData?.start_time ?? "",
  end_time: props.classData?.end_time ?? "",
  capacity: props.classData?.capacity ?? 20,
  price: props.classData?.price ?? 0,
  document_price: props.classData?.document_price ?? 0,
  // Enrollment opens the day the class is created unless overridden.
  enrollment_start_date: props.classData?.enrollment_start_date ?? todayIso(),
  start_date: props.classData?.start_date ?? "",
  end_date: props.classData?.end_date ?? "",
});

const submitLabel = computed(() => (props.mode === "edit" ? "Update Class" : "Save Class"));

const selectedCourse = computed(() =>
  options.value.courses.find((course) => String(course.id) === String(form.course_id))
);

const selectedRoom = computed(() =>
  rooms.value.find((room) => String(room.id) === String(form.room_id))
);

const courseOptions = computed(() =>
  options.value.courses.map((course) => ({ label: course.title, value: String(course.id) }))
);

const teacherOptions = computed(() =>
  options.value.teachers.map((teacher) => ({ label: teacher.name, value: String(teacher.id) }))
);

const buildingOptions = computed(() =>
  options.value.buildings.map((building) => ({ label: building.name, value: String(building.id) }))
);

const floorOptions = computed(() =>
  floors.value.map((floor) => ({ label: floor.name, value: String(floor.id) }))
);

const roomOptions = computed(() =>
  rooms.value.map((room) => ({ label: room.room_number, value: String(room.id) }))
);

const selectedScheduleGroup = computed(() =>
  options.value.scheduleGroups.find(
    (group) => String(group.class_type_id) === String(selectedScheduleType.value)
  )
);

const selectedSchedule = computed(() =>
  selectedScheduleGroup.value?.schedules.find(
    (schedule) => String(schedule.term_id) === String(selectedTerm.value)
  )
);

const scheduleTypeOptions = computed(() =>
  options.value.scheduleGroups.map((group) => ({
    label: group.class_type_name,
    value: String(group.class_type_id),
  }))
);

// Basic class schedules mix two kinds of term: the generic Mon & Thu / Sat & Sun pair used
// on the enrollment receipt, and specific day-splits (Mon & Tue, Wed & Thu, ...) that reflect
// how a course actually runs once the instructor sets the real schedule. Admins creating a
// Basic class only pick the generic receipt term; the assigned instructor later narrows it
// down to the real one.
const RECEIPT_ONLY_TERM_NAMES = ["Mon & Thu", "Sat & Sun"];

const scheduleTermOptions = computed(() => {
  const schedules = selectedScheduleGroup.value?.schedules ?? [];
  const restrictToReceiptTerms = isAdminUser.value && selectedScheduleGroup.value?.class_type_name === "Basic";
  const visibleSchedules = restrictToReceiptTerms
    ? schedules.filter((schedule) => RECEIPT_ONLY_TERM_NAMES.includes(schedule.term_name))
    : schedules;

  return visibleSchedules.map((schedule) => ({
    label: schedule.term_name,
    value: String(schedule.term_id),
  }));
});

const scheduleTimeOptions = computed(() =>
  (selectedSchedule.value?.times ?? []).map((time) => ({
    label: time.time_name,
    value: String(time.id),
  }))
);

const floorPlaceholder = computed(() => (loading.value.floors ? "Loading..." : "Select Floor"));
const roomPlaceholder = computed(() => (loading.value.rooms ? "Loading..." : "Select Room"));

const selectClass =
  "flex w-full items-center justify-between rounded-xl border border-slate-300 px-4 py-3 text-left text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:disabled:bg-gray-900 dark:disabled:text-gray-500";

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

function parseTermDays(termName) {
  return String(termName ?? "")
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
const matchedTerm = terms.value.find((term) => {
  const parsedDays = parseTermDays(term.term_name);
  return parsedDays.length > 0 &&
    parsedDays.length === (form.study_days ?? []).length &&
    parsedDays.every((day) => (form.study_days ?? []).includes(day));
});

selectedTerm.value = toStringOrEmpty(props.classData?.term_id ?? matchedTerm?.id);
selectedTime.value = toStringOrEmpty(props.classData?.time_id ?? matchedTime?.id);
selectedScheduleType.value = toStringOrEmpty(
  options.value.scheduleGroups.find((group) =>
    group.schedules.some(
      (schedule) =>
        String(schedule.term_id) === String(selectedTerm.value) &&
        schedule.times.some((time) => String(time.id) === String(selectedTime.value))
    )
  )?.class_type_id
);
form.term_id = selectedTerm.value;
form.time_id = selectedTime.value;

// Class Type -> Study Term -> Study Time cascade: changing a parent clears its children,
// since the child's valid options come from the newly selected parent's schedule.
watch(selectedScheduleType, () => {
  selectedTerm.value = "";
});

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
    form.start_time = parsed.startTime;
    form.end_time = parsed.endTime;
  }
);

watch(
  selectedTerm,
  (termId) => {
    form.term_id = termId ?? "";
    selectedTime.value = "";

    const term = terms.value.find((item) => String(item.id) === String(termId));
    if (!term) {
      form.study_days = [];
      return;
    }

    form.study_days = parseTermDays(term.term_name);
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

    if (!courseId) return;

    loading.value.lessons = true;
    try {
      const response = await axios.get(`/dashboard/enroll/courses/${courseId}/lessons`);
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
      const response = await axios.get(`/dashboard/enroll/buildings/${buildingId}/floors`);
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
      const response = await axios.get(`/dashboard/enroll/floors/${floorId}/rooms`);
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

const classTypeOptions = computed(() =>
  options.value.classTypes.map((type) => ({
    label: classTypeLabel(type),
    value: String(classTypeValue(type)),
  }))
);

function back() {
  router.get("/dashboard/enroll");
}

function submit() {
  if (props.mode === "edit") {
    form.put(`/dashboard/enroll/${props.classData.id}`);
    return;
  }

  form.post("/dashboard/enroll");
}
</script>

<template>
  <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-slate-200 p-4 sm:p-6 lg:p-8 dark:bg-gray-900 dark:border-gray-800">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">
      <div>
        <label class="font-semibold mb-2 block">{{ $t('Course') }}</label>
        <SelectSearch
          v-model="form.course_id"
          :options="courseOptions"
          :placeholder="$t('Select Course')"
          :button-class="selectClass"
        />
        <p v-if="form.errors.course_id || form.errors.title" class="mt-1 text-xs text-red-600">
          {{ form.errors.course_id || form.errors.title }}
        </p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Status') }}</label>
        <SelectSearch
          v-model="form.class_type"
          :options="classTypeOptions"
          :placeholder="$t('Select Status')"
          :button-class="selectClass"
        />
        <p v-if="form.errors.class_type" class="mt-1 text-xs text-red-600">{{ form.errors.class_type }}</p>
      </div>

      <input v-model="form.status" type="hidden" />

      <div v-if="isAdminUser">
        <label class="font-semibold mb-2 block">{{ $t('Instructor') }}</label>
        <SelectSearch
          v-model="form.teacher_id"
          :options="teacherOptions"
          :placeholder="$t('Select Instructor')"
          :button-class="selectClass"
        />
        <p v-if="form.errors.teacher_id" class="mt-1 text-xs text-red-600">{{ form.errors.teacher_id }}</p>
        <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">{{ $t('The assigned instructor sets the Building, Floor, and Room.') }}</p>
      </div>

      <div v-if="!isAdminUser">
        <label class="font-semibold mb-2 block">{{ $t('Building') }}</label>
        <SelectSearch
          v-model="form.building_id"
          :options="buildingOptions"
          :disabled="form.class_type === 'online'"
          :placeholder="$t('Select Building')"
          :button-class="selectClass"
        />
      </div>

      <div v-if="!isAdminUser">
        <label class="font-semibold mb-2 block">{{ $t('Floor') }}</label>
        <SelectSearch
          v-model="form.floor_id"
          :options="floorOptions"
          :disabled="form.class_type === 'online' || loading.floors || !form.building_id"
          :placeholder="$t(floorPlaceholder)"
          :button-class="selectClass"
        />
      </div>

      <div v-if="!isAdminUser">
        <label class="font-semibold mb-2 block">{{ $t('Room') }}</label>
        <SelectSearch
          v-model="form.room_id"
          :options="roomOptions"
          :disabled="form.class_type === 'online' || loading.rooms || !form.floor_id"
          :placeholder="$t(roomPlaceholder)"
          :button-class="selectClass"
        />
        <p v-if="form.errors.room_id" class="mt-1 text-xs text-red-600">{{ form.errors.room_id }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Class Type') }}</label>
        <SelectSearch
          v-model="selectedScheduleType"
          :options="scheduleTypeOptions"
          :placeholder="$t('Select Class Type')"
          :button-class="selectClass"
        />
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Study Term') }}</label>
        <SelectSearch
          v-model="selectedTerm"
          :options="scheduleTermOptions"
          :disabled="!selectedScheduleType"
          :placeholder="$t(selectedScheduleType ? 'Select Term' : 'Select Class Type first')"
          :button-class="selectClass"
        />
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Study Time') }}</label>
        <SelectSearch
          v-model="selectedTime"
          :options="scheduleTimeOptions"
          :disabled="!selectedTerm"
          :placeholder="$t(selectedTerm ? 'Select Time' : 'Select Term first')"
          :button-class="selectClass"
        />
        <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
        <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Capacity') }}</label>
        <input type="number" min="1" v-model="form.capacity" :readonly="form.class_type !== 'online'" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" :class="form.class_type !== 'online' ? 'bg-slate-50 text-slate-500 dark:bg-gray-800/60 dark:text-gray-400' : ''" />
        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Price') }}</label>
        <input type="number" min="0" step="0.01" v-model="form.price" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Document Price') }}</label>
        <input type="number" min="0" step="0.01" v-model="form.document_price" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.document_price" class="mt-1 text-xs text-red-600">{{ form.errors.document_price }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Start Date') }}</label>
        <input type="date" v-model="form.start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
      </div>

      <div>
        <label class="font-semibold mb-2 block">{{ $t('Start EnRoll') }}</label>
        <input type="date" v-model="form.enrollment_start_date" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
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
