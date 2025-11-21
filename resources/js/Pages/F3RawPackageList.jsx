import { useMutation } from "@/Hooks/useMutation";
import { usePage, router, Link } from "@inertiajs/react";
import { useEffect, useState, useRef } from "react";
import { FaEdit, FaTrash } from "react-icons/fa";
import { FaPlus } from "react-icons/fa6";
import Modal from "@/Components/Modal";
import { useToast } from "@/Hooks/useToast";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";

const F3RawPackageList = () => {
    const toast = useToast();
    const isFirstRender = useRef(true);
    const {
        f3RawPackages: serverF3RawPackages,
        search: serverSearch,
        perPage: serverPerPage,
        totalEntries,
    } = usePage().props;

    const start = serverF3RawPackages.from;
    const end = serverF3RawPackages.to;
    const filteredTotal = serverF3RawPackages.total;
    const overallTotal = totalEntries ?? filteredTotal;
    const deleteModalRef = useRef(null);
    const [searchInput, setSearchInput] = useState(serverSearch || "");
    const [maxItem, setMaxItem] = useState(serverPerPage || 10);
    const [selectedRawPackage, setSelectedRawPackage] = useState(null);
    const [currentPage, setCurrentPage] = useState(
        serverF3RawPackages.current_page || 1
    );

    const f3RawPackageIndexRoute = route("f3.raw.package.index");

    const {
        mutate,
        isLoading: isMutateLoading,
        errorMessage: mutateErrorMessage,
        cancel: mutateCancel,
    } = useMutation();

    useEffect(() => {
        if (serverSearch === searchInput) return;

        const timer = setTimeout(() => {
            router.reload({
                data: { search: searchInput, perPage: maxItem, page: 1 },
            });
            setCurrentPage(1);
        }, 700);

        return () => clearTimeout(timer);
    }, [searchInput]);

    const goToPage = (page) => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, page },
        });
        setCurrentPage(page);
    };

    const changeMaxItemPerPage = (maxItem) => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, page: 1 },
        });
        setMaxItem(maxItem);
    };

    const refresh = () => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, currentPage },
        });
    };

    const handleDelete = async () => {
        try {
            await mutate(
                route("api.f3.raw.package.delete", {
                    id: selectedRawPackage.id,
                }),
                {
                    method: "DELETE",
                }
            );

            refresh();

            deleteModalRef.current.close();
            toast.success("Raw Package deleted successfully!");
        } catch (error) {
            toast.error(mutateErrorMessage);
            console.error(error);
        }
    };

    return (
        <>
            <div className="w-full px-4">
                <div className="flex items-center justify-between text-center">
                    <h1 className="text-base font-bold">F3 Raw Packages</h1>
                    <Link
                        href={route("f3.raw.package.create")}
                        className="btn btn-primary"
                    >
                        <FaPlus /> Add F3 Raw Package
                    </Link>
                </div>

                <div className="flex justify-between py-4">
                    <div className="dropdown dropdown-bottom">
                        <div tabIndex={0} className="m-1 btn">
                            {`Show ${maxItem} items`}
                        </div>
                        <ul
                            tabIndex={0}
                            className="p-2 shadow-lg dropdown-content menu bg-base-100 rounded-lg z-1 w-52"
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

                <table className="table w-full table-auto table-xs">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Raw Package Name</th>
                            <th>Lead Count</th>
                            <th>Package</th>
                            <th>Dimension</th>
                            <th>Added By</th>
                            <th>Created at</th>
                            <th>Updated at</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {serverF3RawPackages.data.map((rawPackage) => (
                            <tr key={rawPackage.id}>
                                <td>{rawPackage.id}</td>
                                <td>{rawPackage.raw_package}</td>
                                <td>{rawPackage?.lead_count || "-"}</td>
                                <td>
                                    {rawPackage?.f3_package_name
                                        ?.package_name || "-"}
                                </td>
                                <td>{rawPackage?.dimension || "-"}</td>
                                <td>{rawPackage?.added_by || "-"}</td>
                                <td>
                                    {formatFriendlyDate(
                                        rawPackage?.created_at
                                    ) || "-"}
                                </td>
                                <td>
                                    {formatFriendlyDate(
                                        rawPackage?.updated_at
                                    ) || "-"}
                                </td>
                                <td className="flex flex-col lg:flex-row">
                                    <a
                                        href={route("f3.raw.package.edit", {
                                            id: rawPackage.id,
                                            search: searchInput,
                                            perPage: maxItem,
                                            page: currentPage,
                                        })}
                                        className="btn btn-ghost btn-sm btn-primary"
                                    >
                                        <FaEdit />
                                    </a>
                                    <a
                                        className="btn btn-ghost btn-sm text-error"
                                        onClick={() => {
                                            setSelectedRawPackage(rawPackage);
                                            deleteModalRef.current.open();
                                        }}
                                    >
                                        <FaTrash />
                                    </a>

                                    <Modal
                                        ref={deleteModalRef}
                                        id="deleteF3RawPackageModal"
                                        title="Are you sure?"
                                        onClose={() =>
                                            deleteModalRef.current?.close()
                                        }
                                        className="max-w-lg"
                                    >
                                        <p className="px-2 pt-4">
                                            This action cannot be undone. Delete{" "}
                                            <span className="pl-1">
                                                {selectedRawPackage?.raw_package ||
                                                    "this?"}
                                            </span>
                                        </p>

                                        <p
                                            className="p-2 border rounded-lg bg-error/10 text-error"
                                            style={{
                                                visibility: mutateErrorMessage
                                                    ? "visible"
                                                    : "hidden",
                                            }}
                                        >
                                            {mutateErrorMessage ||
                                                "placeholder"}
                                        </p>

                                        <div className="flex justify-end gap-2 pt-4">
                                            <button
                                                className="btn btn-error"
                                                onClick={async () => {
                                                    await handleDelete();
                                                }}
                                                disabled={isMutateLoading}
                                            >
                                                {isMutateLoading ? (
                                                    <>
                                                        <span className="loading loading-spinner"></span>{" "}
                                                        Deleting
                                                    </>
                                                ) : (
                                                    "Confirm Delete"
                                                )}
                                            </button>

                                            <button
                                                className="btn btn-outline"
                                                onClick={() =>
                                                    deleteModalRef.current?.close()
                                                }
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </Modal>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="flex justify-between w-full mt-4">
                    <div className="content-center my-2 text-sm text-gray-600">
                        {`Showing ${start ?? 0} to ${
                            end ?? 0
                        } of ${filteredTotal.toLocaleString()} entries`}
                        {overallTotal && overallTotal !== filteredTotal
                            ? ` (filtered from ${overallTotal.toLocaleString()} total entries)`
                            : ""}
                    </div>
                    <div className="join">
                        {serverF3RawPackages.links.map((link, index) => {
                            const page = link.url
                                ? parseInt(
                                      new URL(link.url).searchParams.get("page")
                                  )
                                : currentPage;

                            return (
                                <button
                                    key={index}
                                    className={`join-item btn ${
                                        link.active || page === currentPage
                                            ? "text-white bg-primary"
                                            : "bg-base-200/50"
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
        </>
    );
};

export default F3RawPackageList;
