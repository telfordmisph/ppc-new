import { useState, forwardRef, useImperativeHandle, useRef } from "react";

const ExcelUploader = forwardRef(
    ({ legend, onFileValid, acceptedTypes = ".xlsx" }, ref) => {
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

            const allowedExtensions = [".xlsx"];
            const fileExtension = selectedFile.name.slice(
                selectedFile.name.lastIndexOf(".")
            );

            if (!allowedExtensions.includes(fileExtension.toLowerCase())) {
                setError("Only .xlsx files are allowed.");
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

export default ExcelUploader;
