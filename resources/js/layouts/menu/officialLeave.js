export const key = "officialLeave";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/official-leaves");
}

export function build(ctx) {
  // The office desk and history belong to admin + super_admin; the super-admin-only
  // children (reports, settings, activity log) are appended conditionally.
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  const children = [
    {
      label: "Request Desk",
      labelKey: "navigation.officialLeaveDesk",
      href: "/dashboard/official-leaves",
      match: ["/dashboard/official-leaves"],
      exact: true,
      isActive: (path) => path === "/dashboard/official-leaves",
    },
    {
      label: "Leave History",
      labelKey: "navigation.officialLeaveHistory",
      href: "/dashboard/official-leaves/history",
      match: ["/dashboard/official-leaves/history"],
      isActive: (path) => path.startsWith("/dashboard/official-leaves/history"),
    },
  ];

  if (ctx.isSuperAdmin) {
    children.push(
      {
        label: "Reports & Stats",
        labelKey: "navigation.officialLeaveReports",
        href: "/dashboard/official-leaves/reports",
        match: ["/dashboard/official-leaves/reports"],
        isActive: (path) => path.startsWith("/dashboard/official-leaves/reports"),
      },
      {
        label: "Leave Settings",
        labelKey: "navigation.officialLeaveSettings",
        href: "/dashboard/official-leaves/settings",
        match: ["/dashboard/official-leaves/settings"],
        isActive: (path) => path.startsWith("/dashboard/official-leaves/settings"),
      },
      {
        label: "Activity Log",
        labelKey: "navigation.officialLeaveActivityLog",
        href: "/dashboard/official-leaves/activity-log",
        match: ["/dashboard/official-leaves/activity-log"],
        isActive: (path) => path.startsWith("/dashboard/official-leaves/activity-log"),
      },
    );
  }

  return {
    label: "Official Leave",
    labelKey: "navigation.officialLeave",
    key,
    match: ["/dashboard/official-leaves"],
    icon: "official_leave",
    children,
  };
}
