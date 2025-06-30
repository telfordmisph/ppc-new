import NavBar from "@/Components/NavBar";
import Sidebar from "@/Components/Sidebar/SideBar";
import { Link, usePage, router } from "@inertiajs/react";
import axios from "axios";
import { useEffect } from "react";

export default function AuthenticatedLayout({ header, children }) {
    const { url } = usePage();

    useEffect(() => {
        authCheck();
    }, [url]);

    const authCheck = async () => {
        // Check if the URL contains a query parameter "key" (token value): START
        const queryParams = new URLSearchParams(url.split("?")[1]);
        const queryToken = queryParams.get("key");

        if (queryToken) {
            localStorage.setItem("authify-token", queryToken);

            // Remove query params from the URL without reloading the page
            const cleanUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            router.post(route("setSession"), { queryToken });
        }
        // Check if the URL contains a query parameter "key" (token value): END

        // Check if there is a token stored in localStorage, redirect to login if not: START
        const token = localStorage.getItem("authify-token");

        if (!token) {
            window.location.href = `http://127.0.0.1:8001/authify/login?redirect=${encodeURIComponent(
                window.location.href
            )}`;
            return;
        }
        // Check if there is a token stored in localStorage, redirect to login if not: END

        // Check if the token is valid, redirect to login if not: START
        try {
            const isTokenValid = await axios.get(
                `http://127.0.0.1:8001/api/authify/validate?token=${encodeURIComponent(
                    token
                )}`
            );

            if (isTokenValid.data.status !== "success") {
                localStorage.removeItem("authify-token");

                window.location.href = `http://127.0.0.1:8001/authify/login?redirect=${encodeURIComponent(
                    window.location.href
                )}`;
                return;
            }
        } catch (error) {
            console.log("with error", error);
        }
        // Check if the token is valid, redirect to login if not: END
    };

    return (
        <div className="flex flex-col">
            <div className="flex h-screen overflow-hidden">
                <Sidebar />

                <div className="w-full ">
                    <NavBar />
                    <main className="h-screen px-6 py-6 pb-[70px] overflow-y-auto">
                        <div className="">{children}</div>
                    </main>
                </div>
            </div>
        </div>
    );
}
