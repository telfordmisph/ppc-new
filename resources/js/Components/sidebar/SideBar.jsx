import { Link, usePage, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import Navigation from "@/Components/sidebar/Navigation";
import ThemeToggler from "@/Components/sidebar/ThemeToggler";
import { useThemeStore } from "@/Store/themeStore";

export default function Sidebar() {
    const { display_name } = usePage().props;
    const [isSidebarOpen, setIsSidebarOpen] = useState(false); // for responsiveness
    const { theme, toggleTheme } = useThemeStore();

    const formattedAppName = display_name
        ?.split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");

    return (
        <div className="z-[1] flex w-0 shadow-lg md:w-64">
            {/* Mobile Hamburger */}
            <button
                className="absolute p-2 rounded top-4 right-4 md:hidden"
                onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            >
                <svg
                    className="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>

            <div
                className={`
                    w-full bg-base-100 fixed md:relative top-0 left-0 transition-transform transform
                    ${isSidebarOpen ? "translate-x-0" : "-translate-x-full"}
                    md:translate-x-0
                    md:flex
                    flex-col min-h-screen w-[270px] space-y-6 px-4 pb-6 pt-4
                `}
                style={{
                    scrollbarWidth: "none",
                    msOverflowStyle: "none",
                }}
            >
                <Link
                    href={route("dashboard")}
                    className="flex items-center pl-[10px] text-lg font-bold"
                >
                    <p className="pt-[2px] pl-1">Telford</p>
                </Link>

                <Navigation />

                <ThemeToggler toggleTheme={toggleTheme} theme={theme} />
            </div>
        </div>
    );
}
