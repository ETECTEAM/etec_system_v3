export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) {
    return {
      label: "Pre Attendance",
      labelKey: "Pre Attendance",
      key: "pre_attendance",
      match: ["/dashboard/pre-attendance-requests", "/dashboard/pre-attendance-classes", "/dashboard/pre-attendance-counts"],
      icon: "pre_attendance",
      children: [
        {
          label: "Pre-Att Request",
          labelKey: "Pre-Att Request",
          href: "/dashboard/pre-attendance-requests",
          match: ["/dashboard/pre-attendance-requests"],
          exact: true,
          isActive: (path) => path === "/dashboard/pre-attendance-requests",
        },
        {
          label: "Pre-Att Class",
          labelKey: "Pre-Att Class",
          href: "/dashboard/pre-attendance-classes",
          match: ["/dashboard/pre-attendance-classes"],
          exact: false,
          isActive: (path) => path.startsWith("/dashboard/pre-attendance-classes"),
        },
        {
          label: "Pre-Att Count",
          labelKey: "Pre-Att Count",
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
    labelKey: "Pre Attendance",
    href: "/dashboard/instructor/pre-attendance",
    match: ["/dashboard/instructor/pre-attendance"],
    exact: false,
    icon: "pre_attendance",
    isActive: (path) => path.startsWith("/dashboard/instructor/pre-attendance"),
  };
}
