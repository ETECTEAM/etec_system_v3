<script setup>
import { computed, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close"]);

const page = usePage();

const currentPath = computed(() => page.url ?? "/");
const roles = computed(() => page.props.auth?.roles ?? []);

const isSuperAdmin = computed(() => roles.value.includes("super_admin"));

const canAccessNotifications = computed(
  () => roles.value.includes("super_admin") || roles.value.includes("admin")
);

function isStudentRoute(path) {
  return path.startsWith("/dashboard/students") || path.startsWith("/qr");
}

function isClassManagementRoute(path) {
  const p = path.split("?")[0];

  return (
    p.startsWith("/class-types") ||
    p.startsWith("/dashboard/class-types") ||
    p.startsWith("/dashboard/class-list")
  );
}

function isUserManagementRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/users");
}

function isBuildingRoute(path) {
  const p = path.split("?")[0];

  return (
    p.startsWith("/dashboard/buildings") ||
    p.startsWith("/dashboard/floors") ||
    p.startsWith("/dashboard/rooms")
  );
}

function isScheduleRoute(path) {
  const p = path.split("?")[0];

  return (
    p.startsWith("/dashboard/terms") ||
    p.startsWith("/dashboard/times") ||
    p.startsWith("/dashboard/schdule")
  );
}

function isCourseRoute(path) {
  const p = path.split("?")[0];

  return (
    p.startsWith("/dashboard/course") ||
    p.startsWith("/dashboard/categories") ||
    p.startsWith("/dashboard/subcategories") ||
    p.startsWith("/dashboard/tracks") ||
    p.startsWith("/dashboard/courses") ||
    p.startsWith("/dashboard/lessons")
  );
}

const openMenus = ref({
  building_management: isBuildingRoute(currentPath.value),
  user: isUserManagementRoute(currentPath.value),
  classes: isClassManagementRoute(currentPath.value),
  schdule: isScheduleRoute(currentPath.value),
  student: isStudentRoute(currentPath.value),
  course: isCourseRoute(currentPath.value),
});

const menuItems = computed(() => {
  const base = [
    {
      label: "Dashboard",
      href: "/dashboard",
      match: ["/dashboard"],
      exact: true,
      icon: "home",
    },
  ];

  if (canAccessNotifications.value) {
    base.push({
      label: "Notifications",
      href: "/dashboard/notifications",
      match: ["/dashboard/notifications"],
      exact: false,
      icon: "notification",
      isActive: (path) => path.startsWith("/dashboard/notifications"),
    });
  }

  base.push({
    label: "Class Management",
    key: "classes",
    match: ["/class-types", "/dashboard/class-types", "/dashboard/class-list"],
    icon: "classes",
    children: [
      {
        label: "Class Type",
        href: "/dashboard/class-types",
        match: ["/dashboard/class-types", "/class-types"],
        exact: false,
        isActive: (path) =>
          path.startsWith("/dashboard/class-types") ||
          path.startsWith("/class-types"),
      },
      {
        label: "Class List",
        href: "/dashboard/class-list",
        match: ["/dashboard/class-list"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/class-list"),
      },
    ],
  });

  base.push({
    label: "Course Management",
    key: "course",
    match: ["/dashboard/course"],
    icon: "course",
    children: [
      {
        label: "Categories",
        href: "/dashboard/course/categories",
        match: ["/dashboard/course/categories"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/categories"),
      },
      {
        label: "Sub Categories",
        href: "/dashboard/course/subcategories",
        match: ["/dashboard/course/subcategories"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/subcategories"),
      },
      {
        label: "Tracks",
        href: "/dashboard/course/tracks",
        match: ["/dashboard/course/tracks"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/tracks"),
      },
      {
        label: "Courses",
        href: "/dashboard/course/courses",
        match: ["/dashboard/course/courses"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/courses"),
      },
      {
        label: "Lessons",
        href: "/dashboard/course/lessons",
        match: ["/dashboard/course/lessons"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/course/lessons"),
      },
    ],
  });

  if (isSuperAdmin.value) {
    base.push(
      {
        label: "User Management",
        key: "user",
        match: ["/dashboard/users"],
        icon: "user",
        children: [
          {
            label: "User",
            href: "/dashboard/users",
            match: ["/dashboard/users"],
            exact: false,
            isActive: (path) =>
              path === "/dashboard/users" ||
              path.startsWith("/dashboard/users/create") ||
              path.startsWith("/dashboard/users/edit") ||
              /^\/dashboard\/users\/\d+$/.test(path),
          },
          {
            label: "Role & Permission",
            href: "/dashboard/users/roles",
            match: ["/dashboard/users/roles"],
            exact: false,
            isActive: (path) => path.startsWith("/dashboard/users/roles"),
          },
          {
            label: "Permission",
            href: "/dashboard/users/permissions",
            match: ["/dashboard/users/permissions"],
            exact: false,
            isActive: (path) => path.startsWith("/dashboard/users/permissions"),
          },
        ],
      },
      {
        label: "Schedule Management",
        key: "schdule",
        match: ["/dashboard/terms", "/dashboard/times", "/dashboard/schdule"],
        icon: "schdule",
        children: [
          {
            label: "Terms",
            href: "/dashboard/terms",
            match: ["/dashboard/terms"],
            exact: false,
            isActive: (path) =>
              path === "/dashboard/terms" ||
              path.startsWith("/dashboard/terms/create") ||
              path.startsWith("/dashboard/terms/edit"),
          },
          {
            label: "Times",
            href: "/dashboard/times",
            match: ["/dashboard/times"],
            exact: false,
            isActive: (path) =>
              path === "/dashboard/times" ||
              path.startsWith("/dashboard/times/create") ||
              path.startsWith("/dashboard/times/edit") ||
              /^\/dashboard\/times\/\d+$/.test(path),
          },
          {
            label: "Schedules",
            href: "/dashboard/schdule",
            match: ["/dashboard/schdule"],
            exact: false,
            isActive: (path) =>
              path === "/dashboard/schdule" ||
              path.startsWith("/dashboard/schdule/create") ||
              path.startsWith("/dashboard/schdule/edit"),
          },
        ],
      }
    );
  }

  const singleItems = base.filter((item) => !item.children);
  const dropdownItems = base.filter((item) => item.children);

  return [...singleItems, ...dropdownItems];
});

function isActive(item) {
  const pathOnly = currentPath.value.split("?")[0].replace(/\/+$/, "") || "/";

  if (typeof item.isActive === "function") {
    return item.isActive(pathOnly);
  }

  return item.match.some((targetPath) => {
    const normalizedTarget = targetPath.replace(/\/+$/, "") || "/";

    if (item.exact) {
      return pathOnly === normalizedTarget;
    }

    return (
      pathOnly === normalizedTarget ||
      pathOnly.startsWith(`${normalizedTarget}/`)
    );
  });
}

function isChildActive(children = []) {
  return children.some((child) => {
    if (isActive(child)) {
      return true;
    }

    if (child.children) {
      return isChildActive(child.children);
    }

    return false;
  });
}

function toggleMenu(key) {
  if (props.collapsed) {
    return;
  }

  openMenus.value[key] = !openMenus.value[key];
}

watch(currentPath, (path) => {
  if (isUserManagementRoute(path)) {
    openMenus.value.user = true;
  }

  if (isBuildingRoute(path)) {
    openMenus.value.building_management = true;
  }

  if (isClassManagementRoute(path)) {
    openMenus.value.classes = true;
  }

  if (isScheduleRoute(path)) {
    openMenus.value.schdule = true;
  }

  if (isStudentRoute(path)) {
    openMenus.value.student = true;
  }

  if (isCourseRoute(path)) {
    openMenus.value.course = true;
  }
});
</script>

<template>
  <div
    :class="[
      'fixed inset-0 z-40 lg:static lg:inset-auto lg:z-auto',
      props.open ? 'block' : 'hidden lg:block',
    ]"
  >
    <div
      class="absolute inset-0 bg-slate-900/30 lg:hidden"
      @click="emit('close')"
    />

    <aside
      :class="[
        'relative h-screen border-r border-slate-200 bg-white transition-all duration-200 lg:sticky lg:top-0',
        props.collapsed ? 'w-20' : 'w-80',
      ]"
    >
      <div
        :class="[
          'flex h-full flex-col py-6',
          props.collapsed ? 'px-3' : 'px-4',
        ]"
      >
        <div
          :class="[
            'flex items-start justify-between',
            props.collapsed ? 'justify-center' : '',
          ]"
        >
          <div v-if="!props.collapsed">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">
              ETEC
            </p>
            <p class="text-base font-semibold text-slate-900">Control Center</p>
          </div>

          <button
            type="button"
            class="rounded-lg border border-slate-200 p-1 text-slate-500 transition hover:bg-slate-50 lg:hidden"
            @click="emit('close')"
          >
            <svg
              class="h-4 w-4"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>

        <nav class="mt-6 flex-1 overflow-y-auto">
          <p
            v-if="!props.collapsed"
            class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400"
          >
            Navigation
          </p>

          <ul class="space-y-1.5">
            <li v-for="item in menuItems" :key="item.href ?? item.key">
              <template v-if="item.children">
                <button
                  type="button"
                  :title="props.collapsed ? item.label : ''"
                  :class="[
                    'flex w-full items-center rounded-xl text-sm font-semibold transition',
                    props.collapsed
                      ? 'justify-center px-2 py-3'
                      : 'justify-between px-3 py-2',
                    isChildActive(item.children)
                      ? 'bg-blue-50 text-blue-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                  ]"
                  @click="toggleMenu(item.key)"
                >
                  <span class="flex flex-1 items-center gap-2 text-left">
                    <svg
                      v-if="item.icon === 'course'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                      <path d="M8 7h8" />
                      <path d="M8 11h6" />
                      <path d="M8 15h4" />
                    </svg>

                    <svg
                      v-else-if="item.icon === 'classes'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                      <path d="M6 6h10M6 10h10" />
                    </svg>

                    <svg
                      v-else-if="item.icon === 'user'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M16 19a4 4 0 0 0-8 0" />
                      <circle cx="12" cy="7" r="3" />
                      <path d="M20 8v6" />
                      <path d="M23 11h-6" />
                    </svg>

                    <svg
                      v-else-if="item.icon === 'building_management'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                      <line x1="9" y1="22" x2="9" y2="16" />
                      <line x1="15" y1="22" x2="15" y2="16" />
                    </svg>

                    <svg
                      v-else-if="item.icon === 'schdule'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                      <line x1="16" y1="2" x2="16" y2="6" />
                      <line x1="8" y1="2" x2="8" y2="6" />
                      <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>

                    <svg
                      v-else-if="item.icon === 'student'"
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>

                    <svg
                      v-else
                      class="h-4 w-4 text-slate-400"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                      <line x1="9" y1="3" x2="9" y2="21" />
                    </svg>

                    <span v-if="!props.collapsed">{{ item.label }}</span>
                  </span>

                  <svg
                    v-if="!props.collapsed"
                    class="h-4 w-4 text-slate-400 transition"
                    :class="openMenus[item.key] ? 'rotate-180' : ''"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </button>

                <ul
                  v-if="openMenus[item.key] && !props.collapsed"
                  class="ml-3 mt-2 space-y-1 border-l border-slate-200 pl-3"
                >
                  <li v-for="child in item.children" :key="child.href ?? child.key">
                    <Link
                      :href="child.href"
                      class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                      :class="
                        isActive(child)
                          ? 'bg-blue-50 text-blue-700'
                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                      "
                      @click="emit('close')"
                    >
                      <span>{{ child.label }}</span>
                    </Link>
                  </li>
                </ul>
              </template>

              <Link
                v-else
                :href="item.href"
                :title="props.collapsed ? item.label : ''"
                :class="[
                  'flex items-center rounded-xl text-sm font-semibold transition',
                  props.collapsed
                    ? 'justify-center px-2 py-3'
                    : 'justify-between px-3 py-2',
                  isActive(item)
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                ]"
                @click="emit('close')"
              >
                <span class="flex items-center gap-2">
                  <svg
                    v-if="item.icon === 'notification'"
                    class="h-4 w-4 text-slate-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                  </svg>

                  <svg
                    v-else-if="item.icon === 'home'"
                    class="h-4 w-4 text-slate-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                  </svg>

                  <svg
                    v-else
                    class="h-4 w-4 text-slate-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                  </svg>

                  <span v-if="!props.collapsed">{{ item.label }}</span>
                </span>
              </Link>
            </li>
          </ul>
        </nav>

        <div class="mt-4 border-t border-slate-200 pt-4">
          <Link
            href="/logout"
            method="post"
            as="button"
            :title="props.collapsed ? 'Logout' : ''"
            :class="[
              'w-full rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 transition hover:bg-slate-50',
              props.collapsed ? 'px-2 py-3' : 'px-3 py-2',
            ]"
          >
            <span v-if="props.collapsed">↩</span>
            <span v-else>Logout</span>
          </Link>
        </div>
      </div>
    </aside>
  </div>
</template>