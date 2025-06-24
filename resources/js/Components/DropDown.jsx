import { useState } from "react";

export default function Dropdown({ label, icon = null, links = [] }) {
    const [open, setOpen] = useState(false);

    const hoverColor =
        localStorage.getItem("theme") === "dark"
            ? "hover:bg-gray-800"
            : "hover:bg-gray-100";

    return (
        <div className="w-full">
            <button
                onClick={() => setOpen(!open)}
                className={`flex items-center justify-between w-full px-3 py-2 focus:outline-none ${hoverColor}`}
            >
                <div className="flex items-center space-x-1">
                    {icon && <span className="w-5 h-5">{icon}</span>}
                    <span>{label}</span>
                </div>
                <span className="pt-[3px]">
                    {open ? (
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
                                d="m4.5 15.75 7.5-7.5 7.5 7.5"
                            />
                        </svg>
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
                                d="m19.5 8.25-7.5 7.5-7.5-7.5"
                            />
                        </svg>
                    )}
                </span>
            </button>

            {open && (
                <div className="mt-1 ml-6 space-y-1">
                    {links.map((link) => (
                        <a
                            key={link.href}
                            href={route(link.href)}
                            className={`flex items-center px-2 py-1 text-sm rounded hover:bg-gray-100 dark:hover:bg-red-500 ${hoverColor}`}
                        >
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
                            <p className="pl-1">{link.label}</p>
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}
