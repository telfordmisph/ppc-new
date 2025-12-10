import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import formatPastDateTimeLabel from "@/Utils/formatPastDateTimeLabel";
import { MdUpdate } from "react-icons/md";

const ImportLabel = ({ data, loading }) => {
    if (loading) return null;
    if (!data) return null;

    return (
        <div className="items-center opacity-80">
            <span className="inline-flex items-center">
                <MdUpdate />
                <span className="ml-2">
                    updated {formatPastDateTimeLabel(data.latest_import)}
                </span>
            </span>
            <span className="text-secondary px-1">
                ({formatFriendlyDate(data.latest_import, true)})
            </span>
            <span>with </span>
            <span>{formatAbbreviateNumber(data.entries)} entries</span>
            {data.imported_by && (
                <span className="px-1">
                    by employee{" "}
                    <span className="text-primary">{data.imported_by}</span>
                </span>
            )}
        </div>
    );
};

export default ImportLabel;
