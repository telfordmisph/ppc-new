import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { formatISOTimestampToDate } from "@/Utils/formatISOTimestampToDate";
import { usePage, router } from "@inertiajs/react";
import toast from "react-hot-toast";
import { useEffect, useState } from "react";

const PartNameList = () => {
    const {
        partNames: serverPartNames,
        search: serverSearch,
        perPage: serverPerPage,
        totalEntries,
    } = usePage().props;

    console.log("🚀 ~ PartNameList ~ serverPartNames:", serverPartNames);
    console.log("🚀 ~ serverSearch ~ serverSearch:", serverSearch);

    const start = serverPartNames.from;
    const end = serverPartNames.to;
    const filteredTotal = serverPartNames.total;
    const overallTotal = totalEntries ?? filteredTotal;

    // Local states for input controls
    const [searchInput, setSearchInput] = useState(serverSearch || "");
    const [maxItem, setMaxItem] = useState(serverPerPage || 10);

    // Local state for current page (optional, for UI highlighting)
    const [currentPage, setCurrentPage] = useState(
        serverPartNames.current_page || 1
    );

    // Debounced search effect
    useEffect(() => {
        const timer = setTimeout(() => {
            router.visit(route("partname.index"), {
                data: { search: searchInput, perPage: maxItem, page: 1 },
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
            setCurrentPage(1); // Reset local page for highlighting
        }, 2000);

        return () => clearTimeout(timer);
    }, [searchInput]);

    // Handle page navigation
    const goToPage = (page) => {
        router.visit(route("partname.index"), {
            data: { search: searchInput, perPage: maxItem, page },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        setCurrentPage(page);
    };

    const changeMaxItemPerPage = (maxItem) => {
        router.visit(route("partname.index"), {
            data: { search: searchInput, perPage: maxItem, page: 1 },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        setMaxItem(maxItem);
    };

    return (
        <AuthenticatedLayout>
            <div className="px-4">
                <h1 className="mb-4 text-2xl font-bold">Part Names</h1>

                {/* Controls */}
                <div className="flex justify-between py-4">
                    {/* Max items dropdown */}
                    <div className="dropdown dropdown-bottom">
                        <div tabIndex={0} className="m-1 btn">
                            {`Show ${maxItem} items`}
                        </div>
                        <ul
                            tabIndex={0}
                            className="p-2 shadow-sm dropdown-content menu bg-base-100 rounded-box z-1 w-52"
                        >
                            {[10, 25, 50, 100].map((item) => (
                                <li key={item}>
                                    <a
                                        onClick={() => {
                                            changeMaxItemPerPage(item);
                                        }}
                                        className="flex items-center justify-between"
                                    >
                                        {item}
                                        {maxItem === item && (
                                            <span className="font-bold text-green-500">
                                                ✔
                                            </span>
                                        )}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Search input */}
                    <label className="input">
                        <svg
                            className="h-[1em] opacity-50"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                        >
                            <g
                                strokeLinejoin="round"
                                strokeLinecap="round"
                                strokeWidth="2.5"
                                fill="none"
                                stroke="currentColor"
                            >
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </g>
                        </svg>
                        <input
                            type="search"
                            placeholder="Search"
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                        />
                    </label>
                </div>

                {/* Table */}
                <table className="table w-full table-auto table-xs">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Part Name</th>
                            <th>Focus Group</th>
                            <th>Factory</th>
                            <th>PL</th>
                            <th>Package Name</th>
                            <th>Lead Count</th>
                            <th>Body Size</th>
                            <th>Package</th>
                            <th>Added By</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {serverPartNames.data.map((part) => (
                            <tr key={part.ppc_partnamedb_id}>
                                <td>{part.ppc_partnamedb_id}</td>
                                <td>{part?.Partname || "-"}</td>
                                <td>{part?.Focus_grp || "-"}</td>
                                <td>{part?.Factory || "-"}</td>
                                <td>{part?.PL || "-"}</td>
                                <td>{part?.Packagename || "-"}</td>
                                <td>{part?.Leadcount || "-"}</td>
                                <td>{part?.Bodysize || "-"}</td>
                                <td>{part?.Package || "-"}</td>
                                <td>{part?.added_by || "-"}</td>
                                <td>
                                    {formatISOTimestampToDate(
                                        part?.date_created
                                    )}
                                </td>
                                <td>
                                    <button className="btn btn-sm">Edit</button>
                                    <button className="btn btn-sm text-error">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* Pagination */}
                <div className="flex w-full justify-between mt-4">
                    <div className="text-sm text-gray-600 content-center my-2">
                        {`Showing ${start ?? 0} to ${
                            end ?? 0
                        } of ${filteredTotal.toLocaleString()} entries`}
                        {overallTotal && overallTotal !== filteredTotal
                            ? ` (filtered from ${overallTotal.toLocaleString()} total entries)`
                            : ""}
                    </div>
                    <div className="join">
                        {serverPartNames.links.map((link, index) => {
                            // Extract numeric page from URL
                            const page = link.url
                                ? parseInt(
                                      new URL(link.url).searchParams.get("page")
                                  )
                                : currentPage;

                            return (
                                <button
                                    key={index}
                                    className={`join-item btn rounded ${
                                        link.active || page === currentPage
                                            ? "bg-accent"
                                            : ""
                                    }`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        if (!link.url) return;
                                        goToPage(page);
                                    }}
                                    disabled={!link.url}
                                />
                            );
                        })}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default PartNameList;
