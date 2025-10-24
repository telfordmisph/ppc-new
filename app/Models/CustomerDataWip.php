<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDataWip extends Model
{
  protected $table = 'customer_data_wip';
  public $timestamps = false;

  protected $fillable = [
    'Plant',
    'Part_Name',
    'Lead_Count',
    'Package_Name',
    'Lot_Id',
    'Station',
    'Qty',
    'Lot_Type',
    'Prod_Area',
    'Lot_Status',
    'Date_Loaded',
    'Start_Time',
    'Part_Type',
    'Part_Class',
    'Date_Code',
    'Focus_Group',
    'Process_Group',
    'Bulk',
    'Reqd_Time',
    'Lot_Entry_Time',
    'Stage',
    'Stage_Start_Time',
    'CCD',
    'Stage_Run_Days',
    'Lot_Entry_Time_Days',
    'Tray',
    'Backend_Leadtime',
    'OSL_Days',
    'BE_Group',
    'Strategy_Code',
    'CR3',
    'BE_Starttime',
    'BE_OSL_Days',
    'Body_Size',
    'Auto_Part',
    'Ramp_Time',
    'End_Customer',
    'Bake',
    'Bake_Count',
    'Test_Lot_Id',
    'Stock_Position',
    'Assy_Site',
    'Bake_Time_Temp',
    'imported_by'
  ];
}
