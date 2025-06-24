import NavBar from "@/Components/NavBar";
import Sidebar from "@/Components/SideBar";
import { Link, usePage } from "@inertiajs/react";

export default function AuthenticatedLayout({ header, children }) {
    return (
        <div className="flex flex-col">
            <div className="flex h-screen overflow-hidden">
                <Sidebar />

                <div className="">
                    <NavBar />
                    <main className="w-full h-screen px-6 py-6 pb-[70px] overflow-y-auto">
                        <div className="">{children}</div>
                    </main>
                </div>
            </div>
        </div>
    );
}
