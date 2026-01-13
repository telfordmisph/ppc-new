import { useImperativeHandle, useState, useRef, forwardRef } from "react";

const FileUploader = forwardRef(
    ({ legend, onFileValid, acceptedTypes = ".xlsx,.csv" }, ref) => {
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
                setError(
                    `Only ${allowedExtensions.join(", ")} files are allowed.`
                );
                onFileValid(null);
                return;
            }

            setError("");
            onFileValid(selectedFile);
        };

        return (
            <fieldset className="fieldset">
                <legend className="fieldset-legend">{legend}</legend>
                <input
                    ref={fileInputRef}
                    type="file"
                    accept={acceptedTypes}
                    className="file-input"
                    onChange={handleFileChange}
                />
                {error && <label className="label text-error">{error}</label>}
            </fieldset>
        );
    }
);

export default FileUploader;
