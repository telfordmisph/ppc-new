import clsx from "clsx";
import { useImperativeHandle, useState, useRef, forwardRef } from "react";
import { MdFileDownload } from "react-icons/md";

const FileUploader = forwardRef(
	(
		{
			legend,
			onFileValid,
			acceptedTypes = ".xlsx,.csv",
			downloadClick = null,
			isDownloadLoading = false,
		},
		ref,
	) => {
		// allow CSV by default
		const [error, setError] = useState("");
		const fileInputRef = useRef(null);

		useImperativeHandle(ref, () => ({
			reset: () => {
				setError("");
				onFileValid(null);
				if (fileInputRef.current) {
					fileInputRef.current.value = null;
				}
			},
		}));

		const handleFileChange = (e) => {
			const selectedFile = e.target.files[0];
			if (!selectedFile) {
				setError("No file selected.");
				onFileValid(null);
				return;
			}

			// split the acceptedTypes string into an array and trim whitespace
			const allowedExtensions = acceptedTypes
				.split(",")
				.map((ext) => ext.trim().toLowerCase());
			const fileExtension = selectedFile.name
				.slice(selectedFile.name.lastIndexOf("."))
				.toLowerCase();

			if (!allowedExtensions.includes(fileExtension)) {
				setError(`Only ${allowedExtensions.join(", ")} files are allowed.`);
				onFileValid(null);
				return;
			}

			setError("");
			onFileValid(selectedFile);
		};

		return (
			<fieldset className="fieldset">
				<legend className="fieldset-legend">{legend}</legend>
				<div className="flex gap-2">
					<input
						ref={fileInputRef}
						type="file"
						accept={acceptedTypes}
						className="file-input"
						onChange={handleFileChange}
					/>
					<button
						type="button"
						className={clsx("btn btn-accent", {
							hidden: !downloadClick,
						})}
						onClick={downloadClick}
						disabled={isDownloadLoading}
					>
						<MdFileDownload
							className={clsx("w-5 h-5 mr-2", {
								"animate-bounce": isDownloadLoading,
							})}
						/>
						<span
							className={clsx({
								"skeleton skeleton-text": isDownloadLoading,
							})}
						>
							template
						</span>
					</button>
				</div>
				{error && <label className="label text-error">{error}</label>}
			</fieldset>
		);
	},
);

export default FileUploader;
