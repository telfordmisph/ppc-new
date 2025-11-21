import { useState } from "react";

export default function ExcelUploader({
    legend,
    onFileValid,
    acceptedTypes = ".xlsx",
}) {
    const [error, setError] = useState("");

    const handleFileChange = async (e) => {
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
                type="file"
                accept={acceptedTypes}
                className="file-input"
                onChange={handleFileChange}
            />
            {error && <label className="label text-error">{error}</label>}
        </fieldset>
    );
}
