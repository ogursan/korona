<?php
namespace Device;

use Database\DB;
use \Exception;

class Helper
{
    public function handle($data)
    {
        $device = DB::select("SELECT * FROM machines WHERE serial = :serial", [':serial' => $data['serial']]);

        if (count($device) == 0) {
            throw new Exception(sprintf('Unable to find device with serial %s', $data['serial']));
        }

        $device = array_shift($device);

        DB::exec(
            "UPDATE machines_options SET firmware = :firmware, connect_freq = :connect_freq WHERE machine_id = :id",
            [
                ':firmware' => $data['firmware'],
                ':connect_freq' => $data['connect_freq'],
                ':id' => $device['id'],
            ]
        );

        DB::exec("DELETE FROM machines_options_set WHERE machine_id = :id", [':id' => $device['id']]);
    }
}