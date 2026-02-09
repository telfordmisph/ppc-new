import Tabs from "@/Components/Tabs";
import { router } from "@inertiajs/react";
import { useState } from "react";

const bodySizePages = {
	"Body Sizes' Capacity": route("package.body_size.capacity.index"),
	"Packages' Body Sizes": route("package.body_size.capacity.body-sizes"),
	Machines: route("package.body_size.capacity.machines"),
};

function PackageBodySizeCapacityMachineList() {
	const [selectedPage, setSelectedPage] = useState("Machines");

	const handleSelectPage = (name) => {
		setSelectedPage(name);

		router.visit(bodySizePages[name]);
	};

	return (
		<div className="relative overflow-auto">
			<Tabs
				options={Object.keys(bodySizePages)}
				selectedFactory={selectedPage}
				handleFactoryChange={handleSelectPage}
				tabClassName={"mb-2"}
			/>
		</div>
	);
}

export default PackageBodySizeCapacityMachineList;
