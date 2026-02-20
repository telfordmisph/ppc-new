<?php

namespace App\Constants;

class WipConstants
{
  public const TODAY_WIP_CACHE_KEY = "today_wip";
  public const F3_OUT_STATUSES = [
    "IQA",
    "For Process",
    "In-process",
    "Hold",
    "FVI",
    "OQA",
    "Boxing",
    "OQA",
    "QA Buy-off",
  ];
  public const TSSOP_240_MILS = "TSSOP (240 MILS)";
  public const SPECIAL_FILTER_VALUE = [self::TSSOP_240_MILS];
  public const F1F2_150_MILS_OUT_PACKAGE_VALUES = ["SOIC_N", "SOIC_N_EP", "QSOP", "150mils", "RN", "150 mils"];
  // continue this service filter package OCP
  public const DAYS_UNTIL_RECLASSIFIED_AS_NEW = 10;
  public const BUSINESS_SHIFT_OFFSET = 6;
  public const FACTORIES = ['F1', 'F2', 'F3'];
  public const PRODUCTION_LINES = ['PL1', 'PL6'];
  public const SUMMARY_CATEGORY_FILTER = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];
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
    'weekly' => ['week', 'workweek'],
    'monthly' => ['year', 'month'],
    'quarterly' => ['year', 'quarter'],
    'yearly' => ['year'],
  ];

  public const FACTORY_AGGREGATES = [
    'F1F2' => [
      'wip' => [
        'wip' => ['SUM(wip.Qty)' => 'total_wip'],
        'wip-lot' => [
          'SUM(wip.Qty)' => 'total_wip',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'out' => ['SUM(out.qty)' => 'total_outs'],
        'out-lot' => [
          'SUM(out.qty)' => 'total_outs',
          'COUNT(out.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'out.import_date',
      ],
    ],
    'F1' => [
      'wip' => [
        'wip' => ['SUM(wip.Qty)' => 'total_wip'],
        'wip-lot' => [
          'SUM(wip.Qty)' => 'total_wip',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'out' => ['SUM(out.qty)' => 'total_outs'],
        'out-lot' => [
          'SUM(out.qty)' => 'total_outs',
          'COUNT(out.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'out.import_date',
      ],
    ],
    'F2' => [
      'wip' => [
        'wip' => ['SUM(wip.Qty)' => 'total_wip'],
        'wip-lot' => [
          'SUM(wip.Qty)' => 'total_wip',
          'COUNT(wip.Lot_Id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.Date_Loaded',
      ],
      'out' => [
        'out' => ['SUM(out.qty)' => 'total_outs'],
        'out-lot' => [
          'SUM(out.qty)' => 'total_outs',
          'COUNT(out.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'out.import_date',
      ],
    ],
    'F3' => [
      'wip' => [
        'wip' => ['SUM(f3.qty)' => 'total_wip'],
        'wip-lot' => [
          'SUM(f3.qty)' => 'total_wip',
          'COUNT(f3.lot_number)' => 'total_lots'
        ],
        'dateColumn' => 'f3.date_loaded',
      ],
      'out' => [
        'out' => ['SUM(f3.qty)' => 'total_outs'],
        'out-lot' => [
          'SUM(f3.qty)' => 'total_outs',
          'COUNT(f3.lot_number)' => 'total_lots'
        ],
        'dateColumn' => 'f3.import_date',
      ],
    ],
    'All' => [
      'wip' => [
        'wip' => ['SUM(wip.qty)' => 'total_wip'],
        'wip-lot' => [
          'SUM(wip.qty)' => 'total_wip',
          'COUNT(wip.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'wip.date_loaded',
      ],
      'out' => [
        'out' => ['SUM(out.qty)' => 'total_outs'],
        'out-lot' => [
          'SUM(out.qty)' => 'total_outs',
          'COUNT(out.lot_id)' => 'total_lots'
        ],
        'dateColumn' => 'out.import_date',
      ],
    ],
  ];

  public const F2_OUT_FOCUS_GROUP_INCLUSION = ['CV', 'LT', 'LTCL', 'LTI'];
  // public const F2_OUT_FOCUS_GROUP_INCLUSION = ['CV1', 'DLT', 'WLT', 'SOF'];
  public const F1_OUT_FOCUS_GROUP_EXCLUSION = ['CV', 'CV1', 'LT', 'LTCL', 'LTI', 'DLT', 'WLT', 'SOF'];
  // public const F1_OUT_FOCUS_GROUP_EXCLUSION = ['CV1', 'DLT', 'WLT', 'SOF'];
  public const FINAL_QA_STATION = ['GTTBOX', 'GTTFVI', 'GTTOQA'];
  public const FINAL_QA_STATION_T = ['GTTBOX_T', 'GTTFVI_T', 'GTTOQA_T'];
  public const TRANSFER_QA = ['GTRANS_BOX', 'GTTRANS_QA'];
  public const EWAN_PROCESS = ['GTTRES_T', 'GTSUBCON', 'GTGOUT', 'GTARCH_T', 'GTTBINLOC'];
  public const EXCLUDED_F1F2_STATIONS = ['GTTRES_T', 'GTARCH_T'];

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
  public const REEL_EXCLUDED_STATIONS_F1_BODY_SIZE_CAPACITY = [
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

  public const IMPORT_F1F2_PICKUP_EXPECTED_HEADERS = [
    "PARTNAME" => [
      "PARTNAME",
      "part_name",
      "PART NAME",
      "partname",
      "Part Name",
    ],
    "LOTID" => [
      "LOTID",
      "lot_id",
      "lotId",
      "LOT ID",
    ],
    "QTY" => [
      "QTY",
      "qty",
      "quantity",
    ],
    "PACKAGE" => [
      "PACKAGE",
      "package",
    ],
    "LC" => [
      "LC",
      'lc',
    ],
  ];

  public const IMPORT_F3_PICKUP_EXPECTED_HEADERS = [
    "PARTNAME" => [
      "PARTNAME",
      "part_name",
      "PART NAME",
      "partname",
      "Part Name",
    ],
    "LOTID" => [
      "LOTID",
      "lot_id",
      "lotId",
      "LOT ID",
    ],
    "QTY" => [
      "QTY",
      "qty",
      "quantity",
    ],
    "PACKAGE" => [
      "PACKAGE",
      "package",
    ]
  ];

  public const IMPORT_MANUAL_F1F2_OUT_EXPECTED_HEADERS = [
    'part_name' => [
      'part_name',
      'Part Name',
      'partname',
      'PARTNAME',
    ],
    'lot_id' => [
      'lot_id',
      'Lot ID',
      'lotid',
      'LOTID',
    ],
    'out_date' => [
      'Out_Date',
      'out_date',
      'Out Date',
      'outdate',
      'OUTDATE',
    ],
    'qty' => [
      'qty',
      'Qty',
      'quantity',
      'QUANTITY',
    ],
    'residual' => [
      'residual',
      'Residual',
    ],
    'test_part' => [
      'test_part',
      'Test Part',
      'testpart',
      'TESTPART',
    ],
    'test_lot_id' => [
      'test_lot_id',
      'Test Lot ID',
      'testlotid',
      'TESTLOTID',
    ],
    'focus_group' => [
      'focus_group',
      'Focus Group',
      'focusgroup',
      'FOCUSGROUP',
    ],
    'package' => [
      'package',
      'Package',
    ],
    'process_site' => [
      'process_site',
      'Process Site',
      'processsite',
      'PROCESSSITE',
    ],
    'test_site' => [
      'test_site',
      'Test Site',
      'testsite',
      'TESTSITE',
    ],
    'tray' => [
      'tray',
      'Tray',
    ],
    'bulk' => [
      'bulk',
      'Bulk',
    ],
    'date_loaded' => [
      'date_loaded',
      'Date Loaded',
      'dateloaded',
      'DATELOADED',
    ],
    'process_group' => [
      'process_group',
      'Process Group',
      'processgroup',
      'PROCESSGROUP',
    ],
    'ramp_time' => [
      'ramp_time',
      'Ramp Time',
      'ramptime',
      'RAMPTIME',
    ]
  ];


  public const IMPORT_MANUAL_F1F2_WIP_EXPECTED_HEADERS = [
    'Plant' => [
      'Plant',
      'plant',
      'PLANT',
    ],
    'Part_Name' => [
      'Part_Name',
      'part_name',
      'part name',
      'PART NAME',
      'PARTNAME',
    ],
    'Lead_Count' => [
      'Lead_Count',
      'lead_count',
      'lead count',
      'LEAD COUNT',
      'LEADCOUNT',
    ],
    'Package_Name' => [
      'Package_Name',
      'package_name',
      'package name',
      'PACKAGE NAME',
      'PACKAGENAME',
    ],
    'Lot_Id' => [
      'Lot_Id',
      'lot_id',
      'lot id',
      'LOT ID',
      'LOTID',
    ],
    'Station' => [
      'Station',
      'station',
      'STATION',
    ],
    'Qty' => [
      'Qty',
      'qty',
      'QTY',
      'quantity',
    ],
    'Lot_Type' => [
      'Lot_Type',
      'lot_type',
      'lot type',
      'LOT TYPE',
      'LOTTYPE',
    ],
    'Prod_Area' => [
      'Prod_Area',
      'prod_area',
      'prod area',
      'PROD AREA',
      'PRODAREA',
    ],
    'Lot_Status' => [
      'Lot_Status',
      'lot_status',
      'lot status',
      'LOT STATUS',
      'LOTSTATUS',
    ],
    'Date_Loaded' => [
      'Date_Loaded',
      'date_loaded',
      'date loaded',
      'DATE LOADED',
      'DATELOADED',
    ],
    'Start_Time' => [
      'Start_Time',
      'start_time',
      'start time',
      'START TIME',
      'STARTTIME',
    ],
    'Part_Type' => [
      'Part_Type',
      'part_type',
      'part type',
      'PART TYPE',
      'PARTTYPE',
    ],
    'Part_Class' => [
      'Part_Class',
      'part_class',
      'part class',
      'PART CLASS',
      'PARTCLASS',
    ],
    'Date_Code' => [
      'Date_Code',
      'date_code',
      'date code',
      'DATE CODE',
      'DATECODE',
    ],
    'Focus_Group' => [
      'Focus_Group',
      'focus_group',
      'focus group',
      'FOCUS GROUP',
      'FOCUSGROUP',
    ],
    'Process_Group' => [
      'Process_Group',
      'process_group',
      'process group',
      'PROCESS GROUP',
      'PROCESSGROUP',
    ],
    'Bulk' => [
      'Bulk',
      'bulk',
      'BULK',
    ],
    'Reqd_Time' => [
      'Reqd_Time',
      'reqd_time',
      'reqd time',
      'REQD TIME',
    ],
    'Lot_Entry_Time' => [
      'Lot_Entry_Time',
      'lot_entry_time',
      'lot entry time',
      'LOT ENTRY TIME',
      'LOTENTRYTIME',
    ],
    'Stage' => [
      'Stage',
      'stage',
      'STAGE',
    ],
    'Stage_Start_Time' => [
      'Stage_Start_Time',
      'stage_start_time',
      'stage start time',
      'STAGE START TIME',
      'STAGESTARTTIME',
    ],
    'CCD' => [
      'CCD',
      'ccd',
    ],
    'Stage_Run_Days' => [
      'Stage_Run_Days',
      'stage_run_days',
      'stage run days',
      'STAGE RUN DAYS',
      'STAGERUNDAYS',
    ],
    'Lot_Entry_Time_Days' => [
      'Lot_Entry_Time_Days',
      'lot_entry_time_days',
      'lot entry time days',
      'LOT ENTRY TIME DAYS',
      'LOTENTRYTIMEDAYS',
    ],
    'Tray' => [
      'Tray',
      'tray',
      'TRAY',
    ],
    'Backend_Leadtime' => [
      'Backend_Leadtime',
      'backend_leadtime',
      'backend leadtime',
      'BACKEND LEADTIME',
      'BACKENDLEADTIME',
    ],
    'OSL_Days' => [
      'OSL_Days',
      'osl_days',
      'osl days',
      'OSL DAYS',
      'OSLDAYS',
    ],
    'BE_Group' => [
      'BE_Group',
      'be_group',
      'be group',
      'BE GROUP',
      'BEGROUP',
    ],
    'Strategy_Code' => [
      'Strategy_Code',
      'strategy_code',
      'strategy code',
      'STRATEGY CODE',
      'STRATEGYCODE',
    ],
    'CR3' => [
      'CR3',
      'cr3',
      'CR3',
    ],
    'BE_Starttime' => [
      'BE_Starttime',
      'be_starttime',
      'be starttime',
      'BE STARTTIME',
      'BESTARTTIME',
    ],
    'BE_OSL_Days' => [
      'BE_OSL_Days',
      'be_osl_days',
      'be osl days',
      'BE OSL DAYS',
      'BEOSLDAYS',
    ],
    'Body_Size' => [
      'Body_Size',
      'body_size',
      'body size',
      'BODY SIZE',
      'BODYSIZE',
    ],
    'Auto_Part' => [
      'Auto_Part',
      'auto_part',
      'auto part',
      'AUTO PART',
      'AUTOPART',
    ],
    'Ramp_Time' => [
      'Ramp_Time',
      'ramp_time',
      'ramp time',
      'RAMP TIME',
      'RAMPTIME',
    ],
    'End_Customer' => [
      'End_Customer',
      'end_customer',
      'end customer',
      'END CUSTOMER',
      'ENDCUSTOMER',
    ],
    'Bake' => [
      'Bake',
      'bake',
      'BAKE',
    ],
    'Bake_Count' => [
      'Bake_Count',
      'bake_count',
      'bake count',
      'BAKE COUNT',
      'BAKECOUNT',
    ],
    'Test_Lot_Id' => [
      'Test_Lot_Id',
      'test_lot_id',
      'test lot id',
      'TEST LOT ID',
      'TESTLOTID',
    ],
    'Stock_Position' => [
      'Stock_Position',
      'stock_position',
      'stock position',
      'STOCK POSITION',
      'STOCKPOSITION',
    ],
    'Assy_Site' => [
      'Assy_Site',
      'assy_site',
      'assy site',
      'ASSY SITE',
      'ASSYSITE',
    ],
    'Bake_Time_Temp' => [
      'Bake_Time_Temp',
      'bake_time_temp',
      'bake time temp',
      'BAKE TIME TEMP',
      'BAKETIMETEMP',
    ]
  ];

  public const IMPORT_F3_WIP_EXPECTED_HEADERS = [
    'date_loaded' => [
      'date',
      'date today',
      'Date Today',
      'Date_Loaded',
    ],
    // 'running_ct' => [
    //   'running_ct',
    //   'running ct',
    //   'run ct',
    //   'running_count',
    //   'run_count',
    //   'RUNNING CT',
    //   'RUN CT'
    // ],
    'date_received' => [
      'date_received',
      'date received',
      'received date',
      'recv date',
      'date_recv',
      'DATE RECEIVED'
    ],
    'packing_list_srf' => [
      'packing_list_srf_#',
      'packing list srf#',
      'packing list srf',
      'srf number',
      'srf#',
      'packing_list_srf',
      'SRF#'
    ],
    'po_number' => [
      'po_number',
      'p.o number',
      'po number',
      'purchase order number',
      'po#',
      'PO NUMBER'
    ],
    'machine_number' => [
      'machine_number',
      'machine #',
      'machine number',
      'machine_no',
      'MACHINE NUMBER'
    ],
    'part_number' => [
      'part_number',
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
      'lot_number',
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
      'date_commit',
      'date commit',
      'commit date',
      'date_committed',
      'DATE COMMIT'
    ],
    'actual_date_time' => [
      'actual_date_time',
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
    // 'focus_group' => [
    //   'focus_group',
    //   'focus group',
    //   'group',
    //   'FOCUS GROUP'
    // ],
    'gap_analysis' => [
      'gap_analysis',
      'gap analysis',
      'gap',
      'GAP ANALYSIS'
    ],
    'cycle_time' => [
      'cycle_time',
      'cycle time',
      'ct',
      'CYCLE TIME'
    ]
  ];

  public const IMPORT_F3_OUT_EXPECTED_HEADERS = [
    'date_loaded' => [
      'date',
      'date today',
      'Date Today',
    ],
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
