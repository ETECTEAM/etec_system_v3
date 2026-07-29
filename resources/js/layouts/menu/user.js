export const key = "user";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/users");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "User Management",
    labelKey: "navigation.userManagement",
    key,
    match: ["/dashboard/users"],
    icon: "user",
    children: [
      {
        label: "User",
        labelKey: "navigation.user",
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
        labelKey: "navigation.rolePermission",
        href: "/dashboard/users/roles",
        match: ["/dashboard/users/roles"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/users/roles"),
      },
      {
        label: "User & Permission",
        labelKey: "navigation.userPermission",
        href: "/dashboard/users/permissions",
        match: ["/dashboard/users/permissions"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/users/permissions"),
      },
    ],
  };
}
