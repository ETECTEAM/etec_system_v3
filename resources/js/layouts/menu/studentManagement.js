export const key = "studentManagement";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/student-management");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "Student Management",
    key,
    match: ["/dashboard/student-management"],
    icon: "student_management",
    children: [
      {
        label: "Students",
        href: "/dashboard/student-management/students",
        match: ["/dashboard/student-management/students"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/student-management/students"),
      },
      {
        label: "Student Locks",
        href: "/dashboard/student-management/locks",
        match: ["/dashboard/student-management/locks"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/student-management/locks"),
      },
      {
        label: "Student Hard Locks",
        href: "/dashboard/student-management/hard-locks",
        match: ["/dashboard/student-management/hard-locks"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/student-management/hard-locks"),
      },
    ],
  };
}
