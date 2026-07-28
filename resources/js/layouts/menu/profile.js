export function build(ctx) {
  if (ctx.isSuperAdmin || ctx.isAdmin) return null;

  return {
    label: "My Profile",
    labelKey: "navigation.myProfile",
    href: "/dashboard/instructor",
    match: ["/dashboard/instructor"],
    exact: false,
    icon: "profile",
    isActive: (path) => path.startsWith("/dashboard/instructor"),
  };
}
