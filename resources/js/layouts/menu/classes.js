export const key = "classes";

export function isRoute(path) {
  const p = path.split("?")[0];
  return (
    p.startsWith("/class-types") ||
    p.startsWith("/dashboard/class-types") ||
    p.startsWith("/dashboard/class-list")
  );
}

export function build(ctx) {
  // Class types and the class list are admin screens; instructors manage their own
  // classes from the dashboard instead.
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Class Management",
    labelKey: "navigation.classManagement",
    key,
    match: ["/class-types", "/dashboard/class-types", "/dashboard/class-list"],
    icon: "classes",
    children: [
      {
        label: "Class Type",
        labelKey: "navigation.classType",
        href: "/dashboard/class-types",
        match: ["/dashboard/class-types", "/class-types"],
        exact: false,
        isActive: (path) =>
          path.startsWith("/dashboard/class-types") ||
          path.startsWith("/class-types"),
      },
      {
        label: "Class List",
        labelKey: "navigation.classList",
        href: "/dashboard/class-list",
        match: ["/dashboard/class-list"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/class-list"),
      },
    ],
  };
}
