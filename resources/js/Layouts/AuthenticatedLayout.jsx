// import NavBar from "@/Components/NavBar";
// import Sidebar from "@/Components/Sidebar/SideBar";
// import LoadingScreen from "@/Components/LoadingScreen";
// import { Link, usePage, router } from "@inertiajs/react";
// import axios from "axios";
// import { useEffect, useState } from "react";

// export default function AuthenticatedLayout({ header, children }) {
//     const { url, props } = usePage();

//     const [isLoading, setIsLoading] = useState(true);

//     useEffect(() => {
//         authCheck();
//     }, [url]);

//     const authCheck = async () => {
//         setIsLoading(true);

//         // Check if the URL contains a query parameter "key" (token value): START
//         const queryParams = new URLSearchParams(url.split("?")[1]);
//         const queryToken = queryParams.get("key");

//         if (queryToken) {
//             localStorage.setItem("authify-token", queryToken);

//             // Remove query params from the URL without reloading the page
//             const cleanUrl = window.location.origin + window.location.pathname;
//             window.history.replaceState({}, document.title, cleanUrl);
//             router.post(route("setSession"), { queryToken });

//             setIsLoading(false);
//         }
//         // Check if the URL contains a query parameter "key" (token value): END

//         // Check if there is a token stored in localStorage, redirect to login if not: START
//         const token = localStorage.getItem("authify-token");

//         if (!token) {
//             // for production
//             window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
//                 route("dashboard")
//             )}`;

//             // for dev local
//             // ("http://192.168.2.221/authify/public/login?redirect=http://localhost:8000");
//             return;
//         }
//         // Check if there is a token stored in localStorage, redirect to login if not: END

//         // Check if the token is valid, redirect to login if not: START
//         try {
//             const isTokenValid = await axios.get(
//                 `http://192.168.2.221/authify/public/api/validate?token=${encodeURIComponent(
//                     token
//                 )}`
//             );

//             if (isTokenValid.data.status !== "success") {
//                 localStorage.removeItem("authify-token");

//                 window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
//                     route("dashboard")
//                 )}`;
//                 return;
//             }
//         } catch (error) {
//             console.log("with error", error);
//         }
//         // Check if the token is valid, redirect to login if not: END

//         setIsLoading(false);
//     };

// return (
//     <div className="flex bg-base-200 h-screen text-sm">
//         <Sidebar />
//         <div className="h-full flex flex-col flex-1 overflow-y-hidden">
//             {/* <NavBar /> */}
//             <main className="px-4 py-8 z-10 flex-1 w-full relative overflow-y-auto">
//                 {isLoading && <LoadingScreen text="Please wait..." />}
//                 <div className="w-full">{children}</div>
//             </main>
//             {/* <Footer /> */}
//         </div>
//     </div>
// );
// }

import NavBar from "@/Components/NavBar";
import Sidebar from "@/Components/Sidebar/SideBar";
import LoadingScreen from "@/Components/LoadingScreen";
import { usePage, router } from "@inertiajs/react";
import axios from "axios";
import { useEffect, useState } from "react";
import Footer from "@/Components/Footer";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import { useWipStore } from "@/Store/overallDailyWipTrendStore";
import { useWorkweekStore } from "@/Store/workweekListStore";
import { useImportTraceStore } from "@/Store/importTraceStore";

export default function AuthenticatedLayout({ header, children }) {
    const { url } = usePage();
    const [isLoading, setIsLoading] = useState(true);
    const { fetchPackages } = useF1F2PackagesStore();
    const { fetchWip } = useWipStore();
    const { fetchWorkweek } = useWorkweekStore();
    const { fetchAllImports } = useImportTraceStore();

    useEffect(() => {
        fetchPackages();
        fetchWip();
        fetchWorkweek();
        fetchAllImports();
    }, []);

    useEffect(() => {
        authCheck();
    }, [url]);

    // useEffect(() => {
    //     const token = localStorage.getItem("authify-token");
    //     console.log("🚀 ~ AuthenticatedLayout ~ token:", token);
    //     if (!token) {
    //         window.location.href =
    //             "http://192.168.2.221/authify/public/login?redirect=http://localhost:8000";
    //     }
    // }, []);

    const authCheck = async () => {
        setIsLoading(true);

        // Check if the URL contains a query parameter "key" (token value): START
        const queryParams = new URLSearchParams(url.split("?")[1]);
        const queryToken = queryParams.get("key");

        if (queryToken) {
            localStorage.setItem("authify-token", queryToken);

            // Remove query params from the URL without reloading the page
            const cleanUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            router.post(route("setSession"), { queryToken });

            setIsLoading(false);
        }
        // Check if the URL contains a query parameter "key" (token value): END

        // Check if there is a token stored in localStorage, redirect to login if not: START
        const token = localStorage.getItem("authify-token");

        if (!token) {
            window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
                route("dashboard")
            )}`;
            return;
        }
        // Check if there is a token stored in localStorage, redirect to login if not: END

        // Check if the token is valid, redirect to login if not: START
        try {
            const isTokenValid = await axios.get(
                `http://192.168.2.221/authify/public/api/validate?token=${encodeURIComponent(
                    token
                )}`
            );

            if (isTokenValid.data.status !== "success") {
                localStorage.removeItem("authify-token");

                window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
                    route("dashboard")
                )}`;
                return;
            }
        } catch (error) {
            console.log("with error", error);
        }
        // Check if the token is valid, redirect to login if not: END

        setIsLoading(false);
    };

    // const authCheck = async () => {
    //     setIsLoading(true);

    //     // Check if the URL contains a query parameter "key" (token value): START
    //     const queryParams = new URLSearchParams(url.split("?")[1]);
    //     const queryToken = queryParams.get("key");

    //     if (queryToken) {
    //         localStorage.setItem("authify-token", queryToken);

    //         // Remove query params from the URL without reloading the page
    //         const cleanUrl = window.location.origin + window.location.pathname;
    //         window.history.replaceState({}, document.title, cleanUrl);
    //         router.post(route("setSession"), { queryToken });

    //         setIsLoading(false);
    //     }
    //     // Check if the URL contains a query parameter "key" (token value): END

    //     // Check if there is a token stored in localStorage, redirect to login if not: START
    //     const token = localStorage.getItem("authify-token");

    //     if (!token) {
    //         console.log("fjalskd");

    //         window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
    //             route("dashboard")
    //         )}`;

    //         // for dev local
    //         // window.location.href =
    //         //     "http://192.168.2.221/authify/public/login?redirect=http://localhost:8000";

    //         return;
    //     }
    //     // Check if there is a token stored in localStorage, redirect to login if not: END

    //     // Check if the token is valid, redirect to login if not: START
    //     try {
    //         const isTokenValid = await axios.get(
    //             `http://192.168.2.221/authify/public/api/validate?token=${encodeURIComponent(
    //                 token
    //             )}`
    //         );

    //         if (isTokenValid.data.status !== "success") {
    //             localStorage.removeItem("authify-token");
    //             console.log("N O T S U C C E S S");
    //             window.location.href = `http://192.168.2.221/authify/public/login?redirect=${encodeURIComponent(
    //                 route("dashboard")
    //             )}`;
    //             return;
    //         }
    //     } catch (error) {
    //         console.log("with error", error);
    //     }
    //     // Check if the token is valid, redirect to login if not: END

    //     setIsLoading(false);
    // };

    return (
        <div className="flex bg-base-200 h-screen text-sm">
            <Sidebar />
            <div className="h-full flex flex-col flex-1 overflow-y-hidden">
                <NavBar />
                <main className="px-4 py-8 z-10 flex-1 w-full relative overflow-y-auto">
                    {isLoading && <LoadingScreen text="Please wait..." />}
                    <div className="w-full">{children}</div>
                </main>
                <Footer />
            </div>
        </div>
    );
}
