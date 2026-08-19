<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Save, RefreshCw, Users, UserMinus } from "@lucide/vue";

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

const breadcrumbItems = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: props.classData.title, href: `/dashboard/instructor/classes/${props.classData.id}/attendance` },
  { label: "Team Management", current: true },
]);

const maxTeams = computed(() => Math.max(1, props.students.length || 1));

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
const unassignedStudents = computed(() => props.students.filter((student) => !assignedStudentIds.value.has(student.id)));

function assignedTeamIndex(studentId) {
  return teamsDraft.value.findIndex((team) => team.student_ids.includes(studentId));
}

function assignedTeamName(studentId) {
  const index = assignedTeamIndex(studentId);

  return index === -1 ? null : teamsDraft.value[index]?.team_name || `Team ${index + 1}`;
}

function removeStudent(teamIndex, studentId) {
  const currentTeam = teamsDraft.value[teamIndex];

  if (!currentTeam) {
    return;
  }

  currentTeam.student_ids = currentTeam.student_ids.filter((id) => id !== studentId);
}

function startDrag(studentId, event) {
  if (assignedTeamIndex(studentId) !== -1) {
    event.preventDefault();
    return;
  }

  draggedStudentId.value = studentId;
  event.dataTransfer.effectAllowed = "move";
  event.dataTransfer.setData("text/plain", String(studentId));
}

function dropStudent(teamIndex, event) {
  event.preventDefault();

  const rawStudentId = event.dataTransfer.getData("text/plain") || draggedStudentId.value;
  const studentId = Number(rawStudentId);

  if (!studentId) {
    return;
  }

  const assignedIndex = assignedTeamIndex(studentId);

  if (assignedIndex !== -1 && assignedIndex !== teamIndex) {
    toast.error("Remove the student from the current team first before moving them.");
    return;
  }

  const targetTeam = teamsDraft.value[teamIndex];

  if (!targetTeam) {
    return;
  }

  if (!targetTeam.student_ids.includes(studentId)) {
    targetTeam.student_ids = [...targetTeam.student_ids, studentId];
  }

  draggedStudentId.value = null;
}

function generateTeams() {
  const count = Math.max(1, Math.min(Number(teamCount.value) || 1, maxTeams.value));
  teamCount.value = count;
  teamsDraft.value = createEmptyTeams(count);
  errors.value = {};
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

function memberChipClass(gender) {
  if (gender === "female") {
    return "border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300";
  }

  return "border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300";
}

function studentStatusClass(studentId) {
  return assignedTeamIndex(studentId) === -1
    ? "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
    : "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300";
}

function studentRosterClass(studentId) {
  return assignedTeamIndex(studentId) === -1
    ? "border-slate-200 bg-slate-50 dark:border-gray-800 dark:bg-gray-950 cursor-grab hover:border-cyan-300 hover:bg-cyan-50/50 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/5"
    : "border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/20 dark:bg-emerald-500/10 opacity-95";
}
</script>

<template>
  <Head :title="`${classData.title} Team Management`" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <p class="mt-4 text-xs font-black uppercase tracking-[0.35em] text-cyan-500 dark:text-cyan-400">Team Management</p>
          <h1 class="mt-2 text-3xl font-black text-blue-950 dark:text-gray-100">{{ classData.title }}</h1>
          <p class="mt-2 max-w-2xl text-sm font-semibold text-slate-500 dark:text-gray-400">
            Create teams, choose project topics, and manually assign students. Each student can only belong to one team in this group.
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            <RefreshCw class="h-4 w-4" />
            Back to Attendance
          </Link>
          <button
            type="button"
            :disabled="saving || !teamsDraft.length"
            @click="saveTeams"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
          >
            <Save class="h-4 w-4" />
            {{ saving ? "Saving..." : "Save Teams" }}
          </button>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-5">
          <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
              <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Number of Teams</p>
              <div class="mt-3 flex items-end gap-3">
                <label class="block flex-1">
                  <span class="mb-2 block text-xs font-semibold text-slate-700 dark:text-gray-300">Enter team count</span>
                  <input
                    v-model.number="teamCount"
                    type="number"
                    min="1"
                    :max="maxTeams"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
                  />
                </label>
                <button
                  type="button"
                  :disabled="saving || !props.students.length"
                  @click="generateTeams"
                  class="inline-flex h-12 items-center gap-2 rounded-xl bg-cyan-500 px-5 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-cyan-600 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-70"
                >
                  <Users class="h-4 w-4" />
                  Generate Teams
                </button>
              </div>
              <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-gray-400">
                You can generate up to {{ maxTeams }} teams based on the current class roster.
              </p>
              <p v-if="generalError()" class="mt-2 text-xs font-semibold text-red-600">{{ generalError() }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
              <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Assignment Summary</p>
              <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 p-4 dark:bg-gray-950">
                  <p class="text-xs font-bold text-slate-500 dark:text-gray-400">Assigned Students</p>
                  <p class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100">{{ assignedCount }} / {{ students.length }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 dark:bg-gray-950">
                  <p class="text-xs font-bold text-slate-500 dark:text-gray-400">Unassigned Students</p>
                  <p class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100">{{ students.length - assignedCount }}</p>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
              <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Quick Status</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                  {{ teamsDraft.length || 0 }} Teams Ready
                </span>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                  {{ students.length }} Students
                </span>
              </div>
            </div>
          </div>

          <div v-if="!teamsDraft.length" class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
            <Users class="mx-auto h-10 w-10 text-slate-400" />
            <p class="mt-4 text-lg font-black text-slate-900 dark:text-gray-100">No teams generated yet</p>
            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400">Enter the number of teams, then click Generate Teams to start building the team structure.</p>
          </div>

          <div v-else class="space-y-5">
            <article
              v-for="(team, teamIndex) in teamsDraft"
              :key="teamIndex"
              class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition dark:border-gray-800 dark:bg-gray-900"
              @dragover.prevent
              @drop="dropStudent(teamIndex, $event)"
            >
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="grid flex-1 gap-4 md:grid-cols-2">
                  <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Team Name</span>
                    <input
                      v-model="team.team_name"
                      type="text"
                      class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
                    />
                    <p v-if="teamError(teamIndex, 'team_name')" class="mt-1 text-xs font-semibold text-red-600">{{ teamError(teamIndex, 'team_name') }}</p>
                  </label>

                  <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Project Topic <span class="text-xs font-medium text-slate-400">(optional)</span></span>
                    <input
                      v-model="team.project_topic"
                      type="text"
                      placeholder="Enter project topic or leave blank"
                      class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-500/20"
                    />
                    <p v-if="teamError(teamIndex, 'project_topic')" class="mt-1 text-xs font-semibold text-red-600">{{ teamError(teamIndex, 'project_topic') }}</p>
                  </label>
                </div>

                <div class="rounded-xl bg-cyan-50 px-4 py-3 text-sm font-bold text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-300">
                  {{ team.student_ids.length }} members selected
                </div>
              </div>

              <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-center justify-between gap-4">
                  <p class="text-sm font-bold text-slate-900 dark:text-gray-100">Selected Members</p>
                  <p class="text-xs font-semibold text-slate-500 dark:text-gray-400">Drop students here from the roster panel.</p>
                </div>

                <div
                  class="mt-3 min-h-20 rounded-2xl border-2 border-dashed border-cyan-200 bg-white/70 p-4 transition dark:border-cyan-500/30 dark:bg-gray-900/70"
                >
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="studentId in team.student_ids"
                      :key="studentId"
                      type="button"
                      class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:opacity-90"
                      :class="memberChipClass(props.students.find((student) => student.id === studentId)?.gender)"
                      @click="removeStudent(teamIndex, studentId)"
                    >
                      {{ props.students.find((student) => student.id === studentId)?.name }}
                      <UserMinus class="h-3.5 w-3.5" />
                    </button>
                    <span v-if="!team.student_ids.length" class="text-sm font-medium text-slate-500 dark:text-gray-400">Drop students here.</span>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-6">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Available Students</p>
                <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">Roster</h2>
              </div>
              <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-300">
                {{ unassignedStudents.length }}
              </span>
            </div>

            <div class="mt-4 max-h-[72vh] space-y-2 overflow-y-auto pr-1">
              <div
                v-for="student in unassignedStudents"
                :key="student.id"
                :class="['rounded-xl px-3 py-3 transition', studentRosterClass(student.id)]"
                draggable="true"
                @dragstart="startDrag(student.id, $event)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900 dark:text-gray-100">{{ student.name }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-gray-400">ID #{{ student.id }}</p>
                  </div>
                  <span
                    class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em]"
                    :class="student.gender === 'female'
                      ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                      : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300'"
                  >
                    {{ student.gender || 'other' }}
                  </span>
                </div>

                <p class="mt-2 text-[11px] font-semibold text-slate-500 dark:text-gray-400">
                  Drag to assign to a team
                </p>

              </div>
            </div>

            <p v-if="!students.length" class="mt-4 text-sm font-medium text-slate-500 dark:text-gray-400">No students are enrolled in this class yet.</p>
            <p v-else-if="!unassignedStudents.length" class="mt-4 text-sm font-semibold text-emerald-600 dark:text-emerald-300">All students have been assigned to teams.</p>
          </div>
        </aside>
      </div>
    </section>
  </DashboardLayout>
</template>
