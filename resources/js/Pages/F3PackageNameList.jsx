import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { FaEdit, FaTrash } from "react-icons/fa";
import { FaPlus } from "react-icons/fa6";
import { TbAlertCircle } from "react-icons/tb";
import MaxItemDropdown from "@/Components/MaxItemDropdown";
import Modal from "@/Components/Modal";
import Pagination from "@/Components/Pagination";
import Pagination from "@/Components/Pagination";
import SearchableDropdown from "@/Components/SearchableDropdown";
import SearchInput from "@/Components/SearchInput";
import Tabs from "@/Components/Tabs";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { useF3PackagesStore } from "@/Store/f3PackageListStore";

const F3PackageNameList = () => {
	const toast = useToast();

	const {
		f3PackageNames: serverPackageGroup,
		search: serverSearch,
		perPage: serverPerPage,
		totalEntries,
	} = usePage().props;

	const start = serverPackageGroup.from;
	const end = serverPackageGroup.to;
	const filteredTotal = serverPackageGroup.total;
	const overallTotal = totalEntries ?? filteredTotal;
	const deleteModalRef = useRef(null);
	const [searchInput, setSearchInput] = useState(serverSearch || "");
	const [maxItem, setMaxItem] = useState(serverPerPage || 50);
	const [selectedF3Package, setSelectedF3Package] = useState(null);
	const [currentPage, setCurrentPage] = useState(
		serverPackageGroup.current_page || 1,
	);
	const { removePackage } = useF3PackagesStore((state) => state);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	useEffect(() => {
		if (serverSearch === searchInput) return;

		const timer = setTimeout(() => {
			router.reload({
				data: { search: searchInput, perPage: maxItem, page: 1 },
			});
			setCurrentPage(1);
		}, 700);

		return () => clearTimeout(timer);
	}, [searchInput]);

	const goToPage = (page) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page },
		});
		setCurrentPage(page);
	};

	const changeMaxItemPerPage = (maxItem) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page: 1 },
		});
		setMaxItem(maxItem);
	};

	const refresh = () => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, currentPage },
		});
	};

	const handleDelete = async () => {
		try {
			await mutate(
				route("api.f3.package.names.delete", {
					id: selectedF3Package.id,
				}),
				{
					method: "DELETE",
				},
			);

			refresh();
			removePackage(selectedF3Package.id);
			deleteModalRef.current.close();
			toast.success("F3 Package deleted successfully!");
		} catch (error) {
			toast.error(error?.message);
			console.error(error);
		}
	};

	return (
		<>
			<div className="flex items-center justify-between text-center">
				<Tabs
					options={["F3 Raw Packages", "F3 Packages"]}
					selectedFactory={"F3 Packages"}
					handleFactoryChange={() =>
						router.visit(route("f3.raw.package.index"))
					}
				/>

				<Link href={route("f3.package.create")} className="btn btn-primary">
					<FaPlus /> Add F3 Package
				</Link>
			</div>

			<div className="flex justify-between py-4">
				<MaxItemDropdown
					maxItem={maxItem}
					changeMaxItemPerPage={changeMaxItemPerPage}
				/>

				<SearchInput
					searchInput={searchInput}
					onSearchChange={setSearchInput}
				/>
			</div>

			<table className="table w-full table-auto table-xs">
				<thead>
					<tr>
						<th>ID</th>
						<th>Package Name</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					{serverPackageGroup.data.map((packageGroup) => (
						<tr key={packageGroup.id}>
							<td>{packageGroup.id}</td>
							<td>{packageGroup.package_name}</td>
							<td className="flex flex-col lg:flex-row">
								<a
									href={route("f3.package.edit", {
										id: packageGroup.id,
										// search: searchInput,
										// perPage: maxItem,
										// page: currentPage,
										selectedPackage: packageGroup,
									})}
									className="btn btn-ghost btn-sm btn-primary"
								>
									<FaEdit />
								</a>
								<button
									type="button"
									className="btn btn-ghost btn-sm text-error"
									onClick={() => {
										setSelectedF3Package(packageGroup);
										deleteModalRef.current.open();
									}}
								>
									<FaTrash />
								</button>
								<Modal
									ref={deleteModalRef}
									id="f3PackageNameDeleteModal"
									title="Are you sure?"
									onClose={() => deleteModalRef.current?.close()}
									className="max-w-lg"
								>
									<p className="px-2 pt-4">
										This action cannot be undone. Delete
										<span className="pl-1">
											"
											<span className="text-error">
												{selectedF3Package?.package_name}
											</span>
											"?
										</span>
										<div className="flex items-center text-error gap-2">
											<TbAlertCircle />
											Anything that uses this package in F3 raw packages will be
											deleted.
										</div>
									</p>

									<p
										className="p-2 border rounded-lg bg-error/10 text-error"
										style={{
											visibility: mutateErrorMessage ? "visible" : "hidden",
										}}
									>
										{mutateErrorMessage || "placeholder"}
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
					))
};
</tbody>
			</table>

			<Pagination
				links=
{
	serverPackageGroup.links;
}
currentPage = { currentPage };
goToPage = { goToPage };
filteredTotal = { filteredTotal };
overallTotal = { overallTotal };
start = { start };
end={end}
			/>
</>
	)
<Pagination
	links={serverPackageGroup.links}
	currentPage={currentPage}
	goToPage={goToPage}
	filteredTotal={filteredTotal}
	overallTotal={overallTotal}
	start={start}
	end={end}
/>;
</>
	)
}

export default F3PackageNameList;
