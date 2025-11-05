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
  public const FACTORIES = ['F1', 'F2', 'F3'];
  public const PRODUCTION_LINES = ['PL1', 'PL6'];
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
}
