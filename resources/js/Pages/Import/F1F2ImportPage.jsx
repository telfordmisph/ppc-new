import { useRef, useState } from "react";
import { FaExclamationTriangle } from "react-icons/fa";
import { TbAlertCircle } from "react-icons/tb";
import FileUploader from "@/Components/FileUploader";
import Modal from "@/Components/Modal";
import { useMutation } from "@/Hooks/useMutation";
import { useImportTraceStore } from "@/Store/importTraceStore";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportLabel from "../../Components/lastImportLabel";
import ImportPageLayout from "../../Layouts/ImportPageLayout";

const F1F2ImportPage = () => {
	const {
		data: importTraceData,
		isLoading: isImportTraceLoading,
		fetchAllImports,
	} = useImportTraceStore();

	const [isManualWip, setIsManualWip] = useState(false);
	const [isManualOuts, setIsManualOuts] = useState(false);
	const [selectedWipFile, setSelectedWipFile] = useState(null);
	const [selectedOutFile, setSelectedOutFile] = useState(null);

	const uploaderWIPRef = useRef(null);
	const uploaderOUTRef = useRef(null);

	const ftprootWIPImportRef = useRef(null);
	const ftprootOUTImportRef = useRef(null);

	const manualWIPImportRef = useRef(null);
	const manualOUTImportRef = useRef(null);

	const {
		isLoading: isImportWipLoading,
		errorMessage: importWipErrorMessage,
		errorData: importWipErrorData,
		mutate: importWip,
	} = useMutation();

	const {
		isLoading: isImportOutsLoading,
		errorMessage: importOutsErrorMessage,
		errorData: importOutsErrorData,
		mutate: importOuts,
	} = useMutation();

	const renderWipImportSuccess = (result) => (
		<>
			<div className="mb-2 font-bold text-success">
				<span>WIPs: </span>
				{result?.message || "Successfully imported!"}
			</div>

			<div className="flex justify-between">
				<span className="font-light">new WIP entries:</span>
				<span className="font-bold">
					{Number(result?.total ?? 0).toLocaleString()}
				</span>
			</div>
		</>
	);

	const renderOutImportSuccess = (result) => (
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
	);

	const handleFTProotWIPImport = () => {
		runAsyncToast({
			action: () => importWip(route("import.ftpRootImportWIP")),
			loadingMessage: "Importing WIP data...",
			renderSuccess: renderWipImportSuccess,
			errorMessage: importWipErrorMessage,
		});
	};

	const handleFTProotOUTImport = () => {
		runAsyncToast({
			action: () => importOuts(route("import.ftpRootImportOUTS")),
			loadingMessage: "Importing OUTs data...",
			renderSuccess: renderOutImportSuccess,
			errorMessage: importOutsErrorMessage,
		});
	};

	const handleManualWIPImport = () => {
		if (!selectedWipFile) {
			return;
		}

		const formData = new FormData();
		formData.append("file", selectedWipFile);
		runAsyncToast({
			action: () =>
				importWip(route("import.manualImportWIP"), {
					body: formData,
					isContentTypeInclude: false,
					isFormData: true,
				}),
			loadingMessage: "Importing F1F2 WIP data...",
			renderSuccess: renderWipImportSuccess,
			errorMessage: importWipErrorMessage,
		});

		uploaderWIPRef.current?.reset();
	};

	const handleManualOUTImport = () => {
		if (!selectedOutFile) {
			return;
		}

		const formData = new FormData();
		formData.append("file", selectedOutFile);
		runAsyncToast({
			action: async () => {
				const result = await importOuts(route("import.manualImportOUTS"), {
					body: formData,
					isContentTypeInclude: false,
					isFormData: true,
				});

				await fetchAllImports();

				return result;
			},
			loadingMessage: "Importing F1F2 OUT data...",
			renderSuccess: renderOutImportSuccess,
			errorMessage: importOutsErrorMessage,
		});

		uploaderOUTRef.current?.reset();
	};

	return (
		<ImportPageLayout pageName="F1/F2 Wip & Outs">
			<div className="grid grid-cols-1 w-full gap-4">
				<div className="card border bg-base-100 border-base-content/20">
					<div className="card-body">
						<h2 className="card-title">Refresh Daily WIP Import</h2>
						<p>
							Get the latest data for F1/F2 from the daily WIP import. This must
							be done atleast once a day.
						</p>
						<ImportLabel
							data={importTraceData?.f1f2_wip}
							loading={isImportTraceLoading}
						/>
						<div className="mt-2 flex flex-col">
							<fieldset className="fieldset mb-2 bg-base-100 border-base-300 rounded-box w-64 border p-4">
								<legend className="fieldset-legend">Import options</legend>
								<label className="label">
									<input
										type="checkbox"
										className="toggle  toggle-primary"
										checked={isManualWip}
										onChange={(e) => {
											setIsManualWip(e.target.checked);
											setSelectedWipFile(null);
										}}
									/>
									{isManualWip
										? "Manual File Upload"
										: "Update through ftproot"}
								</label>
							</fieldset>
							<div className="border border-base-content/10 p-2 rounded-lg">
								{isManualWip ? (
									<div>
										<FileUploader
											ref={uploaderWIPRef}
											legend="Pick a WIP CSV file"
											acceptedTypes=".csv"
											onFileValid={(file) => {
												setSelectedWipFile(file);
											}}
										/>
										<button
											className="btn btn-primary w-54"
											onClick={() => manualWIPImportRef.current?.open()}
											disabled={isImportWipLoading || !selectedWipFile}
										>
											Upload Wip Data
										</button>
									</div>
								) : (
									<button
										className="btn btn-primary mb-1"
										onClick={() => ftprootWIPImportRef.current?.open()}
									>
										Refresh auto daily WIP import
									</button>
								)}
							</div>
						</div>
						{importWipErrorData?.data?.partialSuccess != null && (
							<div className="text-error border border-error p-4 rounded-lg flex items-center gap-2">
								<span>
									<TbAlertCircle />
								</span>
								<span>
									Import Interrupted. {importWipErrorData.data.partialSuccess}{" "}
									entries imported successfully. You might want to try again
								</span>
							</div>
						)}
					</div>
				</div>

				<div className="card flex-1 bg-base-100 border border-base-content/20">
					<div className="card-body">
						<h2 className="card-title">Refresh Daily Outs</h2>
						<p>
							Get the latest data for F1/F2 from the daily OUTS. This must be
							done atleast once a day.
						</p>
						<ImportLabel
							data={importTraceData?.f1f2_out}
							loading={isImportTraceLoading}
						/>
						<div className="flex flex-col mt-2 justify-end">
							<fieldset className="fieldset bg-base-100 mb-2 border-base-300 rounded-box w-64 border p-4">
								<legend className="fieldset-legend">Import options</legend>
								<label className="label">
									<input
										type="checkbox"
										className="toggle toggle-primary"
										checked={isManualOuts}
										onChange={(e) => {
											setIsManualOuts(e.target.checked);
											setSelectedOutFile(null);
										}}
									/>
									{isManualOuts
										? "Manual File Upload"
										: "Update through ftproot"}
								</label>
							</fieldset>
							<div className="border border-base-content/10 rounded-lg p-2">
								{isManualOuts ? (
									<div>
										<FileUploader
											ref={uploaderOUTRef}
											legend="Pick an OUT CSV file"
											acceptedTypes=".csv"
											onFileValid={(file) => {
												setSelectedOutFile(file);
											}}
										/>
										<button
											className="btn btn-primary w-54"
											onClick={() => manualOUTImportRef.current?.open()}
											disabled={isImportOutsLoading || !selectedOutFile}
										>
											Upload Out Data
										</button>
									</div>
								) : (
									<button
										className="btn btn-primary mb-1"
										onClick={() => ftprootOUTImportRef.current?.open()}
									>
										Refresh auto daily OUTs import
									</button>
								)}
							</div>
						</div>
						{importOutsErrorData?.data?.partialSuccess != null && (
							<div className="text-error border border-error p-4 rounded-lg flex items-center gap-2">
								<span>
									<TbAlertCircle />
								</span>
								<span>
									Import Interrupted. {importOutsErrorData.data.partialSuccess}{" "}
									entries imported successfully. You might want to try again
								</span>
							</div>
						)}
					</div>
				</div>
				<div>
					<Modal
						ref={ftprootOUTImportRef}
						id="f1f2OutImportModal"
						title="Refresh auto daily OUTS import"
						onClose={() => ftprootOUTImportRef.current?.close()}
						className="max-w-lg"
					>
						<p className="py-4">
							Are you sure? This will start the OUTS import. Current import
							progress (if any) will block this action.
						</p>

						<p className="text-warning-content border-warning border rounded-lg bg-warning px-2 py-1 flex items-center gap-2 mb-2">
							<FaExclamationTriangle /> Importing now will overwrite all
							existing entries for this date.
						</p>

						<div className="flex justify-end gap-2">
							<button
								className="btn btn-soft btn-warning"
								onClick={async () => {
									ftprootOUTImportRef.current?.close();
									handleFTProotOUTImport();
								}}
								disabled={isImportOutsLoading}
							>
								{isImportOutsLoading && (
									<span className="loading loading-spinner"></span>
								)}
								Proceed
							</button>

							<button
								className="btn"
								onClick={() => ftprootOUTImportRef.current?.close()}
								disabled={isImportOutsLoading}
							>
								Cancel
							</button>
						</div>
					</Modal>
					<Modal
						ref={ftprootWIPImportRef}
						id="f1f2WipImportModal"
						title="Refresh auto daily WIP import"
						onClose={() => ftprootWIPImportRef.current?.close()}
						className="max-w-lg"
					>
						<p className="py-4">
							Are you sure? This will start the WIP import. Current import
							progress (if any) will block this action.
						</p>

						<p className="text-warning-content border-warning border rounded-lg bg-warning px-2 py-1 flex items-center gap-2 mb-2">
							<FaExclamationTriangle /> Importing now will overwrite all
							existing entries for this date.
						</p>

						<div className="flex justify-end gap-2">
							<button
								className="btn btn-soft btn-warning"
								onClick={async () => {
									ftprootWIPImportRef.current?.close();
									handleFTProotWIPImport();
								}}
								disabled={isImportWipLoading}
							>
								{isImportWipLoading && (
									<span className="loading loading-spinner"></span>
								)}
								Proceed
							</button>

							<button
								className="btn"
								onClick={() => ftprootWIPImportRef.current?.close()}
								disabled={isImportWipLoading}
							>
								Cancel
							</button>
						</div>
					</Modal>
					<Modal
						ref={manualWIPImportRef}
						id="wipManualImportModal"
						title={`Confirm upload manual WIP import`}
						onClose={() => manualWIPImportRef.current?.close()}
						className="max-w-lg"
					>
						<p className="py-4">
							Are you sure? This will start the WIP import. Current import
							progress (if any) will block this action. This will overwrite all
							existing entries for this date.
						</p>

						<div className="flex justify-end gap-2">
							<button
								className="btn btn-soft btn-warning"
								onClick={async () => {
									manualWIPImportRef.current?.close();
									handleManualWIPImport();
								}}
								disabled={isImportWipLoading}
							>
								{isImportWipLoading && (
									<span className="loading loading-spinner"></span>
								)}
								Proceed
							</button>

							<button
								className="btn"
								onClick={() => manualWIPImportRef.current?.close()}
								disabled={isImportWipLoading}
							>
								Cancel
							</button>
						</div>
					</Modal>

					<Modal
						ref={manualOUTImportRef}
						id="outManualImportModal"
						title={`Confirm upload manual OUT import`}
						onClose={() => manualOUTImportRef.current?.close()}
						className="max-w-lg"
					>
						<p className="py-4">
							Are you sure? This will start the OUT import. Current import
							progress (if any) will block this action. This will overwrite all
							existing entries for this date.
						</p>

						<div className="flex justify-end gap-2">
							<button
								className="btn btn-soft btn-warning"
								onClick={async () => {
									manualOUTImportRef.current?.close();
									handleManualOUTImport();
								}}
								disabled={isImportOutsLoading}
							>
								{isImportOutsLoading && (
									<span className="loading loading-spinner"></span>
								)}
								Proceed
							</button>

							<button
								className="btn"
								onClick={() => manualOUTImportRef.current?.close()}
								disabled={isImportOutsLoading}
							>
								Cancel
							</button>
						</div>
					</Modal>
				</div>
			</div>
		</ImportPageLayout>
	);
};

export default F1F2ImportPage;
