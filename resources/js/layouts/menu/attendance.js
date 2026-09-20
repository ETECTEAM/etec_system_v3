export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) {
    const children = [
      {
        label: "Pre-Attendance Classes",
        labelKey: "navigation.preAttendanceClass",
        href: "/dashboard/pre-attendance-classes",
        match: ["/dashboard/pre-attendance-classes"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/pre-attendance-classes"),
      },
      {
        label: "Pre-Attendance Counts",
        labelKey: "navigation.preAttendanceCount",
        href: "/dashboard/pre-attendance-counts",
        match: ["/dashboard/pre-attendance-counts"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/pre-attendance-counts"),
      },
      {
        label: "Instructor Blocks",
        labelKey: "navigation.instructorAttendanceBlocks",
        href: "/dashboard/instructor-attendance-blocks",
        match: ["/dashboard/instructor-attendance-blocks"],
        exact: true,
        isActive: (path) => path === "/dashboard/instructor-attendance-blocks",
      },
    ];

    return {
      label: "Attendance",
      labelKey: "navigation.attendance",
      key: "pre_attendance",
      match: [
        "/dashboard/pre-attendance-classes",
        "/dashboard/pre-attendance-counts",
        "/dashboard/instructor-attendance-blocks",
      ],
      icon: "pre_attendance",
      children,
    };
  }

  // Instructors have no self-service pre-attendance page anymore: admin already
  // sees every stuck class via Pre-Att Class and can approve it directly, and a
  // full no-show blocks the instructor outright (see the Attendance Block banner
  // on their dashboard/class pages) rather than asking them to request review.
  return null;
}
