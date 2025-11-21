import { usePage, router, Link } from "@inertiajs/react";
import { useToast } from "@/Hooks/useToast";
import { useEffect, useState, useRef } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { FaEdit, FaTrash } from "react-icons/fa";
import Modal from "@/Components/Modal";

const PartNameList = () => {
    const toast = useToast();

    const {
        packageGroups: serverPackageGroup,
        search: serverSearch,
        perPage: serverPerPage,
        totalEntries,
    } = usePage().props;

    const start = serverPackageGroup.from;
    const end = serverPackageGroup.to;
    const filteredTotal = serverPackageGroup.total;
    const overallTotal = totalEntries ?? filteredTotal;
    const deleteModalRef = useRef(null);
    const [searchInput, setSearchInput] = useState(serverSearch || "");
    const [maxItem, setMaxItem] = useState(serverPerPage || 10);
    const [selectedPackageGroup, setSelectedPackageGroup] = useState(null);
    const [currentPage, setCurrentPage] = useState(
        serverPackageGroup.current_page || 1
    );
    const packageGroupIndexPageRoute = route("package.group.index");

    const {
        mutate,
        isLoading: isMutateLoading,
        errorMessage: mutateErrorMessage,
        cancel: mutateCancel,
    } = useMutation();

    useEffect(() => {
        const timer = setTimeout(() => {
            router.reload({
                data: { search: searchInput, perPage: maxItem, page: 1 },
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
            setCurrentPage(1);
        }, 700);

        return () => clearTimeout(timer);
    }, [searchInput]);

    useEffect(() => {
        console.log(serverPackageGroup);
    }, []);

    const goToPage = (page) => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, page },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        setCurrentPage(page);
    };

    const changeMaxItemPerPage = (maxItem) => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, page: 1 },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        setMaxItem(maxItem);
    };

    const refresh = () => {
        router.reload({
            data: { search: searchInput, perPage: maxItem, currentPage },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDelete = async () => {
        try {
            await mutate(
                route("api.package.group.delete", {
                    id: selectedPackageGroup.ppc_partnamedb_id,
                }),
                {
                    method: "DELETE",
                }
            );

            refresh();

            deleteModalRef.current.close();
            toast.success("Part deleted successfully!");
        } catch (error) {
            toast.error(mutateErrorMessage);
            console.error(error);
        }
    };

    return (
        <>
            <div className="flex items-center justify-between text-center">
                <h1 className="text-base font-bold">Package Groups</h1>
            </div>
            <table className="table w-full table-auto table-xs">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Factory</th>
                        <th>Group Name</th>
                        <th>Package Members</th>
                    </tr>
                </thead>
                <tbody>
                    {serverPackageGroup.data.map((packageGroup) => (
                        <tr key={packageGroup.id}>
                            <td>{packageGroup.id}</td>
                            <td>{packageGroup.factory}</td>
                            <td>{packageGroup?.group_name || "-"}</td>
                            <td>
                                {packageGroup?.packages.map((packageName) => (
                                    <p key={packageName.id}>
                                        {packageName?.package_name}
                                    </p>
                                ))}
                            </td>
                            <td className="flex flex-col lg:flex-row">
                                <Link
                                    href={route("package.group.edit", {
                                        id: packageGroup.id,
                                        search: searchInput,
                                        perPage: maxItem,
                                        page: currentPage,
                                    })}
                                    className="btn btn-ghost btn-sm btn-primary"
                                >
                                    <FaEdit />
                                </Link>
                                <Link
                                    className="btn btn-ghost btn-sm text-error"
                                    onClick={() => {
                                        selectedPackageGroup(packageGroup);
                                        deleteModalRef.current.open();
                                    }}
                                >
                                    <FaTrash />
                                </Link>

                                <Modal
                                    ref={deleteModalRef}
                                    id="deletePartModal"
                                    title="Are you sure?"
                                    onClose={() =>
                                        deleteModalRef.current?.close()
                                    }
                                    className="max-w-lg"
                                >
                                    <p className="px-2 pt-4">
                                        This action cannot be undone. Delete{" "}
                                        <span className="pl-1">
                                            this group?
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
                                        {mutateErrorMessage || "placeholder"}
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
        </>
    );
};

export default PartNameList;
