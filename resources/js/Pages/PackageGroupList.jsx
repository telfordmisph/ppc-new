import { usePage, router, Link } from "@inertiajs/react";
import { useToast } from "@/Hooks/useToast";
import { useEffect, useState, useRef } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { FaEdit, FaTrash } from "react-icons/fa";
import { BsInfoCircle } from "react-icons/bs";
import Modal from "@/Components/Modal";
import { FaPlus } from "react-icons/fa6";
import Tabs from "@/Components/Tabs";

const F1F2PackageGroupList = () => {
    const toast = useToast();

    const { packageGroups: serverPackageGroup, factory: initialFactory } =
        usePage().props;

    const deleteModalRef = useRef(null);
    const [selectedFactory, setSelectedFactory] = useState(initialFactory);
    const [selectedPackageGroup, setSelectedPackageGroup] = useState(null);

    const {
        mutate,
        isLoading: isMutateLoading,
        errorMessage: mutateErrorMessage,
        cancel: mutateCancel,
    } = useMutation();

    const handleFactoryChange = (selectedFactory) => {
        setSelectedFactory(selectedFactory);

        router.reload({
            data: { factory: selectedFactory },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const refresh = () => {
        router.reload({
            data: { factory: selectedFactory },
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleDelete = async () => {
        try {
            await mutate(
                route("api.package.delete", {
                    id: selectedPackageGroup.id,
                }),
                {
                    method: "DELETE",
                }
            );

            refresh();

            deleteModalRef.current.close();
            toast.success("Package Group deleted successfully!");
        } catch (error) {
            toast.error(mutateErrorMessage);
            console.error(error);
        }
    };

    const warningDetails = (
        <div className="flex gap-2 flex-col text-xs">
            <div className="bg-base-300 p-4 rounded-lg">
                <BsInfoCircle className="inline mb-1 mr-2" />
                Package names that do not exist here will not display any
                capacity. Please add them to the package group to view their
                capacity.
            </div>
            <div className="bg-base-300 p-4 rounded-lg">
                <BsInfoCircle className="inline mb-1 mr-2" />
                All package names on the
                <Link
                    href={route("package.capacity.index")}
                    className="underline text-secondary px-1"
                >
                    Capacity Page
                </Link>
                should also appear here, along with their corresponding
                canonical names in the package_name columns from the database.
            </div>
            <div className="bg-base-300 p-4 rounded-lg">
                <BsInfoCircle className="inline mb-1 mr-2" />
                Package names are case insensitive. That said, be sure to
                include all possible variants of the name in the group name.
                <p className="mt-2">
                    For example,
                    <span className="font-semibold"> 150 mils</span>,
                    <span className="font-semibold"> 150Mils </span>, and
                    <span className="font-semibold"> 150_MILS </span> are
                    considered different names. To group them together, both
                    should be included in the same package group.
                </p>
            </div>
        </div>
    );

    return (
        <>
            <div className="flex items-center justify-between text-center mb-4">
                <h1 className="text-base font-bold mb-2">Package Groups</h1>
                <Link
                    href={route("package.group.create")}
                    className="btn btn-primary"
                >
                    <FaPlus /> Add Package Group
                </Link>
            </div>

            {warningDetails}

            <Tabs
                options={["F1", "F2", "F3"]}
                selectedFactory={selectedFactory}
                handleFactoryChange={handleFactoryChange}
                tabClassName={"ml-2 py-4"}
            />

            <table className="table w-full table-zebra table-auto table-xs mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Factory</th>
                        <th>Group Name</th>
                        <th>Package Members / Dimension</th>
                    </tr>
                </thead>
                <tbody>
                    {serverPackageGroup?.map((packageGroup) => (
                        <tr key={packageGroup.id}>
                            <td>{packageGroup.id}</td>
                            <td className="uppercase">
                                {packageGroup.factory}
                            </td>
                            <td>{packageGroup?.group_name || "-"}</td>
                            <td>
                                <div className="flex flex-col gap-2">
                                    {packageGroup?.packages.map(
                                        (packageName) => (
                                            <div
                                                key={packageName.id}
                                                className="m-1"
                                            >
                                                <span className="border rounded-lg border-base-content/10 p-1">
                                                    {packageName?.package_name}
                                                </span>
                                            </div>
                                        )
                                    )}
                                </div>
                            </td>
                            <td className="flex flex-col lg:flex-row">
                                <Link
                                    href={route("package.group.edit", {
                                        id: packageGroup.id,
                                    })}
                                    className="btn btn-ghost btn-sm btn-primary"
                                >
                                    <FaEdit />
                                </Link>
                                <a
                                    href="#"
                                    className="btn btn-ghost btn-sm text-error"
                                    onClick={() => {
                                        setSelectedPackageGroup(packageGroup);
                                        deleteModalRef.current.open();
                                    }}
                                >
                                    <FaTrash />
                                </a>

                                <Modal
                                    ref={deleteModalRef}
                                    id="deletePackageGroupModal"
                                    title="Are you sure?"
                                    onClose={() =>
                                        deleteModalRef.current?.close()
                                    }
                                    className="max-w-lg"
                                >
                                    <p className="px-2 pt-4">
                                        This action cannot be undone. Delete
                                        this group?
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

export default F1F2PackageGroupList;
