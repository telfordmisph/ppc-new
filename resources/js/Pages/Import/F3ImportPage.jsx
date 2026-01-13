import Modal from "@/Components/Modal";
import React, { useEffect, useRef, useState } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportPageLayout from "../../Layouts/ImportPageLayout";
import { F3_WIP_HEADERS, F3_OUTS_HEADERS } from "@/Constants/ExcelHeaders";
import Collapse from "@/Components/Collapse";
import { FaFileUpload } from "react-icons/fa";
import FileUploader from "@/Components/FileUploader";
import DataTable from "@/Components/Table";
import ImportLabel from "../../Components/lastImportLabel";
import { useImportTraceStore } from "@/Store/importTraceStore";

const F3ImportPage = () => {
    const { data: importTraceData, isLoading: isImportTraceLoading } =
        useImportTraceStore();

    const uploaderWIPRef = useRef(null);
    const uploaderOUTRef = useRef(null);
    const uploaderF3Ref = useRef(null);
    const manualWIPImportRef = useRef(null);
    const manualOUTImportRef = useRef(null);
    const manualF3ImportRef = useRef(null);
    const [selectedWIPFile, setSelectedWIPFile] = useState(null);
    const [selectedOUTFile, setSelectedOUTFile] = useState(null);
    const [selectedF3File, setSelectedF3File] = useState(null);

    const f3WipLabel = "F3 WIP";
    const f3Label = "F3 WIP & OUTS";
    const f3OutsLabel = "F3 OUTS";

    const {
        isLoading: isImportWipQuantityLoading,
        errorMessage: importWipQuantityErrorMessage,
        errorData: importWipQuantityErrorData,
        mutate: importWipQuantity,
        data: importWipQuantityData,
    } = useMutation();

    const {
        isLoading: isImportWipOutsLoading,
        errorMessage: importOutQuantityErrorMessage,
        errorData: importOutsErrorData,
        mutate: importWipOuts,
    } = useMutation();

    const {
        isLoading: isImportF3Loading,
        errorMessage: importF3ErrorMessage,
        errorData: importF3ErrorData,
        mutate: importF3,
    } = useMutation();

    // const handleManualWIPImport = () => {
    //     if (!selectedWIPFile) {
    //         return;
    //     }

    //     const formData = new FormData();
    //     formData.append("file", selectedWIPFile);

    //     runAsyncToast({
    //         action: () =>
    //             importWipQuantity(route("import.importF3WIP"), {
    //                 body: formData,
    //                 isContentTypeInclude: false,
    //                 isFormData: true,
    //             }),
    //         loadingMessage: `Importing ${f3WipLabel} data...`,
    //         renderSuccess: (result) => (
    //             <>
    //                 <div className="mb-2 font-bold text-success">
    //                     <span>{f3WipLabel}: </span>{" "}
    //                     {result?.message || "Successfully imported!"}
    //                 </div>

    //                 <div className="flex justify-between">
    //                     <span className="font-light">
    //                         new {f3WipLabel} entries:
    //                     </span>
    //                     <span className="font-bold">
    //                         {Number(result?.data?.total ?? 0).toLocaleString()}
    //                     </span>
    //                 </div>

    //                 <div className="flex justify-between text-warning">
    //                     <span className="font-light">
    //                         ignored unknown package entries:
    //                     </span>
    //                     <span className="font-bold">
    //                         {Number(
    //                             result?.data?.ignored_unknown_package.length ??
    //                                 0
    //                         ).toLocaleString()}
    //                     </span>
    //                 </div>
    //             </>
    //         ),
    //         errorMessage: importWipQuantityErrorMessage,
    //     });

    //     uploaderWIPRef.current?.reset();
    // };

    // const handleManualOUTImport = () => {
    //     if (!selectedOUTFile) {
    //         return;
    //     }

    //     const formData = new FormData();
    //     formData.append("file", selectedOUTFile);
    //     runAsyncToast({
    //         action: () =>
    //             importWipOuts(route("import.importF3OUTS"), {
    //                 body: formData,
    //                 isContentTypeInclude: false,
    //                 isFormData: true,
    //             }),
    //         loadingMessage: "Importing F3 OUTs data...",
    //         renderSuccess: (result) => (
    //             <>
    //                 <div className="mb-2 font-bold text-success">
    //                     <span>F3 OUTs: </span>{" "}
    //                     {result?.message || "Successfully imported!"}
    //                 </div>

    //                 <div className="flex justify-between">
    //                     <span className="font-light">new F3 OUTs entries:</span>
    //                     <span className="font-bold">
    //                         {Number(result?.data?.total ?? 0).toLocaleString()}
    //                     </span>
    //                 </div>
    //             </>
    //         ),
    //         errorMessage: importOutQuantityErrorMessage,
    //     });

    //     uploaderOUTRef.current?.reset();
    // };

    const handleManualF3Import = () => {
        if (!selectedF3File) {
            return;
        }

        const formData = new FormData();
        formData.append("file", selectedF3File);
        runAsyncToast({
            action: () =>
                importF3(route("import.importF3"), {
                    body: formData,
                    isContentTypeInclude: false,
                    isFormData: true,
                }),
            loadingMessage: "Importing F3 data...",
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        <span>F3s: </span>{" "}
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">new F3 entries:</span>
                        <span className="font-bold">
                            {Number(result?.data?.total ?? 0).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: importF3ErrorMessage,
        });

        uploaderF3Ref.current?.reset();
    };

    // useEffect(() => {
    //     if (importWipQuantityErrorMessage) {
    //         setSelectedWIPFile(null);
    //     }
    // }, [importWipQuantityErrorMessage]);

    // useEffect(() => {
    //     if (importOutQuantityErrorMessage) {
    //         setSelectedOUTFile(null);
    //     }
    // }, [importOutQuantityErrorMessage]);

    useEffect(() => {
        if (importF3ErrorMessage) {
            setSelectedF3File(null);
        }
    }, [importF3ErrorMessage]);

    return (
        <ImportPageLayout pageName="F3">
            <div className="grid grid-cols-1 w-full gap-4">
                <div className="card flex-1 bg-base-100 border border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">Upload Daily {f3Label}</h2>
                        <p>Upload latest data for F3 WIPs and OUTs.</p>
                        <ImportLabel
                            data={importTraceData?.f3}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <Modal
                                ref={manualF3ImportRef}
                                id="f3ImportModal"
                                title={`Confirm upload ${f3Label} import`}
                                onClose={() =>
                                    manualF3ImportRef.current?.close()
                                }
                                className="max-w-lg"
                            >
                                <p className="py-4">
                                    Are you sure? This will start the {f3Label}{" "}
                                    import. Current import progress (if any)
                                    will block this action.
                                </p>

                                <div className="flex justify-end gap-2">
                                    <button
                                        className="btn btn-soft btn-warning"
                                        onClick={async () => {
                                            manualF3ImportRef.current?.close();
                                            handleManualF3Import();
                                        }}
                                        disabled={isImportF3Loading}
                                    >
                                        {isImportF3Loading && (
                                            <span className="loading loading-spinner"></span>
                                        )}
                                        Proceed
                                    </button>

                                    <button
                                        className="btn"
                                        onClick={() =>
                                            manualF3ImportRef.current?.close()
                                        }
                                        disabled={isImportF3Loading}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </Modal>
                        </div>
                        <FileUploader
                            ref={uploaderF3Ref}
                            legend="Pick an Excel file"
                            onFileValid={(file) => {
                                setSelectedF3File(file);
                            }}
                        />
                        <button
                            className="btn btn-primary w-54"
                            onClick={() => manualF3ImportRef.current?.open()}
                            disabled={isImportF3Loading || !selectedF3File}
                        >
                            Upload {f3Label}
                        </button>
                    </div>

                    {importF3ErrorMessage && importF3ErrorData?.data && (
                        <Collapse
                            title={`${importF3ErrorMessage}. Click to see details.`}
                            className={
                                "border-red-500 hover:border-red-500 bg-error/10"
                            }
                        >
                            {importF3ErrorData?.data?.missing_headers.length >
                                0 && (
                                <div className="mt-2">Missing headers: </div>
                            )}
                            <ul className="list">
                                {importF3ErrorData?.data?.missing_headers.map(
                                    (missing) => (
                                        <li
                                            className="list-row h-8 leading-none"
                                            key={missing}
                                        >
                                            {missing}
                                        </li>
                                    )
                                )}
                            </ul>
                            {importF3ErrorData?.data?.unknown_headers.length >
                                0 && (
                                <div className="mt-2">Unknown (ignored): </div>
                            )}
                            <ul className="list">
                                {importF3ErrorData?.data?.unknown_headers.map(
                                    (unknown) => (
                                        <li
                                            className="list-row h-8 leading-none"
                                            key={unknown}
                                        >
                                            {unknown}
                                        </li>
                                    )
                                )}
                            </ul>
                        </Collapse>
                    )}

                    <Collapse title={`${f3Label} Excel Headers Required`}>
                        <div className="text-secondary">
                            space and whitespace are the same. Case insensitive
                        </div>
                        <ul className="list">
                            {F3_OUTS_HEADERS.map((header) => (
                                <li
                                    className="list-row h-8 leading-none"
                                    key={header}
                                >
                                    {header}
                                </li>
                            ))}
                        </ul>
                    </Collapse>
                </div>

                {/* <div className="card border bg-base-100 border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">
                            Upload Daily {f3WipLabel}
                        </h2>
                        <p>
                            Get the latest data for F3 from the daily WIP
                            import. This must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f3_wip}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <Modal
                                ref={manualWIPImportRef}
                                id="f3WipImportModal"
                                title={`Confirm upload ${f3WipLabel} import`}
                                onClose={() =>
                                    manualWIPImportRef.current?.close()
                                }
                                className="max-w-lg"
                            >
                                <p className="py-4">
                                    Are you sure? This will start the WIP
                                    import. Current import progress (if any)
                                    will block this action.
                                </p>

                                <div className="flex justify-end gap-2">
                                    <button
                                        className="btn btn-soft btn-warning"
                                        onClick={async () => {
                                            manualWIPImportRef.current?.close();
                                            handleManualWIPImport();
                                        }}
                                        disabled={isImportWipQuantityLoading}
                                    >
                                        {isImportWipQuantityLoading && (
                                            <span className="loading loading-spinner"></span>
                                        )}
                                        Proceed
                                    </button>

                                    <button
                                        className="btn"
                                        onClick={() =>
                                            manualWIPImportRef.current?.close()
                                        }
                                        disabled={isImportWipQuantityLoading}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </Modal>
                        </div>
                        <ExcelUploader
                            ref={uploaderWIPRef}
                            legend="Pick an Excel file"
                            onFileValid={(file) => {
                                setSelectedWIPFile(file);
                            }}
                        />
                        <button
                            className="btn btn-primary w-54"
                            onClick={() => manualWIPImportRef.current?.open()}
                            disabled={
                                isImportWipQuantityLoading || !selectedWIPFile
                            }
                        >
                            Upload {f3WipLabel}
                        </button>
                    </div>

                    {importWipQuantityData?.data?.ignored_unknown_package
                        .length > 0 && (
                        <Collapse
                            title={`Unknown Package: ignored Rows (Not imported). Click to see details.`}
                            className={"w-full text-warning"}
                            contentClassName={
                                "overflow-x-auto text-base-content"
                            }
                        >
                            <div className="mb-2 ">
                                showing{" "}
                                {
                                    importWipQuantityData?.data
                                        ?.ignored_unknown_package.length
                                }{" "}
                                out of{" "}
                                {
                                    importWipQuantityData?.data
                                        ?.ignored_unknown_package_count
                                }
                            </div>
                            <div className="overflow-x-auto w-full max-h-96">
                                <DataTable
                                    columns={Object.keys(
                                        importWipQuantityData?.data
                                            ?.ignored_unknown_package[0]
                                    )}
                                    rows={
                                        importWipQuantityData?.data
                                            ?.ignored_unknown_package
                                    }
                                    className={"w-full"}
                                />
                            </div>
                        </Collapse>
                    )}

                    {importWipQuantityErrorMessage &&
                        importWipQuantityErrorData?.data && (
                            <Collapse
                                title={`${importWipQuantityErrorMessage}. Click to see details.`}
                                className={
                                    "border-red-500 hover:border-red-500 bg-error/10"
                                }
                            >
                                {importWipQuantityErrorData?.data
                                    ?.missing_headers.length > 0 && (
                                    <div className="mt-2">
                                        Missing headers:{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importWipQuantityErrorData?.data?.missing_headers.map(
                                        (missing) => (
                                            <li
                                                className="list-row h-8 leading-none"
                                                key={missing}
                                            >
                                                {missing}
                                            </li>
                                        )
                                    )}
                                </ul>
                                {importWipQuantityErrorData?.data
                                    ?.unknown_headers.length > 0 && (
                                    <div className="mt-2">
                                        Unknown (ignored):{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importWipQuantityErrorData?.data?.unknown_headers.map(
                                        (unknown) => (
                                            <li
                                                className="list-row h-8 leading-none"
                                                key={unknown}
                                            >
                                                {unknown}
                                            </li>
                                        )
                                    )}
                                </ul>
                            </Collapse>
                        )}

                    <Collapse title={`${f3WipLabel} Excel Headers Required`}>
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
                    </Collapse>
                </div> */}

                {/* <div className="card flex-1 bg-base-100 border border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">
                            Upload Daily {f3OutsLabel}
                        </h2>
                        <p>
                            Get the latest data for F3 from the daily OUTS. This
                            must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f3_out}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <Modal
                                ref={manualOUTImportRef}
                                id="f3OutImportModal"
                                title={`Confirm upload ${f3OutsLabel} import`}
                                onClose={() =>
                                    manualOUTImportRef.current?.close()
                                }
                                className="max-w-lg"
                            >
                                <p className="py-4">
                                    Are you sure? This will start the{" "}
                                    {f3OutsLabel} import. Current import
                                    progress (if any) will block this action.
                                </p>

                                <div className="flex justify-end gap-2">
                                    <button
                                        className="btn btn-soft btn-warning"
                                        onClick={async () => {
                                            manualOUTImportRef.current?.close();
                                            handleManualOUTImport();
                                        }}
                                        disabled={isImportWipOutsLoading}
                                    >
                                        {isImportWipOutsLoading && (
                                            <span className="loading loading-spinner"></span>
                                        )}
                                        Proceed
                                    </button>

                                    <button
                                        className="btn"
                                        onClick={() =>
                                            manualOUTImportRef.current?.close()
                                        }
                                        disabled={isImportWipOutsLoading}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </Modal>
                        </div>
                        <ExcelUploader
                            ref={uploaderOUTRef}
                            legend="Pick an Excel file"
                            onFileValid={(file) => {
                                setSelectedOUTFile(file);
                            }}
                        />
                        <button
                            className="btn btn-primary w-54"
                            onClick={() => manualOUTImportRef.current?.open()}
                            disabled={
                                isImportWipOutsLoading || !selectedOUTFile
                            }
                        >
                            Upload {f3OutsLabel}
                        </button>
                    </div>

                    {importOutQuantityErrorMessage &&
                        importOutsErrorData?.data && (
                            <Collapse
                                title={`${importOutQuantityErrorMessage}. Click to see details.`}
                                className={
                                    "border-red-500 hover:border-red-500 bg-error/10"
                                }
                            >
                                {importOutsErrorData?.data?.missing_headers
                                    .length > 0 && (
                                    <div className="mt-2">
                                        Missing headers:{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importOutsErrorData?.data?.missing_headers.map(
                                        (missing) => (
                                            <li
                                                className="list-row h-8 leading-none"
                                                key={missing}
                                            >
                                                {missing}
                                            </li>
                                        )
                                    )}
                                </ul>
                                {importOutsErrorData?.data?.unknown_headers
                                    .length > 0 && (
                                    <div className="mt-2">
                                        Unknown (ignored):{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importOutsErrorData?.data?.unknown_headers.map(
                                        (unknown) => (
                                            <li
                                                className="list-row h-8 leading-none"
                                                key={unknown}
                                            >
                                                {unknown}
                                            </li>
                                        )
                                    )}
                                </ul>
                            </Collapse>
                        )}

                    <Collapse title={`${f3OutsLabel} Excel Headers Required`}>
                        <div className="text-secondary">
                            space and whitespace are the same. Case insensitive
                        </div>
                        <ul className="list">
                            {F3_OUTS_HEADERS.map((header) => (
                                <li
                                    className="list-row h-8 leading-none"
                                    key={header}
                                >
                                    {header}
                                </li>
                            ))}
                        </ul>
                    </Collapse>
                </div> */}
            </div>
        </ImportPageLayout>
    );
};

export default F3ImportPage;
