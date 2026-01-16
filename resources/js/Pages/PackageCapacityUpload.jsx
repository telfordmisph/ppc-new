import { router } from "@inertiajs/react";
import Modal from "@/Components/Modal";
import React, { useEffect, useRef, useState } from "react";
import { F3_WIP_HEADERS } from "@/Constants/ExcelHeaders";
import Collapse from "@/Components/Collapse";
import FileUploader from "@/Components/FileUploader";
import DataTable from "@/Components/Table";
import ImportLabel from "@/Components/lastImportLabel";
import { useImportTraceStore } from "@/Store/importTraceStore";
import { useMutation } from "@/Hooks/useMutation";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import Tabs from "@/Components/Tabs";
import { useDownloadFile } from "@/Hooks/useDownload";

const PackageCapacityUpload = () => {
    const { data: importTraceData, isLoading: isImportTraceLoading } =
        useImportTraceStore();

    const uploaderRef = useRef(null);
    const manualCapacityUploadRef = useRef(null);
    const [selectedCapacityFile, setSelectedCapacityFile] = useState(null);

    const packageCapacityLabel = "Package Capacity";

    const {
        download,
        isLoading: isDownloadLoading,
        errorMessage,
    } = useDownloadFile();

    const handleDownloadCapacityTemplate = () => {
        download(route("api.download.downloadCapacityTemplate"));
    };

    const {
        isLoading: capacityLoading,
        errorMessage: capacityErrorMessage,
        errorData: capacityErrorData,
        mutate: uploadCapacity,
        data: capacityData,
    } = useMutation();

    const handleManualCapacityImport = () => {
        if (!selectedCapacityFile) {
            return;
        }

        const formData = new FormData();
        formData.append("file", selectedCapacityFile);

        runAsyncToast({
            action: () =>
                uploadCapacity(route("import.capacity"), {
                    body: formData,
                    isContentTypeInclude: false,
                    isFormData: true,
                }),
            loadingMessage: `Importing ${packageCapacityLabel} data...`,
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        <span>{packageCapacityLabel}: </span>{" "}
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">
                            created {packageCapacityLabel} entries:
                        </span>
                        <span className="font-bold">
                            {Number(
                                result?.created.length ?? 0
                            ).toLocaleString()}
                        </span>
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">
                            updated {packageCapacityLabel} entries:
                        </span>
                        <span className="font-bold">
                            {Number(
                                result?.updated.length ?? 0
                            ).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: capacityErrorMessage,
        });

        uploaderRef.current?.reset();
    };

    useEffect(() => {
        if (capacityErrorMessage) {
            setSelectedCapacityFile(null);
        }
    }, [capacityErrorMessage]);

    return (
        <>
            <div className="flex items-center justify-between text-center">
                <Tabs
                    options={["Package Capacity List", "Upload New Capacity"]}
                    selectedFactory={"Upload New Capacity"}
                    handleFactoryChange={() =>
                        router.visit(route("package.capacity.index"))
                    }
                />
            </div>
            <div className="card border mt-4 bg-base-100 border-base-content/20">
                <div className="card-body">
                    <h2 className="card-title">
                        Upload {packageCapacityLabel}
                    </h2>
                    <p>Upload new capacity for packages.</p>
                    <ImportLabel
                        data={importTraceData?.capacity}
                        loading={isImportTraceLoading}
                    />
                    <div className="card-actions justify-end">
                        <Modal
                            ref={manualCapacityUploadRef}
                            id="packageCapacityUploadModal"
                            title={`Confirm upload ${packageCapacityLabel}`}
                            onClose={() =>
                                manualCapacityUploadRef.current?.close()
                            }
                            className="max-w-lg"
                        >
                            <p className="py-4">
                                Make sure the package name from the file matches
                                the package name in the system. Typos will cause
                                them to be treated as different packages.
                            </p>

                            <div className="flex justify-end gap-2">
                                <button
                                    className="btn btn-soft btn-warning"
                                    onClick={async () => {
                                        manualCapacityUploadRef.current?.close();
                                        handleManualCapacityImport();
                                    }}
                                    disabled={capacityLoading}
                                >
                                    {capacityLoading && (
                                        <span className="loading loading-spinner"></span>
                                    )}
                                    Proceed
                                </button>

                                <button
                                    className="btn"
                                    onClick={() =>
                                        manualCapacityUploadRef.current?.close()
                                    }
                                    disabled={capacityLoading}
                                >
                                    Cancel
                                </button>
                            </div>
                        </Modal>
                    </div>
                    <FileUploader
                        ref={uploaderRef}
                        legend="Pick an Excel file"
                        onFileValid={(file) => {
                            setSelectedCapacityFile(file);
                        }}
                        downloadClick={handleDownloadCapacityTemplate}
                        isDownloadLoading={isDownloadLoading}
                    />
                    <button
                        className="btn btn-primary w-60"
                        onClick={() => manualCapacityUploadRef.current?.open()}
                        disabled={capacityLoading || !selectedCapacityFile}
                    >
                        Upload {packageCapacityLabel}
                    </button>
                </div>
                {capacityData?.updated.length > 0 && (
                    <Collapse
                        title={`Updated Package Capacity List`}
                        className={"w-full text-orange-600"}
                        contentClassName={"overflow-x-auto"}
                    >
                        <div className="overflow-x-auto w-full max-h-96 text-base-content">
                            <DataTable
                                columns={Object.keys(capacityData?.updated[0])}
                                rows={capacityData?.updated}
                                className={"w-full"}
                            />
                        </div>
                    </Collapse>
                )}
                {capacityData?.created.length > 0 && (
                    <Collapse
                        title={`Created Package Capacity List`}
                        className={"w-full text-green-400"}
                        contentClassName={"overflow-x-auto"}
                    >
                        <div className="overflow-x-auto w-full max-h-96 text-base-content">
                            <DataTable
                                columns={Object.keys(capacityData?.created[0])}
                                rows={capacityData?.created}
                                className={"w-full"}
                            />
                        </div>
                    </Collapse>
                )}

                {/* // TODO different: no known headers, just pattern. this is applicable */}

                {capacityErrorMessage && capacityErrorData?.data && (
                    <Collapse
                        title={`${capacityErrorMessage}. Click to see details.`}
                        className={
                            "border-red-500 hover:border-red-500 bg-error/10"
                        }
                    >
                        {capacityErrorData?.data?.missing.length > 0 && (
                            <div className="mt-2">Missing headers: </div>
                        )}
                        <ul className="list">
                            {capacityErrorData?.data?.missing.map((missing) => (
                                <li
                                    className="list-row h-8 leading-none"
                                    key={missing}
                                >
                                    {missing}
                                </li>
                            ))}
                        </ul>
                        {capacityErrorData?.data?.unknown.length > 0 && (
                            <div className="mt-2">Unknown (ignored): </div>
                        )}
                        <ul className="list">
                            {capacityErrorData?.data?.unknown.map((unknown) => (
                                <li
                                    className="list-row h-8 leading-none"
                                    key={unknown}
                                >
                                    {unknown}
                                </li>
                            ))}
                        </ul>
                    </Collapse>
                )}
                {/* <Collapse
                    title={`${packageCapacityLabel} Excel Headers Required`}
                >
                    <div className="text-secondary">
                        space and whitespace are the same. Case insensitive
                    </div>
                    <ul className="list">
                        {F3_WIP_HEADERS.map((header) => (
                            <li
                                className="list-row h-8 leading-none"
                                key={header}
                            >
                                {header}
                            </li>
                        ))}
                    </ul>
                </Collapse> */}
            </div>
        </>
    );
};

export default PackageCapacityUpload;
