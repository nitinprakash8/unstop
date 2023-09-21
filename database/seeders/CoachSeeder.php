<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoachSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coaches = 1;
        $seats = 80;
        $seatInRows = 7;
        $rows = $seats / $seatInRows;
        $rows = $rows > (int) $rows ? (int) $rows + 1 : $rows - 1;
        $seatSet = 1;
        for ($coach = 1; $coach <= $coaches; $coach++) {
            for ($row = 1; $row <= $rows; $row++) {
                for ($seat = 1; $seat <= $seatInRows; $seat++) {
                    DB::table('coaches')->insert([
                        'coach_number' => $coach,
                        'row_number' => $row,
                        'seat_number' => $seatSet,
                        'is_reserved' => false
                    ]);
                    if ($seatSet == $seats) {
                        break;
                    }
                    $seatSet++;
                }


            }
        }
    }
}