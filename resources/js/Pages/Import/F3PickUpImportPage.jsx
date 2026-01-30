import Modal from "@/Components/Modal";
import React, { useEffect, useRef, useState } from "react";
import { useMutation } from "@/Hooks/useMutation";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportPageLayout from "../../Layouts/ImportPageLayout";
import { F3_PICKUP_HEADERS } from "@/Constants/ExcelHeaders";
import Collapse from "@/Components/Collapse";
import FileUploader from "@/Components/FileUploader";
import ImportLabel from "../../Components/lastImportLabel";
import { useImportTraceStore } from "@/Store/importTraceStore";
import { useDownloadFile } from "@/Hooks/useDownload";
import { Link } from "@inertiajs/react";
import { FaLink } from "react-icons/fa6";
import DataTable from "@/Components/Table";
import { MdWarning } from "react-icons/md";
import { Inertia } from "@inertiajs/inertia";

const F3PickUpImportPage = () => {
	const { data: importTraceData, isLoading: isImportTraceLoading } =
		useImportTraceStore();

	const uploaderPickUpRef = useRef(null);
	const manualPickUpImportRef = useRef(null);
	const [selectedPickUpFile, setSelectedPickUpFile] = useState(null);

	const pickUpLabel = "PickUPs";

	const {
		download,
		isLoading: isDownloadLoading,
		errorMessage,
	} = useDownloadFile();

	const handleDownloadPickUpTemplate = () => {
		download(route("api.download.downloadF3PickUpTemplate"));
	};

	const {
		isLoading: isImportF3PickUpLoading,
		errorMessage: importF3PickUpErrorMessage,
		errorData: importF3PickUpErrorData,
		mutate: importF3PickUp,
		data: importF3PickUpData,
	} = useMutation();
	console.log("🚀 ~ F3PickUpImportPage ~ importF3PickUp:", importF3PickUp);

	const handleManualPickUpImport = () => {
		if (!selectedPickUpFile) {
			return;
		}

		const formData = new FormData();
		formData.append("file", selectedPickUpFile);
		runAsyncToast({
			action: () =>
				importF3PickUp(route("import.importF3PickUp"), {
					body: formData,
					isContentTypeInclude: false,
					isFormData: true,
				}),
			loadingMessage: "Importing PickUp data...",
			renderSuccess: (result) => (
				<>
					<div className="mb-2 font-bold">
						<span>F3 PickUps: </span>{" "}
						{result?.data?.ignored_unknown_package?.length > 0 &&
							"Partially imported!"}
						{result?.data?.ignored_unknown_package?.length == 0 &&
							(result?.message || "Successfully imported!")}
					</div>

					<div className="flex flex-col justify-between">
						{result?.data?.ignored_unknown_package?.length > 0 && (
							<div className="bg-warning text-warning-content px-2">
								ignored rows with unknown packages:{" "}
								<span>{result?.data?.ignored_unknown_package?.length}</span>
							</div>
						)}
						<div className="flex gap-2 px-2">
							<span className="font-light">new F3 pickup entries:</span>
							<span className="font-bold">
								{Number(result?.data?.total ?? 0).toLocaleString()}
							</span>
						</div>
					</div>
				</>
			),
			// renderSuccess: (result) => (
			//     <>
			//         <div className="mb-2 font-bold text-success">
			//             <span>PickUps: </span>{" "}
			//             {result?.message || "Successfully imported!"}
			//         </div>

			//         <div className="flex justify-between">
			//             <span className="font-light">new PickUp entries:</span>
			//             <span className="font-bold">
			//                 {Number(result?.data?.total ?? 0).toLocaleString()}
			//             </span>
			//         </div>
			//     </>
			// ),
			errorMessage: importF3PickUpErrorMessage,
		});

		uploaderPickUpRef.current?.reset();
	};

	useEffect(() => {
		if (importF3PickUpErrorMessage) {
			setSelectedPickUpFile(null);
		}
	}, [importF3PickUpErrorMessage]);

	return (
		<ImportPageLayout pageName="F3 PickUp">
			<div className="grid grid-cols-1 w-full gap-4">
				<div className="card flex-1 bg-base-100 border border-base-content/20">
					<div className="card-body">
						<h2 className="card-title">Upload Daily {pickUpLabel}</h2>
						<p>Upload latest data for F3 PickUps.</p>
						{importF3PickUpData?.data?.ignored_unknown_package.length > 0 && (
							<div className="bg-warning text-warning-content p-2 rounded-lg">
								<MdWarning className="inline w-4 h-4 mr-2" />
								Some rows were not imported because their package is unknown
								(see the ignored rows list below). Add the unknown package first
								on the{" "}
								<button
									type="button"
									className="font-semibold inline underline cursor-pointer items-center"
									onClick={() =>
										window.open(
											route("f3.raw.package.index"),
											"_blank",
											"noopener,noreferrer",
										)
									}
								>
									<FaLink className="inline w-4 h-4 mr-1" /> F3 Raw Packages
									page
								</button>
								. After that, you can try importing the file again.
							</div>
						)}
						<ImportLabel
							data={importTraceData?.f3_pickup}
							loading={isImportTraceLoading}
						/>
						<div className="card-actions justify-end">
							<Modal
								ref={manualPickUpImportRef}
								id="pickUpImportModal"
								title={`Confirm upload ${pickUpLabel} import`}
								onClose={() => manualPickUpImportRef.current?.close()}
								className="max-w-lg"
							>
								<p className="py-4">
									Are you sure? This will start the {pickUpLabel} import.
									Current import progress (if any) will block this action.
								</p>

								<div className="flex justify-end gap-2">
									<button
										className="btn btn-soft btn-warning"
										onClick={async () => {
											manualPickUpImportRef.current?.close();
											handleManualPickUpImport();
										}}
										disabled={isImportF3PickUpLoading}
									>
										{isImportF3PickUpLoading && (
											<span className="loading loading-spinner"></span>
										)}
										Proceed
									</button>

									<button
										className="btn"
										onClick={() => manualPickUpImportRef.current?.close()}
										disabled={isImportF3PickUpLoading}
									>
										Cancel
									</button>
								</div>
							</Modal>
						</div>
						<FileUploader
							ref={uploaderPickUpRef}
							legend="Pick an Excel file"
							onFileValid={(file) => {
								setSelectedPickUpFile(file);
							}}
							downloadClick={handleDownloadPickUpTemplate}
							isDownloadLoading={isDownloadLoading}
						/>
						<button
							className="btn btn-primary w-54"
							onClick={() => manualPickUpImportRef.current?.open()}
							disabled={isImportF3PickUpLoading || !selectedPickUpFile}
						>
							Upload {pickUpLabel}
						</button>
					</div>

					{importF3PickUpData?.data?.ignored_unknown_package.length > 0 && (
						<Collapse
							title={`Unknown Package: ignored Rows (Not imported). Click to see details.`}
							className={"w-full border border-warning text-base-content"}
							contentClassName={"overflow-x-auto text-base-content"}
						>
							<div className="mb-2 ">
								showing{" "}
								{importF3PickUpData?.data?.ignored_unknown_package.length} out
								of {importF3PickUpData?.data?.ignored_unknown_package_count}
							</div>
							<div className="overflow-x-auto w-full max-h-96">
								<DataTable
									columns={Object.keys(
										importF3PickUpData?.data?.ignored_unknown_package[0],
									)}
									rows={importF3PickUpData?.data?.ignored_unknown_package}
									className={"w-full"}
								/>
							</div>
						</Collapse>
					)}

					{importF3PickUpErrorMessage && importF3PickUpErrorData?.data && (
						<Collapse
							title={`${importF3PickUpErrorMessage}. Click to see details.`}
							className={"border-red-500 hover:border-red-500 bg-error/10"}
						>
							{importF3PickUpErrorData?.data?.missing_headers.length > 0 && (
								<div className="mt-2">Missing headers: </div>
							)}
							<ul className="list">
								{importF3PickUpErrorData?.data?.missing_headers.map(
									(missing) => (
										<li className="list-row h-8 leading-none" key={missing}>
											{missing}
										</li>
									),
								)}
							</ul>
							{importF3PickUpErrorData?.data?.unknown_headers.length > 0 && (
								<div className="mt-2">Unknown (ignored): </div>
							)}
							<ul className="list">
								{importF3PickUpErrorData?.data?.unknown_headers.map(
									(unknown) => (
										<li className="list-row h-8 leading-none" key={unknown}>
											{unknown}
										</li>
									),
								)}
							</ul>
						</Collapse>
					)}

					<Collapse title={`${pickUpLabel} Excel Headers Required`}>
						<div className="text-secondary">
							space and whitespace are the same. Case insensitive
						</div>
						<ul className="list">
							{F3_PICKUP_HEADERS.map((header) => (
								<li className="list-row h-8 leading-none" key={header}>
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

export default F3PickUpImportPage;
