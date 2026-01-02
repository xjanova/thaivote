<?php

namespace Database\Seeders;

use App\Models\Constituency;
use App\Models\Province;
use Illuminate\Database\Seeder;

class ConstituencySeeder extends Seeder
{
    /**
     * สร้างข้อมูลเขตเลือกตั้ง 400 เขต ตามจำนวนเขตของแต่ละจังหวัด
     * อ้างอิงจาก กกต. (สำนักงานคณะกรรมการการเลือกตั้ง) 2566
     * https://www.ect.go.th/
     */
    public function run(): void
    {
        $provinces = Province::all();

        if ($provinces->isEmpty()) {
            $this->command->error('No provinces found. Please run ProvinceSeeder first.');

            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($provinces as $province) {
            for ($i = 1; $i <= $province->total_constituencies; $i++) {
                $constituency = Constituency::where('province_id', $province->id)
                    ->where('number', $i)
                    ->first();

                $data = [
                    'province_id' => $province->id,
                    'number' => $i,
                    'name' => "เขต {$i}",
                    'total_eligible_voters' => $this->estimateVoters($province->population, $province->total_constituencies),
                    'total_polling_stations' => $this->estimatePollingStations($province->population, $province->total_constituencies),
                ];

                if ($constituency) {
                    $constituency->update($data);
                    $updated++;
                } else {
                    Constituency::create($data);
                    $created++;
                }
            }
        }

        $this->command->info("Constituencies: {$created} created, {$updated} updated.");
    }

    /**
     * ประมาณจำนวนผู้มีสิทธิ์เลือกตั้ง (ประมาณ 80% ของประชากรต่อเขต)
     */
    private function estimateVoters(int $provincePop, int $constituencies): int
    {
        $votersPerConstituency = ($provincePop / $constituencies) * 0.8;

        return (int) round($votersPerConstituency);
    }

    /**
     * ประมาณจำนวนหน่วยเลือกตั้ง (ประมาณ 1 หน่วยต่อ 800-1000 ผู้มีสิทธิ์)
     */
    private function estimatePollingStations(int $provincePop, int $constituencies): int
    {
        $votersPerConstituency = ($provincePop / $constituencies) * 0.8;

        return (int) ceil($votersPerConstituency / 900);
    }
}
