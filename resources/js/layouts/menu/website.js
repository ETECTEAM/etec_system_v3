export const key = "website";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/website");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Website Management",
    labelKey: "navigation.websiteManagement",
    key,
    match: ["/dashboard/website"],
    icon: "website",
    children: [
      {
        label: "School Settings",
        labelKey: "navigation.schoolSettings",
        href: "/dashboard/website/school-settings",
        match: ["/dashboard/website/school-settings"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/school-settings"),
      },
      {
        label: "Menu Management",
        labelKey: "navigation.menuManagement",
        href: "/dashboard/website/menus",
        match: ["/dashboard/website/menus"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/menus"),
      },
      {
        label: "Page Management",
        labelKey: "navigation.pageManagement",
        href: "/dashboard/website/pages",
        match: ["/dashboard/website/pages"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/pages"),
      },
      {
        label: "Video Management",
        labelKey: "navigation.videoManagement",
        href: "/dashboard/website/videos",
        match: ["/dashboard/website/videos"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/videos"),
      },
      {
        label: "News Management",
        labelKey: "navigation.newsManagement",
        href: "/dashboard/website/news",
        match: ["/dashboard/website/news"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/news"),
      },
    ],
  };
}
