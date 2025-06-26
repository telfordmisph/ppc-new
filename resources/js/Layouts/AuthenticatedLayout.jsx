import NavBar from "@/Components/NavBar";
import Sidebar from "@/Components/SideBar";
import { Link, usePage, router } from "@inertiajs/react";
import axios from "axios";
import { useEffect } from "react";

export default function AuthenticatedLayout({ header, children }) {
    const { url } = usePage();

    useEffect(() => {
        authCheck();
        console.log("Current URL:", window.location.href);
    }, [url]);

    const authCheck = async () => {
        const queryParams = new URLSearchParams(url.split("?")[1]);
        const queryToken = queryParams.get("token");

        if (queryToken) {
            localStorage.setItem("authify-token", queryToken);

            // Remove query params from the URL without reloading the page
            const cleanUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            router.post(route("setSession"), queryToken);
        }

        const token = localStorage.getItem("authify-token");

        if (!token) {
            window.location.href = `http://127.0.0.1:8001/authify/login?redirect=${encodeURIComponent(
                window.location.href
            )}`;
            return;
        }

        console.log("with token", token);

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

            console.log("isTokenValid", isTokenValid.data);
        } catch (error) {
            console.log("with error", error);
        }
    };

    return (
        <div className="flex flex-col">
            <div className="flex h-screen overflow-hidden">
                {/* <Sidebar /> */}

                <div className="">
                    {/* <NavBar /> */}
                    <main className="w-full h-screen px-6 py-6 pb-[70px] overflow-y-auto">
                        <div className="">{children}</div>
                    </main>
                </div>
            </div>
        </div>
    );
}
