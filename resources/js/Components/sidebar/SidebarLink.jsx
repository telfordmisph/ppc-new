import React from "react";
import { Link, usePage } from "@inertiajs/react";
import clsx from "clsx";
import { useThemeStore } from "@/Store/themeStore";

const SidebarLink = ({
    href,
    label,
    icon,
    notifications = 0,
    // activeColor = "text-blue-600",
}) => {
    const { url } = usePage();

    const { theme } = useThemeStore();

    const isActive = url === new URL(href, window.location.origin).pathname;

    const isDark = theme === "dark";

    const hoverColor = isDark ? "hover:bg-base-200" : "hover:bg-base-300";

    return (
        <Link
            href={href}
            className={clsx(
                `relative flex h-8 justify-between px-4 py-1 pl-[10px] transition-colors duration-150 rounded-md`,
                hoverColor,
                isActive
                    ? isDark
                        ? "bg-gray-700 text-white"
                        : "bg-neutral text-white"
                    : "text-base"
            )}
        >
            <div className="flex items-center">
                <span className="w-6 h-6 pt-[2px]">{icon}</span>
                <p className="pl-1 pt-[1px]">{label}</p>
            </div>

            <div>
                {notifications > 0 && (
                    <span className="inline-flex items-center justify-center px-2 py-1 ml-2 text-xs leading-none rounded-md text-content bg-accent">
                        {notifications}
                    </span>
                )}
            </div>
        </Link>
    );
};

export default SidebarLink;
