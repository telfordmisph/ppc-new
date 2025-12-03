<?php

namespace App\Constants;

class WipConstants
{
  // ALL stations
  // GTARCH_T	5000140
  // GTTRES_T	3850478
  // GTREEL_T	334528
  // GTTFVI_T	24971
  // GTBKIPBE_T	24456
  // GTTRANS_T	24023
  // GTIQA_T	14205
  // GTTBOX_T	10941
  // GTTOQA_T	9205
  // GTREEL	7272
  // GTBKLDBE_T	6262
  // GTTOQA	5995
  // GTSUBCON	5576
  // GTBRAND_T	5504
  // GTLPI_T	3319
  // GTTBOX	2868
  // GTTFVI	2855
  // GTBKULBE_T	2279
  // GTBRAND	1995
  // GTTSORT_T	1983
  // GTGOUT	1564
  // GTTRANS_QA	1351
  // GTTLLI_T	1233
  // GTRANS-BOX	710
  // GTRANS_BOX	349
  // GTTRANS_B3	340
  // GTTERASE_T	305
  // GTTBINLOC	302
  // GTBATCH_T	270
  // GTTRANS_BE	233
  // GTFORM_T	140
  // GTTERASE	56
  // GTBKLDBE	55
  // GTTPACK	36
  // GTCARIER_T	17
  // GTBULKINSP	1
  // PITOQA1	20824
  // PI-GTTRANS	1802
  // PITLABEL1	1541
  // PITOQA	1534
  // PITFVI1	1406
  // PITBOX1	1383
  // PIGOUT1	270
  // PITBOX_T	128
  // PIPREMARK1	6
  // PITLABEL	2
  // CVDTRAN_GT	10082
  // TNR	495
  // Q-PITRANS1	334
  // TRAY	30
  // TUBE	19
  // 624	4
  // 2500	2
  // <null> 2487
  // GT general trias
  // _T telford
  // _B3 special unknown location
  public const TSSOP_240_MILS = "TSSOP (240 MILS)";
  public const SPECIAL_FILTER_VALUE = [self::TSSOP_240_MILS];
  public const F1F2_OUT_PACKAGE_VALUES = ["SOIC_N", "SOIC_N_EP", "QSOP", "150mils", "RN", "150 mils"];
  // continue this service filter package OCP
  public const DAYS_UNTIL_RECLASSIFIED_AS_NEW = 30;
  public const FACTORIES = ['F1', 'F2', 'F3'];
  public const PRODUCTION_LINES = ['PL1', 'PL6'];
  public const DISTINCT_PACKAGES = [
    "BGA",
    "BGA_CAV",
    "BGA_ED",
    "BL_TIP",
    "BUMPED_CHIP",
    "CBGA",
    "CERDIP",
    "CERPACK",
    "CERPAK",
    "CHIP",
    "CHIPS",
    "CLCC",
    "CQFP",
    "CSP_BGA",
    "DDPAK",
    "DFN",
    "EMGA",
    "FC2QFN",
    "FCCSP",
    "FLATPACK",
    "FLATPAK",
    "GQFN",
    "JLCC",
    "LCC",
    "LCC_HS",
    "LCC_V",
    "LDCC",
    "LFCSP",
    "LFCSP_CAV",
    "LFCSP_RT",
    "LFCSP_SS",
    "LGA",
    "LGA_CAV",
    "LQFN",
    "LQFN_EP",
    "LQFP",
    "LQFP_ED",
    "LQFP_EP",
    "MCML",
    "MINI_SO",
    "MINI_SO_EP",
    "MQFP",
    "MSML",
    "PCA",
    "PDIP",
    "PLCC",
    "PSOP_3",
    "QFN",
    "QFP",
    "QSOP",
    "QSOP_EP",
    "SBDIP",
    "SBRAZE",
    "SC70",
    "SOF",
    "SOF_MP",
    "SOIC_CAV",
    "SOIC_IC",
    "SOIC_N",
    "SOIC_N_EP",
    "SOIC_W",
    "SOIC_W_FP",
    "SOT_23",
    "SOT_23_3",
    "SOT_89",
    "SOT-223",
    "SOT-23",
    "SOT223",
    "SSOP",
    "SSOP-W",
    "TLA-QFN",
    "TO",
    "TO-220",
    "TO-39",
    "TO-46",
    "TO-5",
    "TO-92",
    "TO220",
    "TO39",
    "TO5",
    "TO92",
    "TQFP",
    "TQFP_EP",
    "TSOT",
    "TSSOP",
    "TSSOP_4.4",
    "TSSOP_4.4_EP",
    "TSSOP_6.1",
    "TSSOP-W",
    "UTQFN",
    "WLBGA",
    "WLCSP"
  ];
  public const TODAY_WIP_INCLUDED_STATIONS = [
    'GTTRES_T',
    'GTREEL',
    'CVDTRAN_GT',
    'GTARCH_T',
    'GTTRANS_BE',
    'PITBOX_T',
    'PITBOX1',
    'PITFVI1',
    'PITLABEL1',
    'PITOQA',
    'PITOQA1',
    'Q-PITRANS1'
  ];
  public const TODAY_WIP_EXCLUDED_STATIONS = [
    'GTSUBCON',
    'GTGOUT',
    'GTTBINLOC',
    ...self::BRAND_TRANSFER_B3,
    ...self::TRANSFER_QA,
    ...self::FINAL_QA_STATION
  ];
  public const PERIOD_GROUP_BY = [
    'daily' => ['day'],
    'weekly' => ['year', 'week', 'workweek'],
    'monthly' => ['year', 'month'],
    'quarterly' => ['year', 'quarter'],
    'yearly' => ['year'],
  ];

  public const SPECIAL_PACKAGE = [];
  public const FACTORY_AGGREGATES = [
    'F1F2' => [
      'wip' => [
        'quantity' => ['SUM(wip.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(wip.Qty)' => 'total_quantity',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'quantity' => ['SUM(out.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(out.Qty)' => 'total_quantity',
          'COUNT(out.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'out.Date_Loaded',
      ],
    ],
    'F1' => [
      'wip' => [
        'quantity' => ['SUM(wip.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(wip.Qty)' => 'total_quantity',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'quantity' => ['SUM(out.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(out.Qty)' => 'total_quantity',
          'COUNT(out.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'out.Date_Loaded',
      ],
    ],
    'F2' => [
      'wip' => [
        'quantity' => ['SUM(wip.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(wip.Qty)' => 'total_quantity',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'quantity' => ['SUM(out.Qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(out.Qty)' => 'total_quantity',
          'COUNT(out.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'out.Date_Loaded',
      ],
    ],
    'F3' => [
      'wip' => [
        'quantity' => ['SUM(f3_wip.qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(f3_wip.qty)' => 'total_quantity',
          'COUNT(f3_wip.lot_number)' => 'total_lots'
        ],
        'dateColumn' => 'f3_wip.date_received',
      ],
      'out' => [
        'quantity' => ['SUM(f3_out.qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(f3_out.qty)' => 'total_quantity',
          'COUNT(f3_out.lot_number)' => 'total_lots'
        ],
        'dateColumn' => 'f3_out.date_received',
      ],
    ],
    'All' => [
      'wip' => [
        'quantity' => ['SUM(wip.qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(wip.qty)' => 'total_quantity',
          'COUNT(wip.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.date_loaded',
      ],
      'out' => [
        'quantity' => ['SUM(out.qty)' => 'total_quantity'],
        'quantity-lot' => [
          'SUM(out.qty)' => 'total_quantity',
          'COUNT(out.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'out.date_loaded',
      ],
    ],
  ];

  public const F2_OUT_FOCUS_GROUP_INCLUSION = ['CV', 'LT', 'LTCL', 'LTI'];
  public const F1_OUT_FOCUS_GROUP_EXCLUSION = ['CV', 'CV1', 'LT', 'LTCL', 'LTI', 'DLT', 'WLT', 'SOF'];
  public const FINAL_QA_STATION = ['GTTBOX', 'GTTFVI', 'GTTOQA'];
  public const TRANSFER_QA = ['GTRANS_BOX', 'GTTRANS_QA'];
  public const EWAN_PROCESS = ['GTTRES_T', 'GTSUBCON', 'GTGOUT', 'GTARCH_T', 'GTTBINLOC'];
  public const LOT_ON_HOLD = ['HELD', 'RHLD', 'LWAITH'];
  public const PRE_BAKE = ['GTTRANS_T', 'GTIQA_T', 'GTLPI_T', 'GTBKLDBE_T'];
  public const BRAND_ERASE = ['GTBRAND_T', 'GTTERASE_T'];
  public const LPI_LLI_BRAND_ERASE_SORT = ['GTLPI_T', 'GTTLLI_T', 'GTTSORT_T', ...self::BRAND_ERASE];
  public const BOXING_QA = ['TBOXING', 'TBUYOFFQA', 'TOUTQA'];
  public const BAKE_REEL_TRANSFER = ['TBAKEL', 'TDREEL', 'TRANSFER'];
  public const BRAND_TRANSFER_B3 = ['GTBRAND', 'GTTRANS_B3'];
  public const REEL_TRANSFER_B3 = ['GTREEL', 'GTTRANS_B3'];
  public const F1_EXCLUDED_PLANT = "ADPI";
  public const REEL_EXCLUDED_STATIONS_F1_OVERALL = [
    ...self::BRAND_TRANSFER_B3,
    ...self::EWAN_PROCESS,
    ...self::FINAL_QA_STATION
  ];
  public const REEL_TRANSFER_EXCLUDED_STATIONS_F1 = [
    'GTBRAND',
    ...self::EWAN_PROCESS,
    ...self::TRANSFER_QA,
    ...self::FINAL_QA_STATION
  ];
  public const REEL_EXCLUDED_STATIONS_F1_PL = [
    ...self::BRAND_TRANSFER_B3,
    ...self::EWAN_PROCESS,
    ...self::TRANSFER_QA,
    ...self::FINAL_QA_STATION
  ];
  public const EXCLUDED_STATIONS_F2 = [
    ...self::EWAN_PROCESS,
    'Q-PITRANS1'
  ];
  public const SPECIAL_PART_NAMES = [
    'ADXL312WACPZ',
    'ADXL312WACPZ-RL',
    'ADXL312ACPZ-RL',
    'ADXL313WACPZ-RL',
    'ADXL313WACPZ-RL7',
    'ADXL180WCPZA-RL',
    'ADXL314WBCPZ-RL',
  ];

  public const IMPORT_F3_WIP_EXPECTED_HEADERS = [
    'running_ct' => [
      'running ct',
      'run ct',
      'running_count',
      'run_count',
      'RUNNING CT',
      'RUN CT'
    ],
    'date_received' => [
      'date received',
      'received date',
      'recv date',
      'date_recv',
      'DATE RECEIVED'
    ],
    'packing_list_srf' => [
      'packing list srf#',
      'packing list srf',
      'srf number',
      'srf#',
      'packing_list_srf',
      'SRF#'
    ],
    'po_number' => [
      'p.o number',
      'po number',
      'purchase order number',
      'po#',
      'PO NUMBER'
    ],
    'machine_number' => [
      'machine #',
      'machine number',
      'machine_no',
      'MACHINE NUMBER'
    ],
    'part_number' => [
      'part number',
      'part_no',
      'part#',
      'PART NUMBER'
    ],
    'package_code' => [
      'pkg code',
      'package code',
      'package_code',
      'PKG CODE'
    ],
    'package' => [
      'package',
      'pkg',
      'PACKAGE'
    ],
    'lot_number' => [
      'lot number',
      'lot_no',
      'LOT NUMBER',
      'lot id',
      'lot_id',
      'LOT ID'
    ],
    'process_req' => [
      'process req.',
      'process requirement',
      'process_req',
      'PROCESS REQ'
    ],
    'qty' => [
      'qty',
      'quantity',
      'total qty',
      'total quantity',
      'QUANTITY'
    ],
    'good' => [
      'good',
      'ok',
      'accepted',
      'GOOD'
    ],
    'rej' => [
      'rej',
      'rejected',
      'reject',
      'REJ'
    ],
    'res' => [
      'res',
      'reserved',
      'RES'
    ],
    'date_commit' => [
      'date commit',
      'commit date',
      'date_committed',
      'DATE COMMIT'
    ],
    'actual_date_time' => [
      'actual date/time',
      'actual datetime',
      'act date time',
      'ACTUAL DATE/TIME'
    ],
    'status' => [
      'status',
      'STATUS'
    ],
    'do_number' => [
      'd.o#',
      'do number',
      'delivery order #',
      'DO#'
    ],
    'remarks' => [
      'remarks',
      'note',
      'comments',
      'REMARKS'
    ],
    'doable' => [
      'doable',
      'can do',
      'DOABLE'
    ],
    'focus_group' => [
      'focus group',
      'group',
      'FOCUS GROUP'
    ],
    'gap_analysis' => [
      'gap analysis',
      'gap',
      'GAP ANALYSIS'
    ],
    'cycle_time' => [
      'cycle time',
      'ct',
      'CYCLE TIME'
    ]
  ];

  public const IMPORT_F3_OUT_EXPECTED_HEADERS = [
    'date_received' => [
      'date received',
      'received date',
      'recv date',
      'date_recv',
      'DATE RECEIVED',
      'Date Received'
    ],
    'packing_list_srf' => [
      'packing list srf#',
      'packing list srf',
      'srf number',
      'srf#',
      'packing_list_srf',
      'SRF#'
    ],
    'po_number' => [
      'p.o number',
      'po number',
      'purchase order number',
      'po#',
      'PO NUMBER'
    ],
    'machine_number' => [
      'machine #',
      'machine number',
      'machine_no',
      'MACHINE NUMBER'
    ],
    'part_number' => [
      'part number',
      'part_no',
      'part#',
      'PART NUMBER'
    ],
    'package_code' => [
      'pkg code',
      'package code',
      'package_code',
      'PKG CODE'
    ],
    'package' => [
      'package',
      'pkg',
      'PACKAGE'
    ],
    'lot_number' => [
      'lot number',
      'lot_no',
      'LOT NUMBER',
      'lot id',
      'lot_id',
      'LOT ID'
    ],
    'process_req' => [
      'process req.',
      'process RQMT',
      'process requirement',
      'process_req',
      'PROCESS REQ'
    ],
    'qty' => [
      'qty',
      'good qty',
      'quantity',
      'total qty',
      'total quantity',
      'QUANTITY'
    ],
    'good' => [
      'good',
      'ok',
      'accepted',
      'GOOD'
    ],
    'rej' => [
      'rej',
      'rejected',
      'reject',
      'REJ'
    ],
    'res' => [
      'res',
      'reserved',
      'RES'
    ],
    'date_commit' => [
      'date commit',
      'commit date',
      'date_committed',
      'DATE COMMIT'
    ],
    'actual_date_time' => [
      'actual date/time',
      'actual datetime',
      'act date time',
      'ACTUAL DATE/TIME',
      'actual date/time shipped',
      'actual date/time shipped'
    ],
    'status' => [
      'status',
      'STATUS'
    ],
    'do_number' => [
      'd.o#',
      'do number',
      'delivery order #',
      'DO#'
    ],
    'remarks' => [
      'remarks',
      'note',
      'comments',
      'REMARKS'
    ],
    'doable' => [
      'doable',
      'DOABLE'
    ],
    'gap_analysis' => [
      'gap analysis',
      'gap',
      'GAP ANALYSIS'
    ],
    'cycle_time' => [
      'cycle time',
      'ct',
      'CYCLE TIME'
    ]
  ];

  public const IMPORT_WIP_OUTS_EXPECTED_HEADERS = [
    'package' => [
      'package name',
      'pkg name',
      'pkg_name',
      'package',
      'pkg',
      'package_name',
      'PACKAGE NAME'
    ],
    'qty' => [
      'quantity',
      'qty',
      'total quantity',
      'total_qty',
      'qty_total',
      'QUANTITY'
    ],
    'part_name' => [
      'part name',
      'part',
      'part_name',
      'PART NAME'
    ],
    'lot_id' => [
      'lot_id',
      'lot id',
      'lot number',
      'lot_no',
      'lot_num',
      'LOT ID'
    ],
    'out_date' => [
      'out date',
      'output date',
      'date out',
      'out_date',
      'date_out',
      'OUT DATE'
    ],
    'residual' => [
      'residual',
      'residual qty',
      'RESIDUAL'
    ],
    'test_part' => [
      'test part',
      'test_part',
      'tested part',
      'TEST PART'
    ],
    'test_lot_id' => [
      'test lot id',
      'test_lot_id',
      'test lot',
      'lot test',
      'tested lot',
      'TEST LOT ID'
    ],
    'focus_group' => [
      'focus group',
      'focus_group',
      'group',
      'focus',
      'test group',
      'FOCUS GROUP'
    ],
    'process_site' => [
      'process site',
      'process_site',
      'site',
      'proc site',
      'manufacturing site',
      'PROCESS SITE'
    ],
    'test_site' => [
      'test site',
      'test_site',
      'site test',
      'testing site',
      'test location',
      'TEST SITE'
    ],
    'tray' => [
      'tray',
      'TRAY',
      'Tray',
    ],
    'bulk' => [
      'bulk',
      'BULK',
      'Bulk',
    ],
    'date_loaded' => [
      'date loaded',
      'loaded date',
      'date_loaded',
      'load date',
      'loading date',
      'DATE LOADED'
    ],
    'process_group' => [
      'process group',
      'process_group',
      'proc group',
      'process_grp',
      'PROCESS GROUP'
    ],
    'ramp_time' => [
      'ramp time',
      'ramp_time',
      'ramptime',
      'ramp duration',
      'ramp_time_sec',
      'RAMP TIME'
    ],
  ];
}
