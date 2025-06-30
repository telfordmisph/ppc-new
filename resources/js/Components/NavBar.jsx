import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function NavBar() {
    const { appName, emp_data } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);

    const formattedAppName = appName
        ?.split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");

    return (
        <nav className="">
            <div className="px-4 mx-auto sm:px-6 lg:px-8">
                <div className="flex justify-end h-[50px]">
                    <div className="items-center hidden mr-5 space-x-1 font-semibold md:flex">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            className="size-6"
                        >
                            <path
                                fillRule="evenodd"
                                d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                clipRule="evenodd"
                            />
                        </svg>
                        <span className="mt-[3px]">
                            Hello, {emp_data?.emp_firstname}
                        </span>
                    </div>
                </div>
            </div>
        </nav>
    );
}
