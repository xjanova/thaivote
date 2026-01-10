<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    /**
     * ข้อมูลพรรคการเมืองหลัก อ้างอิงจาก กกต. (สำนักงานคณะกรรมการการเลือกตั้ง)
     * https://www.ect.go.th/
     * https://party.ect.go.th/party-info
     *
     * อัปเดตล่าสุด: เลือกตั้ง 8 กุมภาพันธ์ 2569 (2026)
     * ข้อมูลจากการจับสลากเลขที่พรรค วันที่ 28 ธันวาคม 2568
     *
     * หมายเหตุ: พรรคก้าวไกลถูกยุบ 7 สิงหาคม 2567 -> แทนที่ด้วยพรรคประชาชน
     */
    public function run(): void
    {
        $parties = [
            // พรรคหลัก (Major Parties)
            [
                'name_th' => 'พรรคประชาชน',
                'name_en' => "People's Party",
                'abbreviation' => 'ปชช.',
                'color' => '#FF6B00',
                'secondary_color' => '#FF8C33',
                'leader_name' => 'นทีธร บุญประคม',
                'founded_year' => 2024,
                'logo' => null,
                'website' => 'https://peoplesparty.or.th',
                'party_number' => 46,
                'is_active' => true,
                'description' => 'พรรคประชาชนเป็นพรรคการเมืองก้าวหน้าที่สืบทอดอุดมการณ์จากพรรคก้าวไกล (ถูกยุบ 7 สิงหาคม 2567) ใช้สีส้มเป็นสัญลักษณ์',
            ],
            [
                'name_th' => 'พรรคเพื่อไทย',
                'name_en' => 'Pheu Thai Party',
                'abbreviation' => 'พท.',
                'color' => '#E31E25',
                'secondary_color' => '#FF4D52',
                'leader_name' => 'แพทองธาร ชินวัตร',
                'founded_year' => 2008,
                'logo' => null,
                'website' => 'https://ptp.or.th',
                'party_number' => 9,
                'is_active' => true,
                'description' => 'พรรคการเมืองรัฐบาลปัจจุบัน นำโดยนางสาวแพทองธาร ชินวัตร',
            ],
            [
                'name_th' => 'พรรคภูมิใจไทย',
                'name_en' => 'Bhumjaithai Party',
                'abbreviation' => 'ภท.',
                'color' => '#0066B3',
                'secondary_color' => '#3399CC',
                'leader_name' => 'อนุทิน ชาญวีรกูล',
                'founded_year' => 2008,
                'logo' => null,
                'website' => 'https://www.bhumjaithai.com',
                'party_number' => 37,
                'is_active' => true,
                'description' => 'พรรคร่วมรัฐบาล นำโดยนายอนุทิน ชาญวีรกูล',
            ],
            [
                'name_th' => 'พรรคประชาธิปัตย์',
                'name_en' => 'Democrat Party',
                'abbreviation' => 'ปชป.',
                'color' => '#00AEEF',
                'secondary_color' => '#66D3FF',
                'leader_name' => 'จุรินทร์ ลักษณวิศิษฏ์',
                'founded_year' => 1946,
                'logo' => null,
                'website' => 'https://www.democrat.or.th',
                'party_number' => 27,
                'is_active' => true,
                'description' => 'พรรคการเมืองเก่าแก่ที่สุดในประเทศไทย ก่อตั้งปี พ.ศ. 2489',
            ],
            [
                'name_th' => 'พรรคพลังประชารัฐ',
                'name_en' => 'Palang Pracharath Party',
                'abbreviation' => 'พปชร.',
                'color' => '#1E3A8A',
                'secondary_color' => '#3B5998',
                'leader_name' => 'ประวิตร วงษ์สุวรรณ',
                'founded_year' => 2018,
                'logo' => null,
                'website' => 'https://www.pprp.or.th',
                'party_number' => 43,
                'is_active' => true,
                'description' => 'พรรคการเมืองที่สนับสนุนรัฐบาล คสช. ในอดีต',
            ],
            [
                'name_th' => 'พรรคไทยสร้างไทย',
                'name_en' => 'Thai Sang Thai Party',
                'abbreviation' => 'ทสท.',
                'color' => '#FFC107',
                'secondary_color' => '#FFD54F',
                'leader_name' => 'คุณหญิงสุดารัตน์ เกยุราพันธุ์',
                'founded_year' => 2021,
                'logo' => null,
                'website' => 'https://thaisangthai.org',
                'party_number' => 48,
                'is_active' => true,
                'description' => 'พรรคการเมืองนำโดยคุณหญิงสุดารัตน์ เกยุราพันธุ์',
            ],
            [
                'name_th' => 'พรรคประชาธิปไตยใหม่',
                'name_en' => 'New Democracy Party',
                'abbreviation' => 'ปชธ.',
                'color' => '#2E8B57',
                'secondary_color' => '#3CB371',
                'leader_name' => 'สุรทิน พิจารณ์',
                'founded_year' => 2022,
                'logo' => null,
                'website' => null,
                'party_number' => 8,
                'is_active' => true,
                'description' => 'พรรคการเมืองใหม่ เน้นนโยบายประชาธิปไตย',
            ],
            [
                'name_th' => 'พรรคเสรีรวมไทย',
                'name_en' => 'Seri Ruam Thai Party',
                'abbreviation' => 'สรท.',
                'color' => '#FF69B4',
                'secondary_color' => '#FF1493',
                'leader_name' => 'พลตำรวจเอก เสรีพิศุทธ์ เตมียเวส',
                'founded_year' => 2018,
                'logo' => null,
                'website' => null,
                'party_number' => 12,
                'is_active' => true,
                'description' => 'พรรคเสรีรวมไทย เน้นเสรีภาพและประชาธิปไตย',
            ],
            [
                'name_th' => 'พรรคกล้าธรรม',
                'name_en' => 'Kla Dharma Party',
                'abbreviation' => 'กธ.',
                'color' => '#4B0082',
                'secondary_color' => '#8B008B',
                'leader_name' => 'กัปตัน ธรรมนัส พรหมเผ่า',
                'founded_year' => 2020,
                'logo' => null,
                'website' => null,
                'party_number' => 42,
                'is_active' => true,
                'description' => 'พรรคการเมืองนำโดยกัปตัน ธรรมนัส พรหมเผ่า อดีต รมว.เกษตรและสหกรณ์',
            ],
            [
                'name_th' => 'พรรครวมจิตไทย',
                'name_en' => 'Ruam Jai Thai Party',
                'abbreviation' => 'รจท.',
                'color' => '#006400',
                'secondary_color' => '#228B22',
                'leader_name' => null,
                'founded_year' => 2021,
                'logo' => null,
                'website' => null,
                'party_number' => 5,
                'is_active' => true,
                'description' => 'พรรครวมจิตไทย',
            ],
            [
                'name_th' => 'พรรคทางเลือกใหม่',
                'name_en' => 'New Alternative Party',
                'abbreviation' => 'ทลม.',
                'color' => '#800080',
                'secondary_color' => '#9370DB',
                'leader_name' => null,
                'founded_year' => 2022,
                'logo' => null,
                'website' => null,
                'party_number' => 10,
                'is_active' => true,
                'description' => 'พรรคทางเลือกใหม่',
            ],
            [
                'name_th' => 'พรรครวมพลังประชาชน',
                'name_en' => 'Ruam Palang Prachachon Party',
                'abbreviation' => 'รพช.',
                'color' => '#8B0000',
                'secondary_color' => '#DC143C',
                'leader_name' => null,
                'founded_year' => 2020,
                'logo' => null,
                'website' => null,
                'party_number' => 13,
                'is_active' => true,
                'description' => 'พรรครวมพลังประชาชน',
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
