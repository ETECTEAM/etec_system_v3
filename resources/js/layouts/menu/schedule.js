export const key = "schdule";

export function isRoute(path) {
  const p = path.split("?")[0];
  return (
    p.startsWith("/dashboard/terms") ||
    p.startsWith("/dashboard/times") ||
    p.startsWith("/dashboard/schdule")
  );
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Schedule Management",
    key,
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
  };
}
