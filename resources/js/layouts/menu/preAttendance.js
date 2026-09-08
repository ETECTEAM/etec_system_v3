export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) {
    return {
      label: "Pre Attendance",
      labelKey: "navigation.preAttendance",
      key: "pre_attendance",
      match: ["/dashboard/pre-attendance-requests", "/dashboard/pre-attendance-classes", "/dashboard/pre-attendance-counts"],
      icon: "pre_attendance",
      children: [
        {
          label: "Pre-Att Request",
          labelKey: "navigation.preAttendanceRequest",
          href: "/dashboard/pre-attendance-requests",
          match: ["/dashboard/pre-attendance-requests"],
          exact: true,
          isActive: (path) => path === "/dashboard/pre-attendance-requests",
        },
        {
          label: "Pre-Att Class",
          labelKey: "navigation.preAttendanceClass",
          href: "/dashboard/pre-attendance-classes",
          match: ["/dashboard/pre-attendance-classes"],
          exact: false,
          isActive: (path) => path.startsWith("/dashboard/pre-attendance-classes"),
        },
        {
          label: "Pre-Att Count",
          labelKey: "navigation.preAttendanceCount",
          href: "/dashboard/pre-attendance-counts",
          match: ["/dashboard/pre-attendance-counts"],
          exact: false,
          isActive: (path) => path.startsWith("/dashboard/pre-attendance-counts"),
        },
      ],
    };
  }

  return {
    label: "Pre Attendance",
    labelKey: "navigation.preAttendance",
    href: "/dashboard/instructor/pre-attendance",
    match: ["/dashboard/instructor/pre-attendance"],
    exact: false,
    icon: "pre_attendance",
    isActive: (path) => path.startsWith("/dashboard/instructor/pre-attendance"),
  };
}
