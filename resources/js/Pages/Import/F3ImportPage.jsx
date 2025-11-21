import Modal from "@/Components/Modal";
import React, { useEffect, useRef, useState } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportPageLayout from "../../Layouts/ImportPageLayout";
import { F3_WIP_HEADERS, F3_OUTS_HEADERS } from "@/Constants/ExcelHeaders";
import Collapse from "@/Components/Collapse";
import { FaFileUpload } from "react-icons/fa";
import ExcelUploader from "@/Components/FileUploader";
import DataTable from "@/Components/Table";
import ImportLabel from "./lastImportLabel";
import { useImportTraceStore } from "@/Store/importTraceStore";

const F3ImportPage = ({ pageName }) => {
    const { data: importTraceData, isLoading: isImportTraceLoading } =
        useImportTraceStore();

    const manualWIPImportRef = useRef(null);
    const manualOUTImportRef = useRef(null);
    const [selectedWIPFile, setSelectedWIPFile] = useState(null);
    const [selectedOUTFile, setSelectedOUTFile] = useState(null);

    const f3WipLabel = "F3 WIP";
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

    const handleManualWIPImport = () => {
        if (!selectedWIPFile) {
            return;
        }

        const formData = new FormData();
        formData.append("file", selectedWIPFile);

        runAsyncToast({
            action: () =>
                importWipQuantity(route("import.importF3WIP"), {
                    body: formData,
                    isContentTypeInclude: false,
                    isFormData: true,
                }),
            loadingMessage: `Importing ${f3WipLabel} data...`,
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        <span>{f3WipLabel}: </span>{" "}
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">
                            new {f3WipLabel} entries:
                        </span>
                        <span className="font-bold">
                            {Number(result?.data?.total ?? 0).toLocaleString()}
                        </span>
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">
                            ignored {f3WipLabel} entries:
                        </span>
                        <span className="font-bold">
                            {Number(
                                result?.data?.ignored.length ?? 0
                            ).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: importWipQuantityErrorMessage,
        });
    };

    const handleManualOUTImport = () => {
        if (!selectedOUTFile) {
            return;
        }

        const formData = new FormData();
        formData.append("file", selectedOUTFile);
        runAsyncToast({
            action: () =>
                importWipOuts(route("import.importF3OUTS"), {
                    body: formData,
                    isContentTypeInclude: false,
                    isFormData: true,
                }),
            loadingMessage: "Importing F3 OUTs data...",
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        <span>F3 OUTs: </span>{" "}
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">new F3 OUTs entries:</span>
                        <span className="font-bold">
                            {Number(result?.data?.total ?? 0).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: importOutQuantityErrorMessage,
        });
    };

    useEffect(() => {
        if (importWipQuantityErrorMessage) {
            setSelectedWIPFile(null);
        }
    }, [importWipQuantityErrorMessage]);

    useEffect(() => {
        if (importOutQuantityErrorMessage) {
            setSelectedOUTFile(null);
        }
    }, [importOutQuantityErrorMessage]);

    return (
        <ImportPageLayout pageName="F3">
            <div className="grid grid-cols-1 w-full gap-4">
                <div className="card border bg-base-100 border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">
                            Upload Daily {f3WipLabel}
                        </h2>
                        <p>
                            Get the latest data for {pageName} from the daily
                            WIP import. This must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f3_wip}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <Modal
                                ref={manualWIPImportRef}
                                id="deletePartModal"
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

                    {importWipQuantityData?.data?.ignored.length > 0 && (
                        <Collapse
                            title={`Ignored Rows (Not imported). Click to see details.`}
                            className={"w-full"}
                            contentClassName={"overflow-x-auto"}
                        >
                            <div className="mb-2">
                                showing{" "}
                                {importWipQuantityData?.data?.ignored.length}{" "}
                                out of{" "}
                                {importWipQuantityData?.data?.ignoredCount}
                            </div>
                            <div className="overflow-x-auto w-full max-h-96">
                                <DataTable
                                    columns={Object.keys(
                                        importWipQuantityData?.data?.ignored[0]
                                    )}
                                    rows={importWipQuantityData?.data?.ignored}
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
                                {importWipQuantityErrorData?.data?.missing
                                    .length > 0 && (
                                    <div className="mt-2">
                                        Missing headers:{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importWipQuantityErrorData?.data?.missing.map(
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
                                {importWipQuantityErrorData?.data?.unknown
                                    .length > 0 && (
                                    <div className="mt-2">
                                        Unknown (ignored):{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importWipQuantityErrorData?.data?.unknown.map(
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
                </div>

                <div className="card flex-1 bg-base-100 border border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">
                            Upload Daily {f3OutsLabel}
                        </h2>
                        <p>
                            Get the latest data for {pageName} from the daily
                            OUTS. This must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f3_out}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <Modal
                                ref={manualOUTImportRef}
                                id="deletePartModal"
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
                            legend="Pick an Excel file"
                            onFileValid={(file) => {
                                console.log("🚀 ~ onFileValid ~ file:", file);
                                setSelectedOUTFile(file);
                            }}
                        />
                        <button
                            className="btn btn-primary w-54"
                            onClick={() => manualOUTImportRef.current?.open()}
                            disabled={
                                isImportWipOutsLoading || !setSelectedOUTFile
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
                                {importOutsErrorData?.data?.missing.length >
                                    0 && (
                                    <div className="mt-2">
                                        Missing headers:{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importOutsErrorData?.data?.missing.map(
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
                                {importOutsErrorData?.data?.unknown.length >
                                    0 && (
                                    <div className="mt-2">
                                        Unknown (ignored):{" "}
                                    </div>
                                )}
                                <ul className="list">
                                    {importOutsErrorData?.data?.unknown.map(
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
                </div>
            </div>
        </ImportPageLayout>
    );
};

export default F3ImportPage;
