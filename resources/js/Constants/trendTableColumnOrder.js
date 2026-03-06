const plPreferredOrder = [
	"label",
	// PL1
	"f1_pl1_total_wip",
	"f2_pl1_total_wip",
	"f3_pl1_total_wip",
	"overall_pl1_total_wip",
	"f1_pl1_total_outs",
	"f2_pl1_total_outs",
	"f3_pl1_total_outs",
	"overall_pl1_total_outs",
	// PL6
	"f1_pl6_total_wip",
	"f2_pl6_total_wip",
	"f3_pl6_total_wip",
	"overall_pl6_total_wip",
	"f1_pl6_total_outs",
	"f2_pl6_total_outs",
	"f3_pl6_total_outs",
	"overall_pl6_total_outs",
];

const defaultTrend = [
	"label",
	"f1_total_wip",
	"f2_total_wip",
	"f3_total_wip",
	"overall_total_wip",
	"f1_total_lots",
	"f2_total_lots",
	"f3_total_lots",
	"overall_total_lots",
	"f1_total_outs",
	"f2_total_outs",
	"f3_total_outs",
	"overall_total_outs",
	"f1_capacity",
	"f2_capacity",
	"f3_capacity",
	"overall_capacity",
	"f1_utilization",
	"f2_utilization",
	"f3_utilization",
	"overall_utilization",
];

const defaultColumnGroups = [
	{ key: "wip", label: "WIP", match: "_total_wip" },
	{ key: "wip_lots", label: "WIP Lots", match: "_wip_total_lots" },
	{ key: "lots", label: "Lots", match: "_total_lots" },
	{ key: "outs", label: "Outs", match: "_total_outs" },
	{ key: "out_lots", label: "Out Lots", match: "_out_total_lots" },
	{ key: "capacity", label: "Capacity", match: "_capacity" },
	{ key: "utilization", label: "Utilization", match: "_utilization" },
];

const plColumnGroups = [
	{ key: "pl1_wip", label: "PL1 WIP", match: "_pl1_total_wip" },
	{ key: "pl1_wip_lots", label: "PL1 WIP Lots", match: "_pl1_wip_total_lots" },
	{ key: "pl1_outs", label: "PL1 Outs", match: "_pl1_total_outs" },
	{ key: "pl1_out_lots", label: "PL1 Out Lots", match: "_pl1_out_total_lots" },
	{ key: "pl1_lots", label: "PL1 Lots", match: "_pl1_total_lots" },
	{ key: "pl6_wip", label: "PL6 WIP", match: "_pl6_total_wip" },
	{ key: "pl6_outs", label: "PL6 Outs", match: "_pl6_total_outs" },
];

export { defaultColumnGroups, defaultTrend, plColumnGroups, plPreferredOrder };

