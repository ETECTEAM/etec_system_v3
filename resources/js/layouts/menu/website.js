export const key = "website";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/website");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Website Management",
    key,
    match: ["/dashboard/website"],
    icon: "website",
    children: [
      {
        label: "School Settings",
        href: "/dashboard/website/school-settings",
        match: ["/dashboard/website/school-settings"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/school-settings"),
      },
      {
        label: "Menu Management",
        href: "/dashboard/website/menus",
        match: ["/dashboard/website/menus"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/menus"),
      },
      {
        label: "Page Management",
        href: "/dashboard/website/pages",
        match: ["/dashboard/website/pages"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/pages"),
      },
      {
        label: "Video Management",
        href: "/dashboard/website/videos",
        match: ["/dashboard/website/videos"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/videos"),
      },
      {
        label: "News Management",
        href: "/dashboard/website/news",
        match: ["/dashboard/website/news"],
        exact: false,
        isActive: (path) => path.startsWith("/dashboard/website/news"),
      },
    ],
  };
}
