import Modal from "@/Components/Modal";
import { forwardRef } from "react";
import CancellableActionButton from "./CancellableActionButton";

const DeleteModal = forwardRef(
	(
		{
			id,
			title = "Are you sure?",
			message = "This action cannot be undone. Delete selected rows?",
			errorMessage,
			isLoading = false,
			onDelete,
			onCancel,
			onClose,
		},
		ref,
	) => {
		return (
			<Modal
				ref={ref}
				id={id}
				title={title}
				onClose={onClose}
				className="max-w-lg"
			>
				<div className="px-2 pt-4">{message}</div>

				<p
					className="p-2 border rounded-lg bg-error/10 text-error mt-4"
					style={{ visibility: errorMessage ? "visible" : "hidden" }}
				>
					{errorMessage || "placeholder"}
				</p>

				<div className="flex justify-end gap-2 pt-4">
					<CancellableActionButton
						abort={onCancel}
						refetch={onDelete}
						loading={isLoading}
						buttonText={"Confirm Delete"}
						loadingMessage="Deleting"
					/>

					<button className="btn btn-outline" onClick={onClose}>
						Cancel
					</button>
				</div>
			</Modal>
		);
	},
);

DeleteModal.displayName = "DeleteModal";

export default DeleteModal;
