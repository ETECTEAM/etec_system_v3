export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Enrollment",
    labelKey: "navigation.enrollment",
    key: "enroll",
    icon: "notebook-pen",
    match: ["/dashboard/enroll"],
    children: [
      {
        label: "Enrollment List",
        labelKey: "navigation.enrollmentList",
        href: "/dashboard/enroll",
        match: ["/dashboard/enroll"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/enroll") && !path.startsWith("/dashboard/enroll/students/create") && !path.startsWith("/dashboard/enroll/config"),
      },
      {
        label: "Enroll Config",
        labelKey: "navigation.enrollConfig",
        href: "/dashboard/enroll/config",
        match: ["/dashboard/enroll/config"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/enroll/config"),
      },
    ],
  };
}
