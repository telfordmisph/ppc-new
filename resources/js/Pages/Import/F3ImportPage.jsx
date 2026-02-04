import { useEffect, useMemo, useRef, useState } from "react";
import { FaExternalLinkAlt } from "react-icons/fa";
import { MdWarning } from "react-icons/md";
import Collapse from "@/Components/Collapse";
import FileUploader from "@/Components/FileUploader";
import Modal from "@/Components/Modal";
import DataTable from "@/Components/Table";
import { F3_OUTS_HEADERS } from "@/Constants/ExcelHeaders";
import { useMutation } from "@/Hooks/useMutation";
import { useImportTraceStore } from "@/Store/importTraceStore";
import { runAsyncToast } from "@/Utils/runAsyncToast";
import ImportLabel from "../../Components/lastImportLabel";
import ImportPageLayout from "../../Layouts/ImportPageLayout";

const F3ImportPage = () => {
	const {
		data: importTraceData,
		isLoading: isImportTraceLoading,
		fetchAllImports,
	} = useImportTraceStore();

	const uploaderF3Ref = useRef(null);
	const manualF3ImportRef = useRef(null);
	const [selectedF3File, setSelectedF3File] = useState(null);

	const f3Label = "F3 WIP & OUTS";

	const {
		isLoading: isImportF3Loading,
		errorMessage: importF3ErrorMessage,
		errorData: importF3ErrorData,
		mutate: importF3,
		data: importF3Data,
	} = useMutation();

	const handleManualF3Import = () => {
		if (!selectedF3File) {
			return;
		}

		const formData = new FormData();
		formData.append("file", selectedF3File);
		runAsyncToast({
			action: async () => {
				const result = await importF3(route("import.importF3"), {
					body: formData,
					isContentTypeInclude: false,
					isFormData: true,
				});

				await fetchAllImports();

				return result;
			},
			loadingMessage: "Importing F3 data...",
			renderSuccess: (result) => (
				<>
					<div className="mb-2 font-bold">
						<span>F3s: </span>{" "}
						{result?.data?.ignored_unknown_package?.length > 0 &&
							"Partially imported!"}
						{result?.data?.ignored_unknown_package?.length === 0 &&
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
							<span className="font-light">new F3 entries:</span>
							<span className="font-bold">
								{Number(result?.data?.total ?? 0).toLocaleString()}
							</span>
						</div>
					</div>
				</>
			),
			errorMessage: importF3ErrorMessage,
		});

		uploaderF3Ref.current?.reset();
	};

	useEffect(() => {
		if (importF3ErrorMessage) {
			setSelectedF3File(null);
		}
	}, [importF3ErrorMessage]);

	const uniquePackages = useMemo(() => {
		const map = new Map();
		for (const item of importF3Data?.data?.ignored_unknown_package ?? []) {
			if (!map.has(item.package)) {
				map.set(item.package, {
					...item,
					PACKAGE: item.package,
				});
			}
		}
		return [...map.values()];
	}, [importF3Data?.data?.ignored_unknown_package]);

	return (
		<ImportPageLayout pageName="F3 Wip & Outs">
			<div className="grid grid-cols-1 w-full gap-4">
				<div className="card flex-1 bg-base-100 border border-base-content/20">
					<div className="card-body">
						<h2 className="card-title">Upload Daily {f3Label}</h2>
						<p>Upload latest data for F3 WIPs and OUTs.</p>
						{importF3Data?.data?.ignored_unknown_package?.length > 0 && (
							<div className="bg-warning text-warning-content p-2 rounded-lg">
								<MdWarning className="inline w-4 h-4 mr-2" />
								Some rows were not imported because their package is unknown
								(see the ignored rows list below).
								<a
									href={route("f3.raw.package.createMany", {
										raw_packages: uniquePackages ?? [],
									})}
									target="_blank"
									rel="noopener noreferrer"
									className="mx-1 btn btn-outline btn-primary"
								>
									<div className="inline-grid *:[grid-area:1/1]">
										<div className="status status-secondary animate-ping"></div>
										<div className="status status-secondary"></div>
									</div>
									Add the unknown packages now
									<FaExternalLinkAlt className="inline w-4 h-4 ml-1" />
								</a>
								then you can try importing the file again.
							</div>
						)}
						<ImportLabel
							data={importTraceData?.f3}
							loading={isImportTraceLoading}
						/>
						<div className="card-actions justify-end">
							<Modal
								ref={manualF3ImportRef}
								id="f3ImportModal"
								title={`Confirm upload ${f3Label} import`}
								onClose={() => manualF3ImportRef.current?.close()}
								className="max-w-lg"
							>
								<p className="py-4">
									Are you sure? This will start the {f3Label} import. Current
									import progress (if any) will block this action.
								</p>

								<div className="flex justify-end gap-2">
									<button
										type="button"
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
										type="button"
										className="btn"
										onClick={() => manualF3ImportRef.current?.close()}
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
							type="button"
							className="btn btn-primary w-54"
							onClick={() => manualF3ImportRef.current?.open()}
							disabled={isImportF3Loading || !selectedF3File}
						>
							Upload {f3Label}
						</button>
					</div>

					{importF3Data?.data?.ignored_unknown_package?.length > 0 && (
						<Collapse
							title={`Unknown Package: ignored Rows (Not imported). Click to see details.`}
							className={"w-full border border-warning text-base-content"}
							contentClassName={"overflow-x-auto text-base-content"}
						>
							<div className="mb-2 ">
								showing {importF3Data?.data?.ignored_unknown_package?.length}{" "}
								out of {importF3Data?.data?.ignored_unknown_package_count}
							</div>
							<div className="overflow-x-auto w-full max-h-96">
								<DataTable
									columns={Object.keys(
										importF3Data?.data?.ignored_unknown_package[0],
									)}
									rows={importF3Data?.data?.ignored_unknown_package}
									className={"w-full"}
								/>
							</div>
						</Collapse>
					)}

					{importF3ErrorMessage &&
						(importF3ErrorData?.data?.missing_headers ||
							importF3ErrorData?.data?.unknown_headers) && (
							<Collapse
								title={`${importF3ErrorMessage}. Click to see details.`}
								className={"border-red-500 hover:border-red-500 bg-error/10"}
							>
								{importF3ErrorData?.data?.missing_headers?.length > 0 && (
									<div className="mt-2">Missing headers: </div>
								)}
								<ul className="list">
									{importF3ErrorData?.data?.missing_headers?.map((missing) => (
										<li className="list-row h-8 leading-none" key={missing}>
											{missing}
										</li>
									))}
								</ul>
								{importF3ErrorData?.data?.unknown_headers?.length > 0 && (
									<div className="mt-2">Unknown (ignored): </div>
								)}
								<ul className="list">
									{importF3ErrorData?.data?.unknown_headers?.map((unknown) => (
										<li className="list-row h-8 leading-none" key={unknown}>
											{unknown}
										</li>
									))}
								</ul>
							</Collapse>
						)}

					<Collapse title={`${f3Label} Excel Headers Required`}>
						<div className="text-secondary">
							space and whitespace are the same. Case insensitive
						</div>
						<ul className="list">
							{F3_OUTS_HEADERS.map((header) => (
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

export default F3ImportPage;
