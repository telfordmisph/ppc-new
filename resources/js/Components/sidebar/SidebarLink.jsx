import React from "react";
import { Link, router, usePage } from "@inertiajs/react";
import clsx from "clsx";
import { useThemeStore } from "@/Store/themeStore";
import { DARK_THEME_NAME } from "@/Constants/colors";

const SidebarLink = ({
    href,
    label,
    icon,
    notifications = 0,
    // activeColor = "text-blue-600",
}) => {
    const { appName } = usePage().props;

    const { theme } = useThemeStore();
    const currentPath = window.location.pathname.replace(`/${appName}`, "");

    const pathTo = new URL(href, window.location.origin).pathname.replace(
        `/${appName}`,
        ""
    );
    const firstSegmentFrom = currentPath.split("/")[1];
    const firstSegmentTo = pathTo.split("/")[1];
    const isActive = firstSegmentFrom === firstSegmentTo;

    // console.log("🚀 ~ SidebarLink ~ firstSegmentFrom:", firstSegmentFrom);
    // console.log("🚀 ~ SidebarLink ~ firstSegmentTo:", firstSegmentTo);

    // console.log("🚀 ~ SidebarLink ~ isActive:", isActive);

    const isDark = theme === DARK_THEME_NAME;

    const hoverColor = isDark ? "hover:bg-base-200" : "hover:bg-base-300";

    return (
        <Link
            href={href}
            className={clsx(
                `relative flex h-8 justify-between px-4 py-1 pl-2.5 transition-colors duration-150 rounded-lg`,
                hoverColor,
                isActive
                    ? isDark
                        ? "bg-base-200 text-primary"
                        : "bg-base-300 text-primary"
                    : ""
            )}
        >
            <div className="flex items-center gap-2">
                <span className="w-4 h-4">{icon}</span>
                <p className="pl-1 pt-px">{label}</p>
            </div>

            <div>
                {notifications > 0 && (
                    <span className="inline-flex items-center justify-center px-2 py-1 ml-2 text-xs leading-none rounded-lg text-content bg-accent">
                        {notifications}
                    </span>
                )}
            </div>
        </Link>
    );
};

export default SidebarLink;
