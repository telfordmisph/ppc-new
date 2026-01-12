import NavBar from "@/Components/NavBar";
import Sidebar from "@/Components/Sidebar/SideBar";
import LoadingScreen from "@/Components/LoadingScreen";
import { usePage } from "@inertiajs/react";
import axios from "axios";
import { useEffect, useState } from "react";
import Footer from "@/Components/Footer";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import { useWipStore } from "@/Store/overallDailyWipTrendStore";
import { useWorkweekStore } from "@/Store/workweekListStore";
import { useImportTraceStore } from "@/Store/importTraceStore";
import useUserStore from "@/Store/useUserStore";

export default function AuthenticatedLayout({ header, children }) {
    const { url } = usePage();
    const { fetchPackages } = useF1F2PackagesStore();
    const { fetchWip } = useWipStore();
    const { fetchWorkweek } = useWorkweekStore();
    const { fetchAllImports } = useImportTraceStore();

    const [hasUserFetched, setHasUserFetched] = useState(false);
    const { emp_data } = usePage().props;

    useEffect(() => {
        console.log("🚀 ~ user user:", emp_data);
        if (!emp_data || hasUserFetched) return;

        fetchPackages();
        fetchWip();
        fetchWorkweek();
        fetchAllImports();

        setHasUserFetched(true);
    }, [emp_data, hasUserFetched]);

    useEffect(() => {
        const queryParams = new URLSearchParams(url.split("?")[1]);
        const queryToken = queryParams.get("key");
    }, [url]);


    const authCheck = async () => {
        setIsLoading(true);

        // Check if the URL contains a query parameter "key" (token value): START
        const queryParams = new URLSearchParams(url.split("?")[1]);
        const queryToken = queryParams.get("key");

        if (queryToken) {
            try {
                console.log(
                    "🚀 ~ AuthenticatedLayout ~ queryToken:",
                    queryToken
                );

                const response = await fetch(route("setSession"), {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify({ queryToken }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                const user = data.emp_data;

                console.log("🚀 ~ authCheck ~ user:", user);

                useUserStore.getState().setUser(user);
            } catch (error) {
                console.log(error);
            } finally {
                localStorage.setItem("authify-token", queryToken);
                const cleanUrl =
                    window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
                setIsLoading(false);
            }
        } else {
            // Check if the URL contains a query parameter "key" (token value): END
            // Check if there is a token stored in localStorage, redirect to login if not: START
            const token = localStorage.getItem("authify-token");
    
            if (!token) {
                window.location.href = `http://192.168.1.27:8080/authify/public/login?redirect=${encodeURIComponent(
                    route("dashboard")
                )}`;
                return;
            }
    
            try {
                const isTokenValid = await axios.get(
                    `http://192.168.1.27:8080/authify/public/api/validate?token=${encodeURIComponent(
                        token
                    )}`
                );
    
                if (isTokenValid.data.status !== "success") {
                    localStorage.removeItem("authify-token");
    
                    window.location.href = `http://192.168.1.27:8080/authify/public/login?redirect=${encodeURIComponent(
                        route("dashboard")
                    )}`;
                    return;
                }
            } catch (error) {
                console.log("with error", error);
            }
    
            setIsLoading(false);
        }
    };

    return (
        <div className="flex bg-base-200 h-screen text-sm">
            <Sidebar />
            <div className="h-full flex flex-col flex-1 overflow-y-hidden">
                <NavBar />
                <main className="px-4 py-8 z-10 flex-1 w-full relative overflow-y-auto">
                    <div className="w-full">{children}</div>
                </main>
                <Footer />
            </div>
        </div>
    );
}
