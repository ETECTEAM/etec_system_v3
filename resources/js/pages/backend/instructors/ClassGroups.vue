<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "@/composables/useToast";
import { Save, RefreshCw, Users, UserMinus, Search, Shuffle, MousePointerClick } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";

const toast = useToast();

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
  savedTeams: {
    type: Array,
    default: () => [],
  },
});

const errors = ref({});
const saving = ref(false);
const teamCount = ref(Math.max(1, props.savedTeams.length || 3));
const teamsDraft = ref([]);
const draggedStudentId = ref(null);
const dragOverTeamIndex = ref(null);
const generationMode = ref("manual");
const rosterQuery = ref("");

const generationMethods = [
  {
    value: "manual",
    label: "Instructor select",
    description: "Create empty teams, then drag students in yourself.",
    icon: MousePointerClick,
  },
  {
    value: "auto",
    label: "Auto generate",
    description: "Fill teams automatically with a balanced random split.",
    icon: Shuffle,
  },
];

const breadcrumbItems = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: props.classData.title, href: `/dashboard/instructor/classes/${props.classData.id}/attendance` },
  { label: "Team Management", current: true },
]);

const maxTeams = computed(() => Math.max(1, props.students.length || 1));
const hasStudents = computed(() => props.students.length > 0);
const hasGeneratedArrangement = computed(() =>
  teamsDraft.value.length > 0 || assignedStudentIds.value.size > 0
);

function normalizeTeam(team, index) {
  return {
    team_name: team.team_name || `Team ${index + 1}`,
    project_topic: team.project_topic || "",
    student_ids: Array.isArray(team.member_ids) ? [...team.member_ids] : [],
  };
}

function createEmptyTeams(count) {
  return Array.from({ length: count }, (_, index) => normalizeTeam({}, index));
}

function shuffleStudents(students) {
  const shuffled = [...students];

  for (let index = shuffled.length - 1; index > 0; index -= 1) {
    const swapIndex = Math.floor(Math.random() * (index + 1));
    [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
  }

  return shuffled;
}

function setDraftTeamsFromSaved() {
  if (props.savedTeams.length > 0) {
    teamsDraft.value = props.savedTeams.map((team, index) => normalizeTeam(team, index));
    teamCount.value = props.savedTeams.length;
    return;
  }

  teamsDraft.value = [];
}

watch(
  () => props.savedTeams,
  () => setDraftTeamsFromSaved(),
  { immediate: true },
);

const assignedStudentIds = computed(() => {
  const ids = new Set();

  teamsDraft.value.forEach((team) => {
    (team.student_ids || []).forEach((studentId) => ids.add(Number(studentId)));
  });

  return ids;
});

const assignedCount = computed(() => assignedStudentIds.value.size);
const unassignedCount = computed(() => props.students.length - assignedCount.value);

const unassignedStudents = computed(() => {
  const pool = props.students.filter((student) => !assignedStudentIds.value.has(student.id));
  const query = rosterQuery.value.trim().toLowerCase();

  if (!query) {
    return pool;
  }

  return pool.filter((student) => student.name?.toLowerCase().includes(query));
});

function assignedTeamIndex(studentId) {
  return teamsDraft.value.findIndex((team) => team.student_ids.includes(studentId));
}

function removeStudent(teamIndex, studentId) {
  const currentTeam = teamsDraft.value[teamIndex];

  if (!currentTeam) {
    return;
  }

  currentTeam.student_ids = currentTeam.student_ids.filter((id) => id !== studentId);
}

function confirmReplaceArrangement() {
  if (!hasGeneratedArrangement.value) {
    return true;
  }

  return window.confirm("Replace the current unsaved team arrangement?");
}

function selectGenerationMode(mode) {
  if (mode === generationMode.value) {
    return;
  }

  if (!confirmReplaceArrangement()) {
    return;
  }

  generationMode.value = mode;
  teamsDraft.value = [];
  errors.value = {};
}

function normalizedTeamCount() {
  const count = Math.max(1, Math.min(Number(teamCount.value) || 1, maxTeams.value));
  teamCount.value = count;

  return count;
}

function startDrag(studentId, event) {
  draggedStudentId.value = studentId;
  event.dataTransfer.effectAllowed = "move";
  event.dataTransfer.setData("text/plain", String(studentId));
}

function dropStudent(teamIndex, event) {
  event.preventDefault();
  dragOverTeamIndex.value = null;

  const rawStudentId = event.dataTransfer.getData("text/plain") || draggedStudentId.value;
  const studentId = Number(rawStudentId);

  if (!studentId) {
    return;
  }

  const assignedIndex = assignedTeamIndex(studentId);
  const targetTeam = teamsDraft.value[teamIndex];

  if (!targetTeam) {
    return;
  }

  if (assignedIndex === teamIndex) {
    draggedStudentId.value = null;
    return;
  }

  if (assignedIndex !== -1) {
    teamsDraft.value[assignedIndex].student_ids = teamsDraft.value[assignedIndex].student_ids.filter((id) => id !== studentId);
  }

  if (!targetTeam.student_ids.includes(studentId)) {
    targetTeam.student_ids = [...targetTeam.student_ids, studentId];
  }

  draggedStudentId.value = null;
}

function generateTeams() {
  if (!hasStudents.value) {
    toast.error("No students are enrolled in this class yet.");
    return;
  }

  if (!confirmReplaceArrangement()) {
    return;
  }

  const count = normalizedTeamCount();
  teamsDraft.value = createEmptyTeams(count);
  errors.value = {};
}

function autoGenerateTeams() {
  if (!hasStudents.value) {
    toast.error("No students are enrolled in this class yet.");
    return;
  }

  if (!confirmReplaceArrangement()) {
    return;
  }

  const count = normalizedTeamCount();
  const generatedTeams = createEmptyTeams(count);

  shuffleStudents(props.students).forEach((student, index) => {
    generatedTeams[index % count].student_ids.push(student.id);
  });

  teamsDraft.value = generatedTeams;
  errors.value = {};
}

function runGenerateAction() {
  if (generationMode.value === "auto") {
    autoGenerateTeams();
    return;
  }

  generateTeams();
}

function teamError(index, field) {
  return errors.value[`teams.${index}.${field}`]?.[0] ?? null;
}

function generalError() {
  return errors.value.teams?.[0] ?? errors.value.teams_count?.[0] ?? null;
}

async function saveTeams() {
  if (!teamsDraft.value.length) {
    toast.error("Generate teams first before saving.");
    return;
  }

  saving.value = true;
  errors.value = {};

  try {
    const payload = {
      teams_count: teamCount.value,
      teams: teamsDraft.value.map((team) => ({
        team_name: team.team_name,
        project_topic: team.project_topic,
        student_ids: team.student_ids,
      })),
    };

    const response = await axios.put(`/dashboard/instructor/classes/${props.classData.id}/groups`, payload);

    teamsDraft.value = (response.data?.teams ?? []).map((team, index) => normalizeTeam(team, index));
    teamCount.value = teamsDraft.value.length;
    toast.success(response.data?.message ?? "Teams saved successfully.");
  } catch (error) {
    errors.value = error.response?.data?.errors ?? {};
    toast.error(error.response?.data?.message ?? "Failed to save teams.");
  } finally {
    saving.value = false;
  }
}

function draggedMemberClass(studentId, gender) {
  if (draggedStudentId.value === studentId) {
    return "border-cyan-300 bg-cyan-50 text-cyan-700 opacity-60 dark:border-cyan-500/40 dark:bg-cyan-500/10 dark:text-cyan-300";
  }

  if (gender === "female") {
    return "border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 hover:shadow-sm";
  }

  return "border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300 hover:shadow-sm";
}

function studentRosterClass(studentId) {
  return draggedStudentId.value === studentId
    ? "border-cyan-300 bg-cyan-50 opacity-60 dark:border-cyan-500/40 dark:bg-cyan-500/10"
    : "border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-950 cursor-grab hover:border-cyan-300 hover:bg-cyan-50/50 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/5";
}
</script>

<template>
  <Head :title="`${classData.title} Team Management`" />

  <DashboardLayout>
    <section class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <h1 class="mt-3 text-2xl font-black text-blue-950 dark:text-gray-100 sm:text-3xl">{{ classData.title }}</h1>
          <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">Team management</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            <RefreshCw class="h-4 w-4" />
            Back to attendance
          </Link>
          <button
            type="button"
            :disabled="saving || !teamsDraft.length"
            @click="saveTeams"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
          >
            <Save class="h-4 w-4" />
            {{ saving ? "Saving…" : "Save teams" }}
          </button>
        </div>
      </div>

      <!-- Setup bar -->
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="grid gap-4 sm:grid-cols-2 lg:flex lg:flex-1 lg:items-end">
            <div>
              <span class="mb-2 block text-xs font-semibold text-slate-700 dark:text-gray-300">Generation method</span>
              <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-gray-800 dark:bg-gray-950">
                <button
                  v-for="method in generationMethods"
                  :key="method.value"
                  type="button"
                  @click="selectGenerationMode(method.value)"
                  class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition"
                  :class="generationMode === method.value
                    ? 'bg-white text-cyan-700 shadow-sm dark:bg-gray-800 dark:text-cyan-300'
                    : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >
                  <component :is="method.icon" class="h-4 w-4" />
                  {{ method.label }}
                </button>
              </div>
            </div>

            <label class="block sm:w-40">
              <span class="mb-2 block text-xs font-semibold text-slate-700 dark:text-gray-300">Number of teams</span>
              <input
                v-model.number="teamCount"
                type="number"
                min="1"
                :max="maxTeams"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
              />
            </label>
          </div>

          <div class="flex items-center gap-3">
            <p class="hidden text-xs font-semibold text-slate-500 dark:text-gray-400 sm:block">
              Up to {{ maxTeams }} teams for this roster
            </p>
            <button
              type="button"
              :disabled="saving || !hasStudents"
              @click="runGenerateAction"
              class="inline-flex h-11 items-center gap-2 whitespace-nowrap rounded-xl bg-cyan-500 px-5 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-cyan-600 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
            >
              <Users class="h-4 w-4" />
              {{ generationMode === "auto" ? "Auto generate teams" : "Generate teams" }}
            </button>
          </div>
        </div>
        <p v-if="generalError()" class="mt-3 text-xs font-semibold text-red-600">{{ generalError() }}</p>
      </div>

      <!-- Main content: teams + roster -->
      <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div>
          <div v-if="!teamsDraft.length" class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
            <Users class="mx-auto h-10 w-10 text-slate-400" />
            <p class="mt-4 text-lg font-black text-slate-900 dark:text-gray-100">No teams yet</p>
            <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-slate-500 dark:text-gray-400">
              Choose a generation method above and set a team count, then generate to start building the team structure.
            </p>
          </div>

          <div v-else class="grid gap-4 lg:grid-cols-2">
            <article
              v-for="(team, teamIndex) in teamsDraft"
              :key="teamIndex"
              class="flex flex-col rounded-2xl border bg-white p-5 shadow-sm transition dark:bg-gray-900"
              :class="dragOverTeamIndex === teamIndex
                ? 'border-cyan-400 ring-2 ring-cyan-100 dark:border-cyan-500/50 dark:ring-cyan-500/10'
                : 'border-slate-200 dark:border-gray-800'"
              @dragover.prevent="dragOverTeamIndex = teamIndex"
              @dragleave="dragOverTeamIndex = null"
              @drop="dropStudent(teamIndex, $event)"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="grid flex-1 gap-3">
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300">Team name</span>
                    <input
                      v-model="team.team_name"
                      type="text"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
                    />
                    <p v-if="teamError(teamIndex, 'team_name')" class="mt-1 text-xs font-semibold text-red-600">{{ teamError(teamIndex, 'team_name') }}</p>
                  </label>

                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-gray-300">Project topic <span class="font-medium text-slate-400">(optional)</span></span>
                    <input
                      v-model="team.project_topic"
                      type="text"
                      placeholder="Leave blank for now"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
                    />
                    <p v-if="teamError(teamIndex, 'project_topic')" class="mt-1 text-xs font-semibold text-red-600">{{ teamError(teamIndex, 'project_topic') }}</p>
                  </label>
                </div>

                <span class="shrink-0 rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">
                  {{ team.student_ids.length }}
                </span>
              </div>

              <div
                class="mt-4 flex min-h-24 flex-1 flex-wrap content-start gap-2 rounded-xl border-2 border-dashed p-3 transition"
                :class="dragOverTeamIndex === teamIndex
                  ? 'border-cyan-300 bg-cyan-50/60 dark:border-cyan-500/40 dark:bg-cyan-500/5'
                  : 'border-slate-200 bg-slate-50/70 dark:border-gray-800 dark:bg-gray-950/50'"
              >
                <button
                  v-for="studentId in team.student_ids"
                  :key="studentId"
                  type="button"
                  draggable="true"
                  class="inline-flex cursor-grab items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:opacity-90 active:cursor-grabbing"
                  :class="draggedMemberClass(studentId, props.students.find((student) => student.id === studentId)?.gender)"
                  @dragstart="startDrag(studentId, $event)"
                  @dragend="draggedStudentId = null"
                  @click="removeStudent(teamIndex, studentId)"
                >
                  {{ props.students.find((student) => student.id === studentId)?.name }}
                  <UserMinus class="h-3.5 w-3.5" />
                </button>
                <span v-if="!team.student_ids.length" class="self-center text-sm font-medium text-slate-400 dark:text-gray-500">
                  Drag students here from the roster
                </span>
              </div>
            </article>
          </div>
        </div>

        <!-- Roster -->
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-6 xl:self-start">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">Roster</h2>
            <span class="rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-0.5 text-xs font-bold text-cyan-700 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-300">
              {{ unassignedStudents.length }} unassigned
            </span>
          </div>

          <div v-if="students.length" class="relative mt-4">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="rosterQuery"
              type="text"
              placeholder="Search students…"
              class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
            />
          </div>

          <div class="mt-4 max-h-[60vh] space-y-2 overflow-y-auto pr-1 xl:max-h-[65vh]">
            <div
              v-for="student in unassignedStudents"
              :key="student.id"
              :class="['rounded-xl border px-3 py-2.5 transition', studentRosterClass(student.id)]"
              draggable="true"
              @dragstart="startDrag(student.id, $event)"
              @dragend="draggedStudentId = null"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold text-slate-900 dark:text-gray-100">{{ student.name }}</p>
                  <p class="text-xs font-semibold text-slate-500 dark:text-gray-400">ID #{{ student.id }}</p>
                </div>
                <span
                  class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.1em]"
                  :class="student.gender === 'female'
                    ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                    : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300'"
                >
                  {{ student.gender || 'other' }}
                </span>
              </div>
            </div>

            <p v-if="students.length && !unassignedStudents.length && !rosterQuery" class="pt-6 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-300">
              All students are assigned to teams.
            </p>
            <p v-if="rosterQuery && !unassignedStudents.length" class="pt-6 text-center text-sm font-medium text-slate-500 dark:text-gray-400">
              No unassigned students match "{{ rosterQuery }}".
            </p>
          </div>

          <p v-if="!students.length" class="mt-4 text-sm font-medium text-slate-500 dark:text-gray-400">No students are enrolled in this class yet.</p>
        </aside>
      </div>
    </section>
  </DashboardLayout>
</template>
