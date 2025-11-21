import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import formatPastDateTimeLabel from "@/Utils/formatPastDateTimeLabel";
import { MdUpdate } from "react-icons/md";

const ImportLabel = ({ data, loading }) => {
    if (loading) return null;
    if (!data) return null;

    return (
        <div className="flex gap-1 items-center opacity-80">
            <div>
                <MdUpdate />
            </div>

            <div>updated {formatPastDateTimeLabel(data.latest_import)}</div>
            <div className="text-secondary">
                ({formatFriendlyDate(data.latest_import, true)})
            </div>
            <div>with </div>
            <div>{formatAbbreviateNumber(data.entries)} entries</div>
            {data.imported_by && (
                <div>
                    by <span className="text-primary">{data.imported_by}</span>
                </div>
            )}
        </div>
    );
};

export default ImportLabel;
