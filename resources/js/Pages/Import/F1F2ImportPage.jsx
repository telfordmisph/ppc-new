import Modal from "@/Components/Modal";
import React, { useRef } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportPageLayout from "../../Layouts/ImportPageLayout";
import { TbAlertCircle } from "react-icons/tb";
import { useImportTraceStore } from "@/Store/importTraceStore";
import ImportLabel from "../../Components/lastImportLabel";

const F1F2ImportPage = () => {
    const { data: importTraceData, isLoading: isImportTraceLoading } =
        useImportTraceStore();

    const manualWIPImportRef = useRef(null);
    const manualOUTImportRef = useRef(null);
    const {
        isLoading: isImportWipQuantityLoading,
        errorMessage: importWipQuantityErrorMessage,
        errorData: importWipQuantityErrorData,
        mutate: importWipQuantity,
    } = useMutation();

    const {
        isLoading: isImportWipOutsLoading,
        errorMessage: importWipOutsErrorMessage,
        errorData: importWipOutsErrorData,
        mutate: importWipOuts,
    } = useMutation();

    const handleManualWIPImport = () => {
        runAsyncToast({
            action: () => importWipQuantity(route("import.autoImportWIP")),
            loadingMessage: "Importing WIP data...",
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">new f1/f2 entries:</span>
                        <span className="font-bold">
                            {Number(result?.data?.f1f2 ?? 0).toLocaleString()}
                        </span>
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">new f3 entries:</span>
                        <span className="font-bold">
                            {Number(result?.data?.f3 ?? 0).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: importWipQuantityErrorMessage,
        });
    };

    const handleManualOUTImport = () => {
        runAsyncToast({
            action: () => importWipOuts(route("import.autoImportWIPOUTS")),
            loadingMessage: "Importing OUTs data...",
            renderSuccess: (result) => (
                <>
                    <div className="mb-2 font-bold text-success">
                        {result?.message || "Successfully imported!"}
                    </div>

                    <div className="flex justify-between">
                        <span className="font-light">new entries:</span>
                        <span className="font-bold">
                            {Number(result?.total ?? 0).toLocaleString()}
                        </span>
                    </div>
                </>
            ),
            errorMessage: importWipOutsErrorMessage,
        });
    };

    return (
        <ImportPageLayout pageName="F1/F2">
            <div className="grid grid-cols-1 w-full gap-4">
                <div className="card border bg-base-100 border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">Refresh Daily WIP Import</h2>
                        <p>
                            Get the latest data for F1/F2 from the daily WIP
                            import. This must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f1f2_wip}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <button
                                className="btn btn-primary"
                                onClick={() =>
                                    manualWIPImportRef.current?.open()
                                }
                            >
                                Refresh auto daily WIP import
                            </button>
                            <Modal
                                ref={manualWIPImportRef}
                                id="f1f2WipImportModal"
                                title="Refresh auto daily WIP import"
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
                        {importWipQuantityErrorData?.data?.partialSuccess !=
                            null && (
                            <div className="text-error border border-error p-4 rounded-lg flex items-center gap-2">
                                <span>
                                    <TbAlertCircle />
                                </span>
                                <span>
                                    Import Interrupted.{" "}
                                    {
                                        importWipQuantityErrorData.data
                                            .partialSuccess
                                    }{" "}
                                    entries imported successfully. You might
                                    want to try again, all successful imports
                                    will be ignored.
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="card flex-1 bg-base-100 border border-base-content/20">
                    <div className="card-body">
                        <h2 className="card-title">Refresh Daily Outs</h2>
                        <p>
                            Get the latest data for F1/F2 from the daily OUTS.
                            This must be done atleast once a day.
                        </p>
                        <ImportLabel
                            data={importTraceData?.f1f2_out}
                            loading={isImportTraceLoading}
                        />
                        <div className="card-actions justify-end">
                            <button
                                className="btn btn-primary"
                                onClick={() =>
                                    manualOUTImportRef.current?.open()
                                }
                            >
                                Refresh auto daily OUTs import
                            </button>
                            <Modal
                                ref={manualOUTImportRef}
                                id="f1f2OutImportModal"
                                title="Refresh auto daily OUTS import"
                                onClose={() =>
                                    manualOUTImportRef.current?.close()
                                }
                                className="max-w-lg"
                            >
                                <p className="py-4">
                                    Are you sure? This will start the OUTS
                                    import. Current import progress (if any)
                                    will block this action.
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
                        {importWipOutsErrorData?.data?.partialSuccess !=
                            null && (
                            <div className="text-error border border-error p-4 rounded-lg flex items-center gap-2">
                                <span>
                                    <TbAlertCircle />
                                </span>
                                <span>
                                    Import Interrupted.{" "}
                                    {importWipOutsErrorData.data.partialSuccess}{" "}
                                    entries imported successfully. You might
                                    want to try again, all successful imports
                                    will be ignored.
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ImportPageLayout>
    );
};

export default F1F2ImportPage;
