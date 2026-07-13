export function build(ctx) {
  if (!ctx.isSuperAdmin || !ctx.canAccessNotifications) return null;

  return {
    label: "EnRoll Management",
    key: "enroll",
    icon: "student",
    href: "/dashboard/students",
    match: ["/dashboard/students"],
    exact: false,
    isActive: (path) => path.startsWith("/dashboard/students"),
  };
}
