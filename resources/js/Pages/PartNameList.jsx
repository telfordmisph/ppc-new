import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { FaEdit, FaTrash } from "react-icons/fa";
import { FaPlus } from "react-icons/fa6";
import MaxItemDropdown from "@/Components/MaxItemDropdown";
import Modal from "@/Components/Modal";
import Pagination from "@/Components/Pagination";
import SearchInput from "@/Components/SearchInput";

const PartNameList = () => {
	const toast = useToast();

	const {
		partNames: serverPartNames,
		search: serverSearch,
		perPage: serverPerPage,
		totalEntries,
	} = usePage().props;

	const start = serverPartNames.from;
	const end = serverPartNames.to;
	const filteredTotal = serverPartNames.total;
	const overallTotal = totalEntries ?? filteredTotal;
	const deleteModalRef = useRef(null);
	const [searchInput, setSearchInput] = useState(serverSearch || "");
	const [maxItem, setMaxItem] = useState(serverPerPage || 10);
	const [selectedPart, setSelectedPart] = useState(null);
	const [currentPage, setCurrentPage] = useState(
		serverPartNames.current_page || 1,
	);
	const partNameIndexRoute = route("partname.index");

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	useEffect(() => {
		const timer = setTimeout(() => {
			router.reload({
				data: { search: searchInput, perPage: maxItem, page: 1 },
				preserveState: true,
				preserveScroll: true,
			});
			setCurrentPage(1);
		}, 700);

		return () => clearTimeout(timer);
	}, [searchInput]);

	const goToPage = (page) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page },
			preserveState: true,
			preserveScroll: true,
		});
		setCurrentPage(page);
	};

	const changeMaxItemPerPage = (maxItem) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page: 1 },
			preserveState: true,
			preserveScroll: true,
		});
		setMaxItem(maxItem);
	};

	const refresh = () => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, currentPage },
			preserveState: true,
			preserveScroll: true,
		});
	};

	const handleDelete = async () => {
		try {
			await mutate(
				route("api.partname.delete", {
					id: selectedPart.ppc_partnamedb_id,
				}),
				{
					method: "DELETE",
				},
			);

			refresh();

			deleteModalRef.current.close();
			toast.success("Part deleted successfully!");
		} catch (error) {
			toast.error(mutateErrorMessage);
			console.error(error);
		}
	};
	deleteModalRef.current.close();
	toast.success("Part deleted successfully!");
};
catch (error)
{
	toast.error(mutateErrorMessage);
	console.error(error);
}
}

return (
		<div className="w-full px-4">
			<div className="flex items-center justify-between text-center">
				<h1 className="text-base font-bold">Part Names</h1>
				<Link href={route("partname.create")} className="btn btn-primary">
					<FaPlus /> Add PartName
				</Link>
			</div>

			<div className="flex justify-between py-4">
				<MaxItemDropdown
					maxItem={maxItem}
					changeMaxItemPerPage={changeMaxItemPerPage}
				/>
				<SearchInput
					initialSearchInput={searchInput}
					onSearchChange={setSearchInput}
				/>
			</div>

			<table className="table w-full table-auto table-xs">
				<thead>
					<tr>
						<th>ID</th>
						<th>Part Name</th>
						<th>Focus Group</th>
						<th>Factory</th>
						<th>PL</th>
						<th>Package Name</th>
						<th>Lead Count</th>
						<th>Body Size</th>
						<th>Package</th>
						<th>Added By</th>
						<th>Date Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					{serverPartNames.data.map((part) => (
						<tr key={part.ppc_partnamedb_id}>
							<td>{part.ppc_partnamedb_id}</td>
							<td>{part?.Partname || "-"}</td>
							<td>{part?.Focus_grp || "-"}</td>
							<td>{part?.Factory || "-"}</td>
							<td>{part?.PL || "-"}</td>
							<td>{part?.Packagename || "-"}</td>
							<td>{part?.Leadcount || "-"}</td>
							<td>{part?.Bodysize || "-"}</td>
							<td>{part?.Package || "-"}</td>
							<td>{part?.added_by || "-"}</td>
							<td>{formatISOTimestampToDate(part?.date_created)}</td>
							<td className="flex flex-col lg:flex-row">
								<Link
									href={route("partname.edit", {
										id: part.ppc_partnamedb_id,
										search: searchInput,
										perPage: maxItem,
										page: currentPage,
									})}
									className="btn btn-ghost btn-sm btn-primary"
								>
									<FaEdit />
								</Link>
								<button
									type="button"
									className="btn btn-ghost btn-sm text-error"
									onClick={() => {
										setSelectedPart(part);
										deleteModalRef.current.open();
									}}
								>
									<FaTrash />
								</button>

								<Modal
									ref={deleteModalRef}
									id="deletePartModal"
									title="Are you sure?"
									onClose={() => deleteModalRef.current?.close()}
									className="max-w-lg"
								>
									<p className="px-2 pt-4">
										This action cannot be undone. Delete{" "}
										<span className="pl-1">
											{selectedPart?.Partname || "this?"}
										</span>
									</p>

									<p
										className="p-2 border rounded-lg bg-error/10 text-error"
										style={{
											visibility: mutateErrorMessage ? "visible" : "hidden",
										}}
									>
										{mutateErrorMessage || "placeholder"}
									</p>

									<div className="flex justify-end gap-2 pt-4">
										<button
											type="button"
											className="btn btn-error"
											onClick={async () => {
												await handleDelete();
											}}
											disabled={isMutateLoading}
										>
											{isMutateLoading ? (
												<>
													<span className="loading loading-spinner"></span>{" "}
													Deleting
												</>
											) : (
												"Confirm Delete"
											)}
										</button>

										<button
											type="button"
											className="btn btn-outline"
											onClick={() => deleteModalRef.current?.close()}
										>
											Cancel
										</button>
									</div>
								</Modal>
							</td>
						</tr>
					))}
				</tbody>
			</table>

			<Pagination
				links={serverPartNames.links}
				currentPage={currentPage}
				goToPage={goToPage}
				filteredTotal={filteredTotal}
				overallTotal={overallTotal}
				start={start}
				end={end}
			/>
		</div>
	);
}

export default PartNameList;
