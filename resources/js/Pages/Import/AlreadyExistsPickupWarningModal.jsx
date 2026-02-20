import CancellableActionButton from "@/Components/CancellableActionButton";
import Modal from "@/Components/Modal";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import formatPastDateTimeLabel from "@/Utils/formatPastDateTimeLabel";
import { forwardRef } from "react";

const AlreadyExistsModal = forwardRef(
  (
    {
      title = "Some rows already exist",
      data = [],
      onRefetch,
      loading = false,
      abort = () => {},
      className = "max-w-4xl",
      cancelButtonText = "Cancel",
      actionButtonText = "I know, Import Anyway",
      actionLoadingMessage = "Importing",
    },
    ref
  ) => {
    return (
      <Modal
        ref={ref}
        id="alreadyExistsRowsModal"
        title={title}
        onClose={() => ref?.current?.close()}
        buttonText={"View Already Existing Rows"}
        buttonClass="btn btn-error w-60"
        className={className}
      >
        <p className="px-2 pt-4">
          The following rows have already been added to the Pickup database in
          the last 24 hours:
        </p>

        {data.length > 0 && (
          <div className="overflow-x-auto max-h-72 mt-4 border rounded">
            <table className="table-auto w-full text-left">
              <thead className="bg-base-100">
                <tr>
                  <th className="px-2 py-1">Part Name</th>
                  <th className="px-2 py-1">Lot ID</th>
                  <th className="px-2 py-1">Package</th>
                  <th className="px-2 py-1">Date Created</th>
                  <th className="px-2 py-1">Added By</th>
                </tr>
              </thead>
              <tbody>
                {data.map((row, index) => (
                  <tr
                    key={index}
                    className={index % 2 === 0 ? "bg-base-100" : "bg-base-200"}
                  >
                    <td className="px-2 py-1">{row.PARTNAME}</td>
                    <td className="px-2 py-1">{row.LOTID}</td>
                    <td className="px-2 py-1">{row.PACKAGE}</td>
                    <td className="px-2 py-1">{formatFriendlyDate(row.DATE_CREATED, true)} ({(formatPastDateTimeLabel(row.DATE_CREATED))})</td>
                    <td className="px-2 py-1">{row.addedBy?.FIRSTNAME || "N/A"} ({row?.addedBy?.EMPLOYID || "-"})</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <div className="flex justify-end gap-2 pt-4">
          <CancellableActionButton
            abort={abort || (() => {})}
            refetch={onRefetch}
            loading={loading}
            buttonText={actionButtonText}
            loadingMessage={actionLoadingMessage}
          />
          <button
            type="button"
            className="btn btn-error"
            onClick={() => ref?.current?.close()}
          >
            {cancelButtonText}
          </button>
        </div>
      </Modal>
    );
  }
);

export default AlreadyExistsModal;