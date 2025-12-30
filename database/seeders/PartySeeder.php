<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    /**
     * ข้อมูลพรรคการเมืองหลัก อ้างอิงจาก กกต. (สำนักงานคณะกรรมการการเลือกตั้ง)
     * https://www.ect.go.th/
     */
    public function run(): void
    {
        $parties = [
            [
                'name_th' => 'พรรคก้าวไกล',
                'name_en' => 'Move Forward Party',
                'abbreviation' => 'ก้าวไกล',
                'color' => '#FF6B00',
                'secondary_color' => '#FF8C33',
                'leader_name' => 'พิธา ลิ้มเจริญรัตน์',
                'founded_year' => 2020,
                'logo' => '/images/parties/move-forward.png',
                'website' => 'https://moveforwardparty.org',
                'party_number' => 25,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคเพื่อไทย',
                'name_en' => 'Pheu Thai Party',
                'abbreviation' => 'เพื่อไทย',
                'color' => '#E31E25',
                'secondary_color' => '#FF4D52',
                'leader_name' => 'แพทองธาร ชินวัตร',
                'founded_year' => 2008,
                'logo' => '/images/parties/pheu-thai.png',
                'website' => 'https://ptp.or.th',
                'party_number' => 26,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคภูมิใจไทย',
                'name_en' => 'Bhumjaithai Party',
                'abbreviation' => 'ภูมิใจไทย',
                'color' => '#0066B3',
                'secondary_color' => '#3399CC',
                'leader_name' => 'อนุทิน ชาญวีรกูล',
                'founded_year' => 2008,
                'logo' => '/images/parties/bhumjaithai.png',
                'website' => 'https://www.bhumjaithai.com',
                'party_number' => 3,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคพลังประชารัฐ',
                'name_en' => 'Palang Pracharath Party',
                'abbreviation' => 'พลังประชารัฐ',
                'color' => '#1E3A8A',
                'secondary_color' => '#3B5998',
                'leader_name' => 'ประวิตร วงษ์สุวรรณ',
                'founded_year' => 2018,
                'logo' => '/images/parties/pprp.png',
                'website' => 'https://www.pprp.or.th',
                'party_number' => 21,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรครวมไทยสร้างชาติ',
                'name_en' => 'United Thai Nation Party',
                'abbreviation' => 'รวมไทยสร้างชาติ',
                'color' => '#6B21A8',
                'secondary_color' => '#9333EA',
                'leader_name' => 'พลเอก ประยุทธ์ จันทร์โอชา',
                'founded_year' => 2021,
                'logo' => '/images/parties/utn.png',
                'website' => 'https://www.ruamthai.or.th',
                'party_number' => 36,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคประชาธิปัตย์',
                'name_en' => 'Democrat Party',
                'abbreviation' => 'ประชาธิปัตย์',
                'color' => '#00AEEF',
                'secondary_color' => '#66D3FF',
                'leader_name' => 'จุรินทร์ ลักษณวิศิษฏ์',
                'founded_year' => 1946,
                'logo' => '/images/parties/democrat.png',
                'website' => 'https://www.democrat.or.th',
                'party_number' => 4,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคชาติไทยพัฒนา',
                'name_en' => 'Chart Thai Pattana Party',
                'abbreviation' => 'ชาติไทยพัฒนา',
                'color' => '#8B4513',
                'secondary_color' => '#A0522D',
                'leader_name' => 'วราวุธ ศิลปอาชา',
                'founded_year' => 2008,
                'logo' => '/images/parties/ctp.png',
                'website' => null,
                'party_number' => 5,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคประชาชาติ',
                'name_en' => 'Prachachat Party',
                'abbreviation' => 'ประชาชาติ',
                'color' => '#00FF7F',
                'secondary_color' => '#32CD32',
                'leader_name' => 'วันมูหะมัดนอร์ มะทา',
                'founded_year' => 2018,
                'logo' => '/images/parties/prachachat.png',
                'website' => null,
                'party_number' => 24,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคไทยสร้างไทย',
                'name_en' => 'Thai Sang Thai Party',
                'abbreviation' => 'ไทยสร้างไทย',
                'color' => '#FFC107',
                'secondary_color' => '#FFD54F',
                'leader_name' => 'คุณหญิงสุดารัตน์ เกยุราพันธุ์',
                'founded_year' => 2021,
                'logo' => '/images/parties/tst.png',
                'website' => 'https://thaisangthai.org',
                'party_number' => 49,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคเสรีรวมไทย',
                'name_en' => 'Thai Liberal Party',
                'abbreviation' => 'เสรีรวมไทย',
                'color' => '#FF69B4',
                'secondary_color' => '#FF1493',
                'leader_name' => 'พลตำรวจเอก เสรีพิศุทธ์ เตมียเวส',
                'founded_year' => 2018,
                'logo' => '/images/parties/tlp.png',
                'website' => null,
                'party_number' => 28,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคชาติพัฒนากล้า',
                'name_en' => 'Chart Pattana Kla Party',
                'abbreviation' => 'ชาติพัฒนากล้า',
                'color' => '#4B0082',
                'secondary_color' => '#8B008B',
                'leader_name' => 'กรณ์ จาติกวณิช',
                'founded_year' => 2020,
                'logo' => '/images/parties/cpk.png',
                'website' => null,
                'party_number' => 34,
                'is_active' => true,
            ],
            [
                'name_th' => 'พรรคประชาธิปไตยใหม่',
                'name_en' => 'New Democracy Party',
                'abbreviation' => 'ประชาธิปไตยใหม่',
                'color' => '#2E8B57',
                'secondary_color' => '#3CB371',
                'leader_name' => 'สุรทิน พิจารณ์',
                'founded_year' => 2022,
                'logo' => '/images/parties/ndp.png',
                'website' => null,
                'party_number' => 61,
                'is_active' => true,
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($parties as $partyData) {
            $party = Party::where('name_th', $partyData['name_th'])->first();

            if ($party) {
                // Update existing party if data changed
                $party->update($partyData);
                $updated++;
            } else {
                // Create new party
                Party::create($partyData);
                $created++;
            }
        }

        $this->command->info("Parties: {$created} created, {$updated} updated.");
    }
}
