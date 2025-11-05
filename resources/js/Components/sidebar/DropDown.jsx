import { useState, useEffect, useMemo } from "react";
import { usePage, Link } from "@inertiajs/react";
import { Transition } from "@headlessui/react";
import clsx from "clsx";
import { useThemeStore } from "@/Store/themeStore";
import { DARK_THEME_NAME } from "@/Constants/colors";

export default function Dropdown({
    label,
    icon = null,
    links = [],
    notification = null,
}) {
    const { url } = usePage();
    const { theme } = useThemeStore();
    const normalizePath = (href) => {
        try {
            const urlObj = new URL(href, window.location.origin);
            return urlObj.pathname;
        } catch {
            return href;
        }
    };

    const isActiveLink = (href) => {
        return url === new URL(href, window.location.origin).pathname;
    };

    const hasActiveLink = useMemo(() => {
        return links.some((link) => isActiveLink(link.href));
    }, [url, links]);

    const [open, setOpen] = useState(false);

    useEffect(() => {
        setOpen(hasActiveLink);
    }, [hasActiveLink]);

    const hoverColor =
        theme === DARK_THEME_NAME ? "hover:bg-base-200" : "hover:bg-base-300";

    const activeColor =
        theme === DARK_THEME_NAME ? "bg-base-200" : "bg-base-300 text-primary";

    return (
        <div className="relative w-full">
            <button
                onClick={() => setOpen(!open)}
                className={`flex items-center h-8 justify-between w-full px-2.5 py-2 rounded-lg ${hoverColor}`}
            >
                <div className="relative flex items-center space-x-2">
                    {icon && <span className="w-6 h-6 pt-0.5">{icon}</span>}
                    <span className="pl-0 pr-1">{label}</span>

                    {/* Dropdown notification */}
                    {notification ? (
                        typeof notification === "number" ? (
                            <span className="ml-1 bg-accent text-content text-xs font-bold px-1.5 py-0.5 rounded-full">
                                {notification > 99 ? "99+" : notification}
                            </span>
                        ) : (
                            <span className="w-2 h-2 rounded-full bg-primary"></span>
                        )
                    ) : null}
                </div>

                <span
                    className={`pt-[3px] transition-transform duration-300 ${
                        open ? "rotate-180" : "rotate-0"
                    }`}
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        strokeWidth="1.5"
                        stroke="currentColor"
                        className="w-4 h-4"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M19.5 8.25L12 15.75 4.5 8.25"
                        />
                    </svg>
                </span>
            </button>

            <Transition
                show={open}
                enter="transition ease-out duration-200"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="transition ease-in duration-150"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
            >
                <div className="mt-1 ml-5">
                    {links.map((link, index) => {
                        const active = isActiveLink(link.href);
                        const linkNotification = link.notification;

                        return (
                            <Link
                                key={`${normalizePath(link.href)}-${index}`}
                                href={link.href}
                                className={clsx(
                                    "flex items-center h-8 justify-between pr-2 text-sm rounded-r-lg transition-colors",
                                    !active && hoverColor,
                                    active && activeColor,
                                    active && "text-primary"
                                )}
                            >
                                <div className="flex items-center space-x-2">
                                    <div
                                        className={clsx(
                                            "w-0.5 h-8",
                                            active
                                                ? "bg-primary"
                                                : "bg-base-300"
                                        )}
                                    ></div>
                                    {link.icon ? (
                                        <span className="w-4 h-4">
                                            {link.icon}
                                        </span>
                                    ) : (
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth="1.5"
                                            stroke="currentColor"
                                            className="size-4"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z"
                                            />
                                        </svg>
                                    )}
                                    <p className="pl-1">{link.label}</p>
                                </div>

                                {linkNotification ? (
                                    typeof linkNotification === "number" ? (
                                        <span className="bg-accent text-content text-xs px-1.5 py-0.5 rounded-lg">
                                            {linkNotification > 99
                                                ? "99+"
                                                : linkNotification}
                                        </span>
                                    ) : (
                                        <span className="w-2 h-2 mr-[7px] bg-accent rounded-full"></span>
                                    )
                                ) : null}
                            </Link>
                        );
                    })}
                </div>
            </Transition>
        </div>
    );
}
